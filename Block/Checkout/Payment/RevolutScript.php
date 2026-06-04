<?php
declare(strict_types=1);

namespace Revolut\PaymentHyva\Block\Checkout\Payment;

use Magento\Framework\View\Element\Template;
use Revolut\Payment\Model\Ui\ConfigProvider;

class RevolutScript extends Template
{
    private ConfigProvider $configProvider;

    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
    }

    public function getRevolutConfig(): string
    {
        $config = $this->configProvider->getConfig();
        return json_encode($config['payment']['revolut'] ?? []);
    }
}
