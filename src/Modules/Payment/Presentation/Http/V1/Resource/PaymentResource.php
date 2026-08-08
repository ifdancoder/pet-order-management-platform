<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\V1\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payment\Domain\Entity\Payment;

final class PaymentResource extends JsonResource
{
    private Payment $payment;

    public function __construct(Payment $resource)
    {
        parent::__construct($resource);

        $this->payment = $resource;
    }

    /**
     * @return array{id: string, order_id: string, amount: int, currency: string, provider: string, status: string, provider_payment_id: ?string, failure_code: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->payment->id()->value(),
            'order_id' => $this->payment->orderId()->value(),
            'amount' => $this->payment->amount()->amount(),
            'currency' => $this->payment->amount()->currency(),
            'provider' => $this->payment->provider()->value,
            'status' => $this->payment->status()->value,
            'provider_payment_id' => $this->payment->providerPaymentId(),
            'failure_code' => $this->payment->failureCode(),
        ];
    }
}
