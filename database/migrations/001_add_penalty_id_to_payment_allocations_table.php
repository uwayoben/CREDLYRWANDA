<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreignId('penalty_id')->nullable()->constrained('penalities')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropForeign(['penalty_id']);
            $table->dropColumn('penalty_id');
        });
    }
};