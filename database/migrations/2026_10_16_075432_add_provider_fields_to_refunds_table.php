<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table): void {
            $table->string('provider_refund_id')->nullable()->after('amount_cents');
            $table->string('idempotency_key', 191)->unique()->after('provider_refund_id');
            $table->string('provider_status', 30)->nullable()->after('idempotency_key');
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->unique(['payment_transaction_id', 'provider_refund_id']);
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropUnique(['payment_transaction_id', 'provider_refund_id']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['provider_refund_id', 'idempotency_key', 'provider_status', 'completed_at']);
        });
    }
};
