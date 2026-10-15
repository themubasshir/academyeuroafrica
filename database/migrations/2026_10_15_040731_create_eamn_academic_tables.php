<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->json('delivery_types');
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('access_days')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('course_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('delivery_type', 30);
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
            $table->unique(['course_id', 'name']);
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('type', 30)->default('text');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->boolean('requires_previous')->default(false);
            $table->timestamps();
        });

        Schema::create('course_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30);
            $table->string('provider_video_id');
            $table->string('title');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->unique(['provider', 'provider_video_id']);
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploader_id')->constrained('users')->restrictOnDelete();
            $table->nullableMorphs('attachable');
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->string('visibility', 30)->default('enrolled');
            $table->timestamps();
        });

        Schema::create('enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_variant_id']);
        });

        Schema::create('trainer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['trainer_id', 'course_id']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrolment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('position_seconds')->default(0);
            $table->timestamps();
            $table->unique(['enrolment_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('trainer_assignments');
        Schema::dropIfExists('enrolments');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('course_videos');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('course_variants');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('programmes');
    }
};
