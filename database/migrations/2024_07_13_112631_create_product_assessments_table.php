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
        Schema::create('product_assessments', function (Blueprint $table) {
            $table->id();
            $table->longText("ipAddress")->nullabble();
            $table->longText("country")->nullabble();
            $table->longText("city")->nullabble();
            $table->longText("device")->nullabble();
            $table->longText("os")->nullabble();
            $table->longText("urlPath")->nullabble();
            $table->longText("action")->nullabble();
            $table->longText("googlemap")->nullabble();
            $table->longText("productId")->nullabble();
            $table->longText("productName")->nullabble();
            $table->longText("productPic")->nullabble();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_assessments');
    }
};
