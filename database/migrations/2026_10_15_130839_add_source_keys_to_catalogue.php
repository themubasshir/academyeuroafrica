<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Stable per-import identifiers so re-running the importer is a no-op. */
    public function up(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->string('source_key', 120)->nullable()->after('sort_order')->index();
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('source_key', 120)->nullable()->after('sort_order')->index();
        });
        Schema::table('materials', function (Blueprint $table) {
            $table->string('source_key', 120)->nullable()->after('original_name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('course_modules', fn (Blueprint $t) => $t->dropColumn('source_key'));
        Schema::table('lessons', fn (Blueprint $t) => $t->dropColumn('source_key'));
        Schema::table('materials', fn (Blueprint $t) => $t->dropColumn('source_key'));
    }
};
