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
        Schema::create('baggings', function (Blueprint $table) {
            $table->id();
            $table->longText("MasterId")->nullable();
            $table->longText("UserId")->nullable();
            $table->longText("OrderId")->nullable();
            $table->longText("PaymentId")->nullable();
            $table->longText("BaggingId")->nullable();
            $table->longText("BAdminId")->nullable();
            $table->longText("BAdminName")->nullable();
            $table->longText("BAdminPicture")->nullable();
            $table->datetime("BAdminDate")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baggings');
    }
};
