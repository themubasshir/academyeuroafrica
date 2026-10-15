<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->dateTimeTz('due_at')->nullable();
            $table->unsignedInteger('max_points')->default(100);
            $table->string('audience', 20)->default('course'); // course|group|students
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('grades_released')->default(false);
            $table->timestamps();
        });

        Schema::create('assignment_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['assignment_id', 'user_id']);
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 20)->default('draft'); // draft|submitted|returned|graded
            $table->timestamps();
            $table->unique(['assignment_id', 'user_id']);
        });

        Schema::create('assignment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_submission_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });

        Schema::create('assignment_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grader_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('score');
            $table->text('feedback')->nullable();
            $table->string('status', 20)->default('draft'); // draft|released|returned
            $table->timestamps();
            $table->unique('assignment_submission_id');
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedInteger('max_attempts')->default(1);
            $table->unsignedInteger('passing_score')->nullable();
            $table->boolean('randomise_questions')->default(false);
            $table->boolean('randomise_options')->default(false);
            $table->string('release_results', 30)->default('after_close'); // immediate|after_close|manual
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // mcq|multi|tf|short|essay
            $table->text('prompt');
            $table->unsignedInteger('points')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_no');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress|submitted|graded
            $table->json('question_order')->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'user_id', 'attempt_no']);
            $table->index(['quiz_id', 'user_id']);
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->json('chosen_option_ids')->nullable();
            $table->text('text_answer')->nullable();
            $table->unsignedInteger('awarded_points')->nullable();
            $table->timestamps();
            $table->unique(['quiz_attempt_id', 'quiz_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('assignment_grades');
        Schema::dropIfExists('assignment_attachments');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignment_recipients');
        Schema::dropIfExists('assignments');
    }
};
