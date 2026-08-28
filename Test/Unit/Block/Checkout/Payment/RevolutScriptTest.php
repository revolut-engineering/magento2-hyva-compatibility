<?php

namespace Revolut\PaymentHyva\Test\Unit\Block\Checkout\Payment;

use Hyva\Checkout\Model\Checkout;
use Hyva\Checkout\Model\Checkout\Step;
use Hyva\Checkout\Model\Navigation\Navigator;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
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

    /**
     * @var Navigator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $navigatorMock;

    /**
     * @var CheckoutSession|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutSessionMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->configProviderMock = $this->createMock(ConfigProvider::class);
        $this->navigatorMock = $this->createMock(Navigator::class);
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);

        $this->revolutScript = new RevolutScript(
            $this->contextMock,
            $this->configProviderMock,
            $this->navigatorMock,
            $this->checkoutSessionMock
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

    public function testGetCascadingStepRoutesReturnsEncodedRouteList()
    {
        $shipping = $this->createMock(Step::class);
        $shipping->method('getRoute')->willReturn('shipping');
        $payment = $this->createMock(Step::class);
        $payment->method('getRoute')->willReturn('payment');
        $summary = $this->createMock(Step::class);
        $summary->method('getRoute')->willReturn('summary');

        $checkout = $this->createMock(Checkout::class);
        $checkout->method('getAvailableSteps')->willReturn([$shipping, $payment, $summary]);
        $this->navigatorMock->method('getActiveCheckout')->willReturn($checkout);

        $this->assertSame('["shipping","payment","summary"]', $this->revolutScript->getCascadingStepRoutes());
    }

    public function testGetCascadingStepRoutesReturnsEmptyArrayJsonWhenNavigatorThrows()
    {
        $this->navigatorMock->method('getActiveCheckout')
            ->willThrowException(new \RuntimeException('no active checkout'));

        $this->assertSame('[]', $this->revolutScript->getCascadingStepRoutes());
    }

    public function testGetGrandTotalReturnsQuoteGrandTotalAsFloat()
    {
        $quoteMock = $this->quoteWithGrandTotal();
        $quoteMock->method('getGrandTotal')->willReturn('42.50');
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        $this->assertSame(42.50, $this->revolutScript->getGrandTotal());
    }

    public function testGetGrandTotalReturnsZeroWhenGetQuoteThrows()
    {
        $this->checkoutSessionMock->method('getQuote')
            ->willThrowException(new \RuntimeException('no quote'));

        $this->assertSame(0.0, $this->revolutScript->getGrandTotal());
    }

    public function testGetGrandTotalReturnsZeroWhenGetGrandTotalThrows()
    {
        $quoteMock = $this->quoteWithGrandTotal();
        $quoteMock->method('getGrandTotal')
            ->willThrowException(new \RuntimeException('boom'));
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        $this->assertSame(0.0, $this->revolutScript->getGrandTotal());
    }

    public function testGetQuoteCurrencyCodeReturnsQuoteCurrency()
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getQuoteCurrencyCode'])
            ->getMock();
        $quoteMock->method('getQuoteCurrencyCode')->willReturn('GBP');
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        $this->assertSame('GBP', $this->revolutScript->getQuoteCurrencyCode());
    }

    public function testGetQuoteCurrencyCodeReturnsEmptyStringWhenGetQuoteThrows()
    {
        $this->checkoutSessionMock->method('getQuote')
            ->willThrowException(new \RuntimeException('no quote'));

        $this->assertSame('', $this->revolutScript->getQuoteCurrencyCode());
    }

    private function quoteWithGrandTotal(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getGrandTotal'])
            ->getMock();
    }
}
