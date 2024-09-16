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
        Schema::create('collection_accounts', function (Blueprint $table) {
            $table->id();
            $table->longText("AccountType")->nullable();
            $table->longText("OrderId")->nullable();
            $table->longText("AccountId")->nullable();
            $table->longText("Phone")->nullable();
            $table->longText("Email")->nullable();
            $table->decimal("Debit")->nullable();
            $table->decimal("Credit")->nullable();
            $table->decimal("Balance")->nullable();
            $table->decimal("AmountToPay")->nullable();
            $table->longText("UserId")->nullable();
            $table->longText("FullName")->nullable();
            $table->dateTime("NextBillingDate")->nullable();
            $table->dateTime("Deadline")->nullable();
            $table->integer("DaysToPayment"); // Column to hold number of days for payment



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_accounts');
    }
};
