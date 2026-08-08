<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enum;

enum PaymentProvider: string
{
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case Fake = 'fake';
}
