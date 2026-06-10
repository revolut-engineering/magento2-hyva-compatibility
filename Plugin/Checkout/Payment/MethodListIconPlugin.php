<?php
declare(strict_types=1);

namespace Revolut\PaymentHyva\Plugin\Checkout\Payment;

use Hyva\Checkout\Model\AbstractMethodMetaData;
use Hyva\Checkout\Model\MethodMetaDataInterface;
use Hyva\Checkout\ViewModel\Checkout\Payment\MethodList;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\MethodInterface;
use Psr\Log\LoggerInterface;
use Revolut\Payment\Model\Ui\ConfigProvider;
use Revolut\PaymentHyva\Model\MethodMetaData\RevolutMethodMetaDataFactory;

/**
 * Adds the brand-logo strip to the Revolut payment methods in the Hyvä
 * checkout payment list, mirroring the standard (Luma) checkout label icons.
 */
class MethodListIconPlugin
{
    private const ICON_ATTRIBUTES = ['height' => '24', 'class' => 'inline-block ml-1 h-6 w-auto align-middle'];

    private const ICON_MASTERCARD = 'Revolut_Payment::images/mastercard.svg';
    private const ICON_VISA = 'Revolut_Payment::images/visa.svg';
    private const ICON_AMEX = 'Revolut_Payment::images/amex.svg';
    private const ICON_REVOLUT = 'Revolut_Payment::images/revolut.svg';
    private const ICON_APPLE_PAY = 'Revolut_Payment::images/apple-pay-logo.svg';
    private const ICON_GOOGLE_PAY = 'Revolut_Payment::images/g-pay-logo.png';

    private const REVOLUT_METHOD_CODES = [
        ConfigProvider::CODE,
        ConfigProvider::REVOLUT_PAY_CODE,
        ConfigProvider::REVOLUT_PAYMENT_REQUEST_CODE,
    ];

    private RevolutMethodMetaDataFactory $metaDataFactory;
    private ConfigProvider $configProvider;
    private LoggerInterface $logger;

    /**
     * @var array<string, array<int, array<string, mixed>>>|null
     */
    private ?array $iconMap = null;

    public function __construct(
        RevolutMethodMetaDataFactory $metaDataFactory,
        ConfigProvider $configProvider,
        LoggerInterface $logger
    ) {
        $this->metaDataFactory = $metaDataFactory;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
    }

    /**
     * Replace the metadata of Revolut methods with one that renders the brand strip.
     *
     * @param MethodList $subject
     * @param AbstractMethodMetaData $result
     * @param Template $parent
     * @param MethodInterface $method
     * @return AbstractMethodMetaData
     */
    public function afterGetMethodMetaData(
        MethodList $subject,
        AbstractMethodMetaData $result,
        Template $parent,
        MethodInterface $method
    ): AbstractMethodMetaData {
        $code = (string) $method->getCode();

        if (!in_array($code, self::REVOLUT_METHOD_CODES, true)) {
            return $result;
        }

        $icons = $this->getIconMap()[$code] ?? [];

        if ($icons === []) {
            return $result;
        }

        return $this->metaDataFactory->create([
            'method' => $method,
            'data' => array_merge((array) $result->getData(), [MethodMetaDataInterface::ICON => $icons]),
        ]);
    }

    /**
     * Build (once per request) the icon list per Revolut method code.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getIconMap(): array
    {
        if ($this->iconMap !== null) {
            return $this->iconMap;
        }

        $cardIcons = [
            $this->icon(self::ICON_MASTERCARD),
            $this->icon(self::ICON_VISA),
        ];

        if ($this->isAmexAvailable()) {
            $cardIcons[] = $this->icon(self::ICON_AMEX);
        }

        $this->iconMap = [
            ConfigProvider::CODE => $cardIcons,
            ConfigProvider::REVOLUT_PAY_CODE => array_merge([$this->icon(self::ICON_REVOLUT)], $cardIcons),
            ConfigProvider::REVOLUT_PAYMENT_REQUEST_CODE => [
                $this->icon(self::ICON_APPLE_PAY),
                $this->icon(self::ICON_GOOGLE_PAY),
            ],
        ];

        return $this->iconMap;
    }

    /**
     * @param string $src
     * @return array<string, mixed>
     */
    private function icon(string $src): array
    {
        return ['src' => $src, 'attributes' => self::ICON_ATTRIBUTES];
    }

    /**
     * Mirror the standard checkout: only show the Amex logo when Amex is an available brand.
     *
     * @return bool
     */
    private function isAmexAvailable(): bool
    {
        try {
            $config = $this->configProvider->getConfig();
            $brands = $config['payment'][ConfigProvider::GATEWAY_CODE]['availableCardBrands'] ?? [];

            return is_array($brands) && in_array('amex', $brands, true);
        } catch (\Throwable $e) {
            $this->logger->debug('MethodListIconPlugin::isAmexAvailable - ' . $e->getMessage());

            return false;
        }
    }
}
