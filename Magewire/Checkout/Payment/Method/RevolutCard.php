<?php
declare(strict_types=1);

namespace Revolut\PaymentHyva\Magewire\Checkout\Payment\Method;

use Revolut\Payment\Model\Ui\ConfigProvider;

class RevolutCard extends AbstractRevolutMethod
{
    public function mount(): void
    {
        $this->initializeRevolutOrder();
    }

    public function refresh(): void
    {
        if (!$this->isSelectedMethod()) {
            return;
        }

        $this->initializeRevolutOrder();
        $this->dispatchBrowserEvent('payment:method:refresh', ['method' => $this->getMethodCode()]);
    }

    protected function getMethodCode(): string
    {
        return ConfigProvider::CODE;
    }
}
