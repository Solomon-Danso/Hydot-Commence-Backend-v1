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
        Schema::create('reply_chats', function (Blueprint $table) {
            $table->id();
            $table->longText("ReplyId")->nullable();
            $table->longText("Email")->nullable();
            $table->longText("CustomerName")->nullable();
            $table->longText("CustomerMessage")->nullable();
            $table->longText("Reply")->nullable();
            $table->longText("Attachment")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reply_chats');
    }
};
