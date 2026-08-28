<?php
declare(strict_types=1);

namespace Revolut\PaymentHyva\Block\Checkout\Payment;

use Hyva\Checkout\Model\Navigation\Navigator;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Revolut\Payment\Model\Ui\ConfigProvider;

class RevolutScript extends Template
{
    private ConfigProvider $configProvider;
    private Navigator $navigator;
    private CheckoutSession $checkoutSession;

    /**
     * @param Template\Context $context
     * @param ConfigProvider $configProvider
     * @param Navigator $navigator
     * @param CheckoutSession $checkoutSession
     * @param array<string,mixed> $data
     */
    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        Navigator $navigator,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->navigator = $navigator;
        $this->checkoutSession = $checkoutSession;
    }

    public function getRevolutConfig(): string
    {
        $config = $this->configProvider->getConfig();
        return (string) json_encode($config['payment']['revolut'] ?? []);
    }

    public function isCardholderNameFieldEnabled(): bool
    {
        $config = $this->configProvider->getConfig();
        return (bool) ($config['payment']['revolut']['cardholderNameField'] ?? false);
    }

    /**
     * Quote grand total used by the informational/promotional banners (points, Revolut Pay title).
     *
     * @return float
     */
    public function getGrandTotal(): float
    {
        try {
            return (float) $this->checkoutSession->getQuote()->getGrandTotal();
        } catch (\Throwable) {
            return 0.0;
        }
    }

    /**
     * Quote currency used by wallet capability checks before their method content is rendered.
     *
     * @return string
     */
    public function getQuoteCurrencyCode(): string
    {
        try {
            return (string) $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Routes of every step in the active checkout, as a JSON array.
     *
     * The order is placed from the payment step for all Revolut methods (the card iframe must stay
     * live, the wallets self-drive). In multi-step layouts a later step (e.g. the mobile "summary")
     * is never reached, yet Main::validateCascadingStepData() requires every available step route to
     * be marked complete client-side. Exposing the routes lets the scripts seed that map before
     * placing the order. Empty outside a checkout context.
     *
     * @return string
     */
    public function getCascadingStepRoutes(): string
    {
        $routes = [];

        try {
            foreach ($this->navigator->getActiveCheckout()->getAvailableSteps() as $step) {
                $routes[] = $step->getRoute();
            }
        } catch (\Throwable) {
            $routes = [];
        }

        return (string) json_encode($routes);
    }
}
