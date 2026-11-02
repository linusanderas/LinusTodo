<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index('position');
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('kind');
            $table->timestamp('occurs_at')->nullable();
            $table->unsignedInteger('position');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
        Schema::dropIfExists('projects');
    }
};
