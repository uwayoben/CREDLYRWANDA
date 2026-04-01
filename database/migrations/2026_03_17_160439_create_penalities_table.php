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
        Schema::create('penalities', function (Blueprint $table) {
            $table->id();
           $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('installment_id')->nullable()->constrained()->onDelete('cascade');

            $table->decimal('penalty_amount', 15, 2);
            $table->date('penalty_date');
            $table->text('reason')->nullable();
            $table->boolean('is_waived')->default(false);
            $table->date('waived_date')->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users');

            $table->timestamps();

            $table->index(['loan_id', 'penalty_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalities');
    }
};
