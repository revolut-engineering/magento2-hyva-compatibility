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
use Revolut\PaymentHyva\Magewire\Checkout\Payment\Method\RevolutPaymentRequest;

class RevolutPaymentRequestTest extends TestCase
{
    /**
     * @var RevolutPaymentRequest
     */
    private $method;

    protected function setUp(): void
    {
        $this->method = new RevolutPaymentRequest(
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

    public function testGetMethodCodeReturnsPaymentRequestCode()
    {
        $reflectionMethod = new \ReflectionMethod($this->method, 'getMethodCode');
        $reflectionMethod->setAccessible(true);

        $this->assertSame(
            ConfigProvider::REVOLUT_PAYMENT_REQUEST_CODE,
            $reflectionMethod->invoke($this->method)
        );
    }
}
