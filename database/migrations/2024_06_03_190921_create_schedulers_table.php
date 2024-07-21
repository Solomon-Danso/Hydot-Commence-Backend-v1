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
        Schema::create('schedulers', function (Blueprint $table) {
            $table->id();
            $table->longText('Description')->nullable();
            $table->dateTime('StartTime');
            $table->dateTime('EndTime');
            $table->longText('StartTimezone')->nullable();
            $table->longText('EndTimezone')->nullable();
            $table->longText('Subject');
            $table->longText('Location')->nullable();
            $table->boolean('IsAllDay')->default(false);
            $table->longText('RecurrenceRule')->nullable();
            $table->bigInteger('ScheduleId')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedulers');
    }
};
