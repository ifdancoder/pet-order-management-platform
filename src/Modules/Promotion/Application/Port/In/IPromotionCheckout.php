<?php

declare(strict_types=1);

namespace Modules\Promotion\Application\Port\In;

use Modules\Promotion\Application\Data\PromotionQuote;
use Modules\Promotion\Application\Data\PromotionQuoteRequest;

interface IPromotionCheckout
{
    public function quote(PromotionQuoteRequest $request): PromotionQuote;
}
