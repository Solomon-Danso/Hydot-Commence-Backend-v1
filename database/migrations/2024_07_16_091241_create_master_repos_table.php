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
        Schema::create('master_repos', function (Blueprint $table) {
            $table->id();
            $table->longText("MasterId")->nullable();
            $table->longText("UserId")->nullable();
            $table->longText("OrderId")->nullable();
            $table->longText("PaymentId")->nullable();
            $table->longText("BaggingId")->nullable();
            $table->longText("CheckerId")->nullable();
            $table->longText("DeliveryId")->nullable();




            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_repos');
    }
};
