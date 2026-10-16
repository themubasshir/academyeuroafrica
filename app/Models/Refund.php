<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_transaction_id', 'amount_cents', 'provider_refund_id', 'idempotency_key', 'provider_status', 'reason', 'status', 'completed_at', 'processed_by'])]
class Refund extends Model
{
    use HasFactory;

    /** @return BelongsTo<PaymentTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }
}
