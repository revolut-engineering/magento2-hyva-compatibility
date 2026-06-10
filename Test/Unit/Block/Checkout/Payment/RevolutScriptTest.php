<?php

namespace Revolut\PaymentHyva\Test\Unit\Block\Checkout\Payment;

use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;
use Revolut\Payment\Model\Ui\ConfigProvider;
use Revolut\PaymentHyva\Block\Checkout\Payment\RevolutScript;

class RevolutScriptTest extends TestCase
{
    /**
     * @var RevolutScript
     */
    private $revolutScript;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configProviderMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->configProviderMock = $this->createMock(ConfigProvider::class);

        $this->revolutScript = new RevolutScript(
            $this->contextMock,
            $this->configProviderMock
        );
    }

    public function testGetRevolutConfigReturnsEncodedRevolutSubarray()
    {
        $revolutConfig = [
            'merchant_public_key' => 'pk_test',
            'mode' => 'sandbox',
            'locale' => 'en',
        ];

        $this->configProviderMock->method('getConfig')->willReturn([
            'payment' => [
                'revolut' => $revolutConfig,
            ],
        ]);

        $this->assertSame(json_encode($revolutConfig), $this->revolutScript->getRevolutConfig());
    }

    public function testGetRevolutConfigReturnsEmptyArrayJsonWhenKeysMissing()
    {
        $this->configProviderMock->method('getConfig')->willReturn([]);

        $this->assertSame('[]', $this->revolutScript->getRevolutConfig());
    }

    public function testIsCardholderNameFieldEnabledReturnsTrueWhenFlagTruthy()
    {
        $this->configProviderMock->method('getConfig')->willReturn([
            'payment' => [
                'revolut' => [
                    'cardholderNameField' => true,
                ],
            ],
        ]);

        $this->assertTrue($this->revolutScript->isCardholderNameFieldEnabled());
    }

    public function testIsCardholderNameFieldEnabledReturnsFalseWhenFlagFalsy()
    {
        $this->configProviderMock->method('getConfig')->willReturn([
            'payment' => [
                'revolut' => [
                    'cardholderNameField' => 0,
                ],
            ],
        ]);

        $this->assertFalse($this->revolutScript->isCardholderNameFieldEnabled());
    }

    public function testIsCardholderNameFieldEnabledReturnsFalseWhenKeyMissing()
    {
        $this->configProviderMock->method('getConfig')->willReturn([]);

        $this->assertFalse($this->revolutScript->isCardholderNameFieldEnabled());
    }
}
