<?php

declare(strict_types=1);

namespace Modules\Order\Application\Enum;

enum OrderTransition: string
{
    case Confirm = 'confirm';
    case StartProcessing = 'start_processing';
    case MarkShipped = 'mark_shipped';
    case Complete = 'complete';
    case Cancel = 'cancel';
    case MarkPaymentFailed = 'mark_payment_failed';
    case RetryPayment = 'retry_payment';
}
