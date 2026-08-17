<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use App\Enums\PaymentProvider;

/**
 * Class PaymentTransaction
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $plan_id
 * @property float|null $amount
 * @property string|null $currency
 * @property PaymentProvider $provider_id
 * @property string|null $transaction_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 */
final class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'amount',
        'currency',
        'provider_id',
        'transaction_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'provider_id' => PaymentProvider::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
