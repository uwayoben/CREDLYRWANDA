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
        Schema::create('other_incomes', function (Blueprint $table) {
            $table->id();
             $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('received_by')->constrained('users');
            $table->string('income_number')->unique();

            $table->date('income_date');
            $table->string('source'); // e.g., 'Registration Fees', 'Consultancy', 'Investment Income'
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);

            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->string('attachment')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'income_date', 'source']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_incomes');
    }
};
