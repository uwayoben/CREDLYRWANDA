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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->string('expense_number')->unique();

            $table->date('expense_date');
            $table->string('item');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('category'); // e.g., 'Salaries', 'Rent', 'Utilities', 'Office Supplies', 'Marketing'

            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque'])->default('cash');
            $table->string('receipt_attachment')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'expense_date', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
