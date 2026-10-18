<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrolments', function (Blueprint $table) {
            $table->string('source', 30)->default('purchased')->index()->after('order_id');
            $table->string('grant_reason', 500)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('enrolments', function (Blueprint $table) {
            $table->dropColumn(['source', 'grant_reason']);
        });
    }
};
