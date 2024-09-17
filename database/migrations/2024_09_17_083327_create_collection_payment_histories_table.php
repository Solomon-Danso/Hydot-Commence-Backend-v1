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
        Schema::create('collection_payment_histories', function (Blueprint $table) {
            $table->id();
            $table->longText("AccountType")->nullable();
            $table->longText("AccountId")->nullable();
            $table->longText("UserId")->nullable();
            $table->longText("OrderId")->nullable();
            $table->decimal("OldBalance")->nullable();
            $table->decimal("AmountPaid")->nullable();
            $table->decimal("NewBalance")->nullable();
            $table->longText("TransactionId")->nullable();
            $table->longText("Email")->nullable();
            $table->longText("Status")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_payment_histories');
    }
};
