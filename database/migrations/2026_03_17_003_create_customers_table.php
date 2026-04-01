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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('names');
            $table->string('national_id')->unique();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('province');
            $table->string('district');
            $table->string('sector');
            $table->string('cell');
            $table->string('village');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed']);
            $table->string('spouse_name')->nullable();
$table->string('spouse_phone')->nullable();
$table->string('spause_id_number')->nullable();
$table->string('marital_property_regime')->nullable();
            $table->string('employer_name')->nullable();
            $table->enum('employment_status', ['employed', 'self_employed', 'unemployed', 'retired'])->nullable();
            $table->string('relationship_with_ndfsp')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'national_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
