<?php

namespace App\Livewire\Admin;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Domain\Accounts\CreatePlatformAccount;
use App\Enums\AccountKind;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
#[Title('Contas')]
class AccountBoard extends Component
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    public bool $showForm = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $kind = 'organizer';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isPlatformAdmin(), 403);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(CreatePlatformAccount $create): void
    {
        $validated = $this->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'kind' => ['required', Rule::enum(AccountKind::class)],
        ], [
            'name.required' => 'Qual é o nome da pessoa?',
            'email.required' => 'Qual é o e-mail?',
            'email.email' => 'Esse e-mail não parece válido.',
            'email.unique' => 'Já existe uma conta com esse e-mail.',
            'password.required' => 'Defina uma senha inicial.',
            'password.confirmed' => 'A confirmação da senha não bate.',
            'kind.required' => 'Essa conta vai criar eventos ou participar?',
        ]);

        $create->handle(
            $validated['name'],
            $validated['email'],
            $validated['password'],
            AccountKind::from($validated['kind']),
        );

        $this->showForm = false;
        $this->resetForm();
        Flux::toast(variant: 'success', text: 'Conta criada. Passe o e-mail e a senha para a pessoa.');
    }

    public function render(): View
    {
        return view('livewire.admin.accounts', [
            'accounts' => User::query()->orderBy('name')->get(),
            'kinds' => AccountKind::cases(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset('name', 'email', 'password', 'password_confirmation');
        $this->kind = AccountKind::Organizer->value;
        $this->resetValidation();
    }
}
