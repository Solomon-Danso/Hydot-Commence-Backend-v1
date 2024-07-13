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
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->longText("UserId")->nullable();
            $table->longText("Username")->nullable();
            $table->longText("Picture")->nullable();
            $table->longText("Phone")->nullable();
            $table->longText("Email")->nullable();
            $table->longText("Password")->nullable();
            $table->longText("Role")->nullable();
            $table->longText("TokenId")->nullable();
            $table->datetime("TokenExpire")->nullable();
            $table->integer("LoginLimit")->default(0);
            $table->boolean("IsBlocked")->default(false);
            $table->boolean("IsSuspended")->default(false);
            $table->datetime("SuspensionExpire")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
