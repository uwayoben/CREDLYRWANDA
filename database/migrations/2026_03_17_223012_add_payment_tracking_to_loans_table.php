<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->default(0)->after('total_amount');
        $table->decimal('principal_paid', 15, 2)->default(0)->after('amount_paid');
        $table->decimal('interest_paid', 15, 2)->default(0)->after('principal_paid');
        $table->decimal('penalty_paid', 15, 2)->default(0)->after('interest_paid');
        $table->decimal('remaining_balance', 15, 2)->default(0)->after('penalty_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
        $table->dropColumn([
            'amount_paid', 'principal_paid', 'interest_paid',
            'penalty_paid', 'remaining_balance',
        ]);
    });
    }
};
