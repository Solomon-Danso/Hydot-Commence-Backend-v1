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
        Schema::create('shopping_cards', function (Blueprint $table) {
            $table->id();
            $table->longText("CardNumber")->nullable();
            $table->longText("PurchasedByID")->nullable();
            $table->decimal("Amount")->nullable();
            $table->longText("AccountHolderName")->nullable();
            $table->longText("AccountHolderID")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_cards');
    }
};
