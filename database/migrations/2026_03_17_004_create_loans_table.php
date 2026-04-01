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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
              $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->string('loan_number')->unique();

            // Loan Details
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2); // Percentage
            $table->enum('interest_type', ['declining', 'flat'])->default('declining');
            $table->integer('number_of_installments');
            $table->enum('installment_frequency', ['daily', 'weekly', 'bi_weekly', 'monthly', 'quarterly']);

            // Calculated Amounts
            $table->decimal('total_interest', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->decimal('application_fee', 15, 2)->default(0);
            $table->decimal('penalty_rate', 5, 2)->default(0); // Percentage per day/week

            // Dates
            $table->date('disbursement_date');
            $table->date('first_payment_date');
            $table->date('last_payment_date');
            $table->date('expected_completion_date');
             $table->enum('loan_status', [
                'pending',
                'approved',
                'disbursed',
                'active',
                'completed',
                'defaulted',
                'written_off',
                'rejected'
            ])->default('pending');

            $table->string('loan_class');

            $table->date('date_when_arrears_start')->nullable();
            $table->decimal('arrears_amount', 15, 2)->default(0);

            // Collateral
            $table->text('collateral_details')->nullable();
            $table->decimal('collateral_value', 15, 2)->nullable();
            $table->string('guarantee_collateral')->nullable();

            // Purpose and Notes
            $table->text('purpose')->nullable();
             $table->text('notes')->nullable();

            // Tracking
            $table->date('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->date('completed_at')->nullable();
            $table->date('disbursed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'loan_status']);
            $table->index(['customer_id', 'loan_status']);
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
