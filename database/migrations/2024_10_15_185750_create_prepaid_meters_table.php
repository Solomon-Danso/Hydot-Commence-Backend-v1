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
        Schema::create('prepaid_meters', function (Blueprint $table) {
            $table->id();
            $table->longText('Token')->nullable();
            $table->longText('productId')->nullable();
            $table->longText('packageType')->nullable();
            $table->decimal('Amount')->nullable();
            $table->longText('apiHost')->nullable();
            $table->longText('apiKey')->nullable();
            $table->longText('apiSecret')->nullable();
            $table->longText('softwareID')->nullable();
            $table->longText('companyId')->nullable();
            $table->longText('email')->nullable();
            $table->Date('ExpireDate')->nullable();
            $table->longText('companyName')->nullable();
            $table->longText('companyPhone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prepaid_meters');
    }
};
