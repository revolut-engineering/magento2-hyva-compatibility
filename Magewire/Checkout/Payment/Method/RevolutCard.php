<?php
declare(strict_types=1);

namespace Revolut\PaymentHyva\Magewire\Checkout\Payment\Method;

use Revolut\Payment\Model\Ui\ConfigProvider;

class RevolutCard extends AbstractRevolutMethod
{
    protected function getMethodCode(): string
    {
        return ConfigProvider::CODE;
    }
}
