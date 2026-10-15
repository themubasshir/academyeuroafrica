<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Commercial-package metadata: where each price/claim came from in the
     * client source archive, and the admin approval state. Buying stays off
     * until approval_status moves to 'approved' AND status to 'published'.
     */
    public function up(): void
    {
        Schema::table('course_variants', function (Blueprint $table) {
            // Nullable so a pending-approval variant can exist with no set price.
            $table->unsignedInteger('price_cents')->nullable()->change();
            $table->string('source_key', 120)->nullable()->after('status')->index();
            $table->string('approval_status', 20)->default('pending')->after('source_key')->index();
            $table->string('duration', 120)->nullable()->after('approval_status');
            $table->json('inclusions')->nullable()->after('duration');
            $table->string('price_source', 200)->nullable()->after('inclusions');
            $table->string('checkout_ref', 80)->nullable()->after('price_source')->index();
        });
    }

    public function down(): void
    {
        Schema::table('course_variants', function (Blueprint $table) {
            $table->dropColumn([
                'source_key', 'approval_status', 'duration',
                'inclusions', 'price_source', 'checkout_ref',
            ]);
        });
    }
};
