<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_variants', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('availability', 20)->default('paid')->after('price_cents');
            $table->string('expiry_policy', 20)->default('none')->after('duration');
            $table->unsignedInteger('access_duration_days')->nullable()->after('expiry_policy');
            $table->dateTime('fixed_expiry_at')->nullable()->after('access_duration_days');
            $table->boolean('renewable')->default(false)->after('fixed_expiry_at');
            $table->unsignedInteger('display_order')->default(0)->after('renewable');
            $table->json('eligibility')->nullable()->after('inclusions');
        });

        Schema::table('enrolments', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('enrolled_at');
            $table->timestamp('entitlement_started_at')->nullable()->after('activated_at');
            $table->timestamp('entitlement_ended_at')->nullable()->after('entitlement_started_at');
            $table->index('entitlement_ended_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('kind', 20)->default('purchase')->after('status');
            $table->unsignedBigInteger('renewal_of_order_id')->nullable()->after('kind');
            $table->foreign('renewal_of_order_id')->references('id')->on('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['renewal_of_order_id']);
            $table->dropColumn(['kind', 'renewal_of_order_id']);
        });

        Schema::table('enrolments', function (Blueprint $table) {
            $table->dropIndex(['entitlement_ended_at']);
            $table->dropColumn([
                'activated_at', 'entitlement_started_at', 'entitlement_ended_at',
            ]);
        });

        Schema::table('course_variants', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'availability', 'expiry_policy', 'access_duration_days',
                'fixed_expiry_at', 'renewable', 'display_order', 'eligibility',
            ]);
        });
    }
};
