<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a2a_tasks', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('session_id')->nullable();
            $table->enum('state', ['submitted', 'working', 'input-required', 'completed', 'canceled', 'failed', 'unknown']);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a2a_tasks');
    }
};
