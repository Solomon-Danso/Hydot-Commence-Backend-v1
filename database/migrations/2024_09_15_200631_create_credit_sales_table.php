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
        Schema::create('credit_sales', function (Blueprint $table) {
            $table->id();
            $table->longText("OrderId")->nullable();
            $table->longText("ReferenceId")->nullable();
            $table->longText("Phone")->nullable();
            $table->longText("Email")->nullable();
            $table->decimal("CreditAmount")->nullable();
            $table->longText("UserId")->nullable();
            $table->longText("FullName")->nullable();
            $table->longText("DigitalAddress")->nullable();
            $table->longText("NationalIDType")->nullable();
            $table->longText("NationalID")->nullable();
            $table->decimal("BalanceLeft")->nullable();
            $table->boolean("IsApproved")->default(false);
            $table->dateTime("NextBillingDate")->nullable();
            $table->decimal("AmountToPay")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_sales');
    }
};
