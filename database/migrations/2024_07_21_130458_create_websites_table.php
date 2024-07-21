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
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->longText("CompanyName")->nullable();
            $table->longText("Image1")->nullable();
            $table->longText("Image2")->nullable();
            $table->longText("Image3")->nullable();
            $table->longText("Video")->nullable();

            $table->longText("Whatsapp")->nullable();
            $table->longText("Instagram")->nullable();
            $table->longText("Facebook")->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
