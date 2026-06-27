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
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->timestamp('recorded_at')->index();
            $table->float('temperature')->default(0);
            $table->float('flow_rate')->default(0);
            $table->float('water_level')->default(0);
            $table->string('vibration_status', 50)->default('Tidak Bergetar');
            $table->string('status', 50)->default('Optimal')->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
