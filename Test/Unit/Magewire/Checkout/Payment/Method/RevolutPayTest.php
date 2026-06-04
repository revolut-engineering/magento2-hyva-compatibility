<?php

namespace Revolut\PaymentHyva\Test\Unit\Magewire\Checkout\Payment\Method;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Quote\Api\CartRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Rakit\Validation\Validator;
use Revolut\Payment\Api\OrderManagementInterface;
use Revolut\Payment\Model\Ui\ConfigProvider;
use Revolut\PaymentHyva\Magewire\Checkout\Payment\Method\AbstractRevolutMethod;
use Revolut\PaymentHyva\Magewire\Checkout\Payment\Method\RevolutPay;

class RevolutPayTest extends TestCase
{
    /**
     * @var RevolutPay
     */
    private $method;

    protected function setUp(): void
    {
        $this->method = new RevolutPay(
            $this->createMock(Validator::class),
            $this->createMock(SessionCheckout::class),
            $this->createMock(OrderManagementInterface::class),
            $this->createMock(CartRepositoryInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testIsAbstractRevolutMethod()
    {
        $this->assertInstanceOf(AbstractRevolutMethod::class, $this->method);
    }

    public function testGetMethodCodeReturnsRevolutPayCode()
    {
        $reflectionMethod = new \ReflectionMethod($this->method, 'getMethodCode');
        $reflectionMethod->setAccessible(true);

        $this->assertSame(ConfigProvider::REVOLUT_PAY_CODE, $reflectionMethod->invoke($this->method));
    }
}
