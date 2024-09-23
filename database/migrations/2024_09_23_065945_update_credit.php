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
        Schema::table('credit_sales', function (Blueprint $table) {
            $table->longText("UserPic")->nullable();
            $table->longText("IDFront")->nullable();
            $table->longText("IDBack")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_sales', function (Blueprint $table) {
            //
        });
    }
};
