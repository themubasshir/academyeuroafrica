<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('status', 20)->default('active')->index()->after('password');
            $table->string('timezone', 64)->nullable()->after('status');
            $table->timestamp('disabled_at')->nullable()->after('timezone');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('label', 100);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('label', 150);
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('external_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('subject', 191);
            $table->string('email', 191)->nullable();
            $table->json('claims')->nullable();
            $table->timestamp('linked_at')->useCurrent();
            $table->timestamps();
            $table->unique(['provider', 'subject']);
            $table->index('email');
        });

        Schema::create('identity_conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('subject', 191);
            $table->string('email', 191);
            $table->foreignId('existing_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('resolution', 30)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bridge_auth_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code_hash', 64)->unique();
            $table->string('state_hash', 64);
            $table->string('ip', 45)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index('expires_at');
        });

        Schema::create('joomla_page_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('path', 191)->unique();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->unsignedBigInteger('course_variant_id')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('joomla_page_mappings');
        Schema::dropIfExists('bridge_auth_codes');
        Schema::dropIfExists('identity_conflicts');
        Schema::dropIfExists('external_identities');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'timezone', 'disabled_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
