<?php

namespace App\Domain\Finance;

use App\Enums\AdjustmentKind;
use App\Enums\CostStatus;
use App\Enums\PaymentStatus;
use App\Enums\RevenueCategory;
use App\Enums\RevenueStatus;
use App\Models\Audit;
use App\Models\BudgetItem;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Throwable;

final class AuditSummary
{
    /** @var list<string> */
    private const SKIPPED = [
        'id',
        'event_id',
        'budget_id',
        'budget_item_id',
        'cost_category_id',
        'vendor_id',
        'assignee_id',
        'organization_id',
        'recorded_by',
        'sort_order',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /** @var list<string> */
    private const MONEY = [
        'estimated_amount',
        'contracted_amount',
        'unit_amount',
        'expected_amount',
        'actual_amount',
        'amount',
        'price',
    ];

    public function phrase(Audit $audit): string
    {
        $kind = class_basename($audit->auditable_type);
        $name = $this->name($audit);
        $article = $this->article($kind);
        $noun = $this->noun($kind);

        if ($audit->action === 'created' && $kind === 'Payment') {
            $amount = $this->money($audit->new_values['amount'] ?? 0);
            $place = $name !== null ? " em {$name}" : '';

            return "criou o pagamento de {$amount}{$place}";
        }

        $subject = trim("{$article} {$noun}".($name !== null ? " {$name}" : ''));
        $verb = match ($audit->action) {
            'created' => 'criou',
            'deleted' => 'excluiu',
            default => 'alterou',
        };

        $detail = match ($audit->action) {
            'created' => $this->createdDetail($audit),
            'updated' => $this->changes($audit),
            default => null,
        };

        return $detail === null ? "{$verb} {$subject}" : "{$verb} {$subject} · {$detail}";
    }

    private function name(Audit $audit): ?string
    {
        $model = $audit->relationLoaded('auditable') ? $audit->auditable : $audit->auditable()->first();

        if ($model instanceof Payment) {
            $model->loadMissing('budgetItem');
            $fromItem = $this->clean($model->budgetItem?->description);

            if ($fromItem !== null) {
                return $fromItem;
            }
        }

        if ($model !== null) {
            foreach (['description', 'name', 'beneficiary_name'] as $key) {
                $value = $this->clean($model->getAttribute($key));

                if ($value !== null) {
                    return $value;
                }
            }
        }

        $bags = [$audit->new_values ?? [], $audit->old_values ?? []];

        foreach ($bags as $bag) {
            foreach (['description', 'name', 'beneficiary_name'] as $key) {
                $value = $this->clean($bag[$key] ?? null);

                if ($value !== null) {
                    return $value;
                }
            }

            $budgetItemId = $bag['budget_item_id'] ?? null;

            if (is_numeric($budgetItemId)) {
                $value = $this->clean(BudgetItem::query()->find((int) $budgetItemId)?->description);

                if ($value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function createdDetail(Audit $audit): ?string
    {
        $values = $audit->new_values ?? [];
        $keys = match (class_basename($audit->auditable_type)) {
            'Revenue' => ['expected_amount', 'actual_amount'],
            'BudgetItem' => ['contracted_amount', 'estimated_amount'],
            'TicketTier' => ['price'],
            'EventFee', 'RevenueDistribution' => ['amount', 'basis_points'],
            default => [],
        };

        foreach ($keys as $key) {
            if (! array_key_exists($key, $values) || $values[$key] === null || $values[$key] === '') {
                continue;
            }

            return $this->fieldLabel($key).' '.$this->formatValue($audit, $key, $values[$key]);
        }

        return null;
    }

    private function changes(Audit $audit): ?string
    {
        $parts = [];

        foreach ($audit->new_values ?? [] as $key => $value) {
            if (in_array($key, self::SKIPPED, true)) {
                continue;
            }

            $before = $this->formatValue($audit, $key, $audit->old_values[$key] ?? null);
            $after = $this->formatValue($audit, $key, $value);

            if ($before === $after) {
                continue;
            }

            $parts[] = $this->fieldLabel($key)." de {$before} para {$after}";
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    private function formatValue(Audit $audit, string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (in_array($key, self::MONEY, true)) {
            return $this->money($value);
        }

        if ($key === 'basis_points' && is_numeric($value)) {
            return Money::formatPercent((int) $value);
        }

        if ($key === 'status') {
            return $this->statusLabel($audit, (string) $value);
        }

        if ($key === 'category') {
            return RevenueCategory::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if ($key === 'kind') {
            return AdjustmentKind::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if (str_ends_with($key, '_on') || str_ends_with($key, '_at')) {
            return $this->date($value);
        }

        return $this->clean($value) ?? '—';
    }

    private function statusLabel(Audit $audit, string $value): string
    {
        $enum = match (class_basename($audit->auditable_type)) {
            'BudgetItem' => CostStatus::class,
            'Payment' => PaymentStatus::class,
            'Revenue' => RevenueStatus::class,
            default => null,
        };

        if ($enum === null) {
            return $value;
        }

        return $enum::tryFrom($value)?->label() ?? $value;
    }

    private function money(mixed $cents): string
    {
        return Money::format(is_numeric($cents) ? (int) $cents : 0);
    }

    private function date(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '—';
        }

        try {
            return CarbonImmutable::parse($value)->format('d/m/Y');
        } catch (Throwable) {
            return $value;
        }
    }

    private function fieldLabel(string $key): string
    {
        return match ($key) {
            'status' => 'status',
            'description', 'name' => 'nome',
            'beneficiary_name' => 'quem',
            'estimated_amount' => 'estimado',
            'contracted_amount' => 'contratado',
            'unit_amount' => 'unitário',
            'quantity' => 'quantidade',
            'expected_amount' => 'previsto',
            'actual_amount' => 'entrou',
            'amount' => 'valor',
            'price' => 'preço',
            'paid_on' => 'pago em',
            'due_on' => 'vencimento',
            'occurred_on' => 'data',
            'category', 'source', 'revenue_category' => 'origem',
            'sold_quantity' => 'vendidos',
            'goal' => 'meta',
            'method', 'payment_method' => 'pagamento',
            'notes' => 'observação',
            'detail' => 'detalhe',
            'basis_points' => 'percentual',
            'kind' => 'tipo',
            'applies_to' => 'incide em',
            'responsible_name' => 'responsável',
            'pix' => 'pix',
            'invoice_number' => 'nota',
            'starts_at' => 'começa',
            'ends_at' => 'termina',
            default => str_replace('_', ' ', $key),
        };
    }

    private function noun(string $kind): string
    {
        return match ($kind) {
            'BudgetItem' => 'custo',
            'Payment' => 'pagamento',
            'Revenue' => 'receita',
            'TicketTier' => 'ingresso',
            'RevenueDistribution' => 'divisão',
            'EventFee' => 'taxa',
            default => strtolower($kind),
        };
    }

    private function article(string $kind): string
    {
        return match ($kind) {
            'Revenue', 'RevenueDistribution', 'EventFee' => 'a',
            default => 'o',
        };
    }

    private function clean(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
