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
        Schema::create('checkers', function (Blueprint $table) {
            $table->id();
            $table->longText("MasterId")->nullable();
            $table->longText("UserId")->nullable();
            $table->longText("OrderId")->nullable();
            $table->longText("PaymentId")->nullable();
            $table->longText("BaggingId")->nullable();
            $table->longText("CheckerId")->nullable();

            $table->longText("BAdminId")->nullable();
            $table->longText("BAdminName")->nullable();
            $table->longText("BAdminPicture")->nullable();
            $table->datetime("BAdminDate")->nullable();

            $table->longText("CAdminId")->nullable();
            $table->longText("CAdminName")->nullable();
            $table->longText("CAdminPicture")->nullable();
            $table->datetime("CAdminDate")->nullable();

            $table->longText("Status")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkers');
    }
};
