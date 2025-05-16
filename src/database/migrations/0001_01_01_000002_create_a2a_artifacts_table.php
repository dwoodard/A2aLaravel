<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a2a_artifacts', function (Blueprint $table) {
            $table->id();
            $table->string('task_id');
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->json('parts'); // required, array of artifact parts
            $table->boolean('append')->nullable();
            $table->boolean('lastChunk')->nullable();
            $table->unsignedInteger('index')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('task_id')->references('id')->on('a2a_tasks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a2a_artifacts');
    }
};
