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
        Schema::table('shopping_card_collectors', function (Blueprint $table) {
            $table->longText("Status")->nullable();
            $table->longText("Email")->nullable();
            $table->longText("CardNumber")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_card_collectors', function (Blueprint $table) {
            //
        });
    }
};
