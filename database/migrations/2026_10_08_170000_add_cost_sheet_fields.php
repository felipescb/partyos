<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('description');
            $table->bigInteger('unit_amount')->nullable()->after('quantity');
            $table->string('detail')->nullable()->after('unit_amount');
            $table->string('responsible_name')->nullable()->after('payment_method');
            $table->string('pix')->nullable()->after('responsible_name');
            $table->string('invoice_number')->nullable()->after('pix');
            $table->string('invoice_url')->nullable()->after('invoice_number');
            $table->unsignedInteger('sort_order')->default(0)->after('notes');
        });

        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->unsignedInteger('payout_basis_points')->nullable()->after('sold_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn([
                'quantity',
                'unit_amount',
                'detail',
                'responsible_name',
                'pix',
                'invoice_number',
                'invoice_url',
                'sort_order',
            ]);
        });

        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropColumn('payout_basis_points');
        });
    }
};
