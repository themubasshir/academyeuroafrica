<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->boolean('is_required')->default(true)->after('requires_previous');
            $table->unsignedTinyInteger('completion_threshold')->default(90)->after('is_required');
            $table->foreignId('primary_material_id')->nullable()->after('completion_threshold')
                ->constrained('materials')->nullOnDelete();
        });

        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->unsignedTinyInteger('progress_percent')->default(0)->after('position_seconds');
            $table->timestamp('acknowledged_at')->nullable()->after('progress_percent');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('is_required')->default(true)->after('passing_score');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('is_required')->default(true)->after('max_points');
            $table->string('evaluation_type', 20)->default('numeric')->after('is_required');
            $table->unsignedInteger('passing_score')->nullable()->after('evaluation_type');
            $table->boolean('allow_resubmission')->default(true)->after('passing_score');
            $table->unsignedInteger('max_submissions')->default(2)->after('allow_resubmission');
            $table->boolean('allow_late_submissions')->default(false)->after('max_submissions');
        });

        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropUnique(['assignment_id', 'user_id']);
            $table->unsignedInteger('attempt_no')->default(1)->after('user_id');
            $table->unique(['assignment_id', 'user_id', 'attempt_no']);
        });

        Schema::table('assignment_grades', function (Blueprint $table) {
            $table->boolean('passed')->nullable()->after('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('assignment_submissions')->where('attempt_no', '>', 1)->delete();

        Schema::table('assignment_grades', function (Blueprint $table) {
            $table->dropColumn('passed');
        });

        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropUnique(['assignment_id', 'user_id', 'attempt_no']);
            $table->dropColumn('attempt_no');
            $table->unique(['assignment_id', 'user_id']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn([
                'is_required', 'evaluation_type', 'passing_score', 'allow_resubmission',
                'max_submissions', 'allow_late_submissions',
            ]);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('is_required');
        });

        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropColumn(['progress_percent', 'acknowledged_at']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_material_id');
            $table->dropColumn(['is_required', 'completion_threshold']);
        });
    }
};
