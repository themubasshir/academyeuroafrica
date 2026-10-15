<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'order_id', 'appointment_id', 'delta', 'reason'])]
class SessionCreditLedger extends Model
{
    use HasFactory;

    protected $table = 'session_credit_ledger';

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Append-only credit balance = SUM(delta). Never mutate rows. */
    public static function balanceFor(int $userId): int
    {
        return (int) self::where('user_id', $userId)->sum('delta');
    }
}
