<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add display description and audience targeting (whole course | group |
     * individual student) to learning materials.
     */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('description')->nullable()->after('original_name');
            $table->foreignId('group_id')->nullable()->after('visibility')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->after('group_id')->constrained('users')->cascadeOnDelete();
            $table->index(['group_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropIndex(['group_id', 'student_id']);
            $table->dropConstrainedForeignId('student_id');
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn('description');
        });
    }
};
