<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 30)->unique();
            $table->boolean('enabled')->default(false);
            $table->string('environment', 20)->default('sandbox');
            $table->text('sandbox_client_id')->nullable();
            $table->text('sandbox_client_secret')->nullable();
            $table->text('sandbox_webhook_id')->nullable();
            $table->text('live_client_id')->nullable();
            $table->text('live_client_secret')->nullable();
            $table->text('live_webhook_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
