<?php

namespace Revolut\PaymentHyva\Test\Unit\Block\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Revolut\Payment\Model\Ui\ConfigProvider;
use Revolut\PaymentHyva\Block\Checkout\PointsBanner;

class PointsBannerTest extends TestCase
{
    /**
     * @var PointsBanner
     */
    private $pointsBanner;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configProviderMock;

    /**
     * @var CheckoutSession|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutSessionMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->configProviderMock = $this->createMock(ConfigProvider::class);
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);

        $this->pointsBanner = new PointsBanner(
            $this->contextMock,
            $this->configProviderMock,
            $this->checkoutSessionMock
        );
    }

    private function quoteWithGrandTotal(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getGrandTotal'])
            ->getMock();
    }

    public function testGetGrandTotalReturnsQuoteGrandTotalAsFloat()
    {
        $quoteMock = $this->quoteWithGrandTotal();
        $quoteMock->method('getGrandTotal')->willReturn('42.50');
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        $this->assertSame(42.50, $this->pointsBanner->getGrandTotal());
    }

    public function testGetGrandTotalReturnsZeroWhenGetQuoteThrows()
    {
        $this->checkoutSessionMock->method('getQuote')
            ->willThrowException(new \RuntimeException('no quote'));

        $this->assertSame(0.0, $this->pointsBanner->getGrandTotal());
    }

    public function testGetGrandTotalReturnsZeroWhenGetGrandTotalThrows()
    {
        $quoteMock = $this->quoteWithGrandTotal();
        $quoteMock->method('getGrandTotal')
            ->willThrowException(new \RuntimeException('boom'));
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        $this->assertSame(0.0, $this->pointsBanner->getGrandTotal());
    }
}
