<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_history', function (Blueprint $table) {
            $table->string('gateway', 50)->default('duitku')->after('plan_id');
            $table->string('merchant_ref', 100)->unique()->after('gateway'); // idempotency key
            $table->string('billing_period', 20)->default('monthly')->after('amount'); // monthly|yearly
            $table->string('payment_channel', 50)->nullable()->after('payment_method'); // QRIS|... diisi callback
            $table->string('checkout_url', 1024)->nullable()->after('payment_reference');
            $table->timestamp('expired_at')->nullable()->after('payment_date');
            $table->json('raw_payload')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('payment_history', function (Blueprint $table) {
            $table->dropUnique(['merchant_ref']);
            $table->dropColumn([
                'gateway', 'merchant_ref', 'billing_period',
                'payment_channel', 'checkout_url', 'expired_at', 'raw_payload',
            ]);
        });
    }
};
