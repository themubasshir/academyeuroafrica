<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'reference', 'status', 'total_cents', 'currency'])]
class Order extends Model
{
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<PaymentTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function refundedCents(): int
    {
        return (int) $this->transactions()
            ->with('refunds')
            ->get()
            ->flatMap(fn (PaymentTransaction $transaction) => $transaction->refunds)
            ->where('status', 'completed')
            ->sum('amount_cents');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
