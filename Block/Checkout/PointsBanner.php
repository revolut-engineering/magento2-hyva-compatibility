<?php
declare(strict_types=1);

namespace Revolut\PaymentHyva\Block\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template\Context;
use Revolut\Payment\Model\Ui\ConfigProvider;
use Revolut\PaymentHyva\Block\Checkout\Payment\RevolutScript;

class PointsBanner extends RevolutScript
{
    private CheckoutSession $checkoutSession;

    /**
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param CheckoutSession $checkoutSession
     * @param array<string,mixed> $data
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $configProvider, $data);
        $this->checkoutSession = $checkoutSession;
    }

    public function getGrandTotal(): float
    {
        try {
            return (float) $this->checkoutSession->getQuote()->getGrandTotal();
        } catch (\Throwable $e) {
            return 0.0;
        }
    }
}
