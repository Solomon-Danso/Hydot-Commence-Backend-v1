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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->longText("CartId")->nullable();
            $table->longText("MenuId")->nullable();
            $table->longText("CategoryId")->nullable();
            $table->longText("ProductId")->nullable();
            $table->longText("Picture")->nullable();
            $table->longText("Title")->nullable();
            $table->decimal("Price")->nullable();
            $table->integer("Quantity")->nullable();
            $table->longText("Size")->nullable();
            $table->longText("UserId")->nullable();
            $table->longText("OrderId")->nullable();
            $table->longText("Country")->nullable();
            $table->longText("Region")->nullable();
            $table->longText("City")->nullable();
            $table->longText("DigitalAddress")->nullable();
            $table->longText("DetailedAddress")->nullable();
            $table->longText("OrderStatus")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
