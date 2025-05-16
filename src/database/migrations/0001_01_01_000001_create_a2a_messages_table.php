<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a2a_messages', function (Blueprint $table) {
            $table->id();
            $table->string('task_id');
            $table->string('role');
            $table->json('parts'); // required, array of message parts
            $table->json('metadata')->nullable(); // optional, arbitrary key-value
            $table->unsignedInteger('index')->nullable();
            $table->timestamps();
            $table->foreign('task_id')->references('id')->on('a2a_tasks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a2a_messages');
    }
};
