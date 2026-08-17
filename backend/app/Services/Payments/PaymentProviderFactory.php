<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Services\Payments\Interfaces\PaymentProviderInterface;

final readonly class PaymentProviderFactory
{
    public static function make(PaymentProvider $provider): PaymentProviderInterface
    {
        return match ($provider) {
            PaymentProvider::LAVA_TOP => app(LavaTopService::class),
            default => throw new \InvalidArgumentException("Payment provider not implemented: {$provider->name}"),
        };
    }
}
