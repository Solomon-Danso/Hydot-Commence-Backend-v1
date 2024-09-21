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
        Schema::create('payment_on_deliveries', function (Blueprint $table) {
            $table->id();
            $table->longText("OrderId")->nullable();
            $table->longText("PaymentOnDeliveryID")->nullable();
            $table->longText("Phone")->nullable();
            $table->longText("Email")->nullable();
            $table->decimal("Amount")->nullable();
            $table->longText("UserId")->nullable();
            $table->longText("FullName")->nullable();
            $table->boolean("IsFullyPaid")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_on_deliveries');
    }
};
