<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Enums\PaymentProvider;

/**
 * Class Plan
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property float $price
 * @property int $duration_days
 * @property string|null $offer_id
 * @property PaymentProvider $provider_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'offer_id',
        'provider_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
            'provider_id' => PaymentProvider::class,
        ];
    }
}
