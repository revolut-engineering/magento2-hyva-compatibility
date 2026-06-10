<?php
declare(strict_types=1);

namespace Revolut\PaymentHyva\Model\MethodMetaData;

use Hyva\Checkout\Model\ConfigData\HyvaThemes\SystemConfigPayment;
use Hyva\Checkout\Model\MethodMetaData\IconRenderer;
use Hyva\Checkout\Model\MethodMetaData\SubtitleRenderer;
use Hyva\Checkout\Model\PaymentMethodMetaData;
use Magento\Payment\Model\MethodInterface;

/**
 * Renders a strip of brand logos in the Hyvä checkout payment method label,
 * matching the standard (Luma) checkout.
 *
 * The native single-icon slot only renders one logo. When the "icon" data key
 * holds a list of logo definitions (rather than a single {src|svg} entry), this
 * renders all of them. Storing the list under "icon" keeps the inherited
 * canRenderIcon() working, so the "Display Payment Method Icons" admin toggle is
 * still respected.
 */
class RevolutMethodMetaData extends PaymentMethodMetaData
{
    private IconRenderer $iconRenderer;

    /**
     * @param IconRenderer $iconRenderer
     * @param SubtitleRenderer $subtitleRenderer
     * @param MethodInterface $method
     * @param SystemConfigPayment $systemConfigPayment
     * @param array<string,mixed> $data
     */
    public function __construct(
        IconRenderer $iconRenderer,
        SubtitleRenderer $subtitleRenderer,
        MethodInterface $method,
        SystemConfigPayment $systemConfigPayment,
        array $data = []
    ) {
        parent::__construct($iconRenderer, $subtitleRenderer, $method, $systemConfigPayment, $data);
        $this->iconRenderer = $iconRenderer;
    }

    /**
     * Render every logo when the icon holds a list; otherwise keep the default behaviour.
     *
     * @return string
     */
    public function renderIcon(): string
    {
        $icon = $this->getData(self::ICON);

        if (!$this->isIconList($icon)) {
            return parent::renderIcon();
        }

        $html = '';
        foreach ((array) $icon as $entry) {
            $html .= $this->iconRenderer->render(is_array($entry) ? $entry : ['src' => $entry]);
        }

        return $html;
    }

    /**
     * A single icon is an associative array ({src}/{svg}) or a string; anything else
     * holding multiple entries is treated as a list of logos.
     *
     * @param mixed $icon
     * @return bool
     */
    private function isIconList($icon): bool
    {
        return is_array($icon)
            && $icon !== []
            && !isset($icon['src'])
            && !isset($icon['svg']);
    }
}
