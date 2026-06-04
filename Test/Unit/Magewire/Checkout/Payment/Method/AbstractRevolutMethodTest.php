<?php

namespace Revolut\PaymentHyva\Test\Unit\Magewire\Checkout\Payment\Method;

use Hyva\Checkout\Model\Magewire\Component\Evaluation\Success;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Payment;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Rakit\Validation\Validator;
use Revolut\Payment\Api\Data\OrderManagementResponseDataInterface;
use Revolut\Payment\Api\OrderManagementInterface;
use Revolut\Payment\Model\Ui\ConfigProvider;
use Revolut\Payment\Observer\DataAssignObserver;
use Revolut\PaymentHyva\Magewire\Checkout\Payment\Method\RevolutCard;

class AbstractRevolutMethodTest extends TestCase
{
    /**
     * @var RevolutCard
     */
    private $method;

    /**
     * @var Validator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validatorMock;

    /**
     * @var SessionCheckout|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sessionCheckoutMock;

    /**
     * @var OrderManagementInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderManagementMock;

    /**
     * @var CartRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->validatorMock = $this->createMock(Validator::class);
        $this->sessionCheckoutMock = $this->createMock(SessionCheckout::class);
        $this->orderManagementMock = $this->createMock(OrderManagementInterface::class);
        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->method = new RevolutCard(
            $this->validatorMock,
            $this->sessionCheckoutMock,
            $this->orderManagementMock,
            $this->quoteRepositoryMock,
            $this->loggerMock
        );
    }

    public function testGetQuoteReturnsCheckoutSessionQuote()
    {
        $quoteMock = $this->createMock(Quote::class);
        $this->sessionCheckoutMock->method('getQuote')->willReturn($quoteMock);

        $this->assertSame($quoteMock, $this->method->getQuote());
    }

    public function testSyncQuoteDataUsesCustomerEmailAndPopulatesBillingAddress()
    {
        $billingAddressMock = $this->createMock(Address::class);
        $billingAddressMock->method('getCountryId')->willReturn('GB');
        $billingAddressMock->method('getRegionCode')->willReturn('LND');
        $billingAddressMock->method('getCity')->willReturn('London');
        $billingAddressMock->method('getStreetLine')->willReturnMap([
            [1, 'Street One'],
            [2, 'Street Two'],
        ]);
        $billingAddressMock->method('getPostcode')->willReturn('EC1A 1BB');
        $billingAddressMock->method('getFirstname')->willReturn('John');
        $billingAddressMock->method('getLastname')->willReturn('Doe');
        $billingAddressMock->method('getTelephone')->willReturn('+441234567890');
        $billingAddressMock->expects($this->never())->method('getEmail');

        $quoteMock = $this->createQuoteMock();
        $quoteMock->method('getCustomerEmail')->willReturn('customer@example.com');
        $quoteMock->method('getBillingAddress')->willReturn($billingAddressMock);
        $this->sessionCheckoutMock->method('getQuote')->willReturn($quoteMock);

        $this->method->syncQuoteData();

        $this->assertSame('customer@example.com', $this->method->email);
        $this->assertSame([
            'countryCode' => 'GB',
            'region' => 'LND',
            'city' => 'London',
            'streetLine1' => 'Street One',
            'streetLine2' => 'Street Two',
            'postcode' => 'EC1A 1BB',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'telephone' => '+441234567890',
        ], $this->method->billingAddress);
    }

    public function testSyncQuoteDataFallsBackToBillingAddressEmail()
    {
        $billingAddressMock = $this->createMock(Address::class);
        $billingAddressMock->method('getEmail')->willReturn('billing@example.com');
        $billingAddressMock->method('getRegionCode')->willReturn(null);
        $billingAddressMock->method('getRegion')->willReturn('Greater London');

        $quoteMock = $this->createQuoteMock();
        $quoteMock->method('getCustomerEmail')->willReturn(null);
        $quoteMock->method('getBillingAddress')->willReturn($billingAddressMock);
        $this->sessionCheckoutMock->method('getQuote')->willReturn($quoteMock);

        $this->method->syncQuoteData();

        $this->assertSame('billing@example.com', $this->method->email);
        $this->assertSame('Greater London', $this->method->billingAddress['region']);
    }

    public function testSyncQuoteDataLeavesBillingAddressUntouchedWhenNoBillingAddress()
    {
        $quoteMock = $this->createQuoteMock();
        $quoteMock->method('getCustomerEmail')->willReturn(null);
        $quoteMock->method('getBillingAddress')->willReturn(null);
        $this->sessionCheckoutMock->method('getQuote')->willReturn($quoteMock);

        $this->method->syncQuoteData();

        $this->assertSame('', $this->method->email);
        $this->assertSame([], $this->method->billingAddress);
    }

    public function testInitializeRevolutOrderSuccess()
    {
        $publicId = 'pub_test_123';

        $responseMock = $this->createMock(OrderManagementResponseDataInterface::class);
        $responseMock->method('getSuccess')->willReturn(true);
        $responseMock->method('getPublicId')->willReturn($publicId);
        $this->orderManagementMock->expects($this->once())->method('create')->willReturn($responseMock);

        $paymentMock = $this->createMock(Payment::class);
        $paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(DataAssignObserver::PUBLIC_ID, $publicId);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getPayment')->willReturn($paymentMock);
        $this->sessionCheckoutMock->method('getQuote')->willReturn($quoteMock);

        $this->quoteRepositoryMock->expects($this->once())->method('save')->with($quoteMock);

        $this->method->initializeRevolutOrder();

        $this->assertSame($publicId, $this->method->publicId);
    }

    public function testInitializeRevolutOrderDoesNothingWhenNotSuccessful()
    {
        $responseMock = $this->createMock(OrderManagementResponseDataInterface::class);
        $responseMock->method('getSuccess')->willReturn(false);
        $responseMock->method('getMessage')->willReturn('declined');
        $this->orderManagementMock->method('create')->willReturn($responseMock);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('declined'));
        $this->quoteRepositoryMock->expects($this->never())->method('save');

        $this->method->initializeRevolutOrder();

        $this->assertNull($this->method->publicId);
    }

    public function testInitializeRevolutOrderDoesNothingWhenPublicIdEmpty()
    {
        $responseMock = $this->createMock(OrderManagementResponseDataInterface::class);
        $responseMock->method('getSuccess')->willReturn(true);
        $responseMock->method('getPublicId')->willReturn('');
        $responseMock->method('getMessage')->willReturn('');
        $this->orderManagementMock->method('create')->willReturn($responseMock);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Revolut order initialization failed'));
        $this->quoteRepositoryMock->expects($this->never())->method('save');

        $this->method->initializeRevolutOrder();

        $this->assertNull($this->method->publicId);
    }

    public function testInitializeRevolutOrderSwallowsException()
    {
        $this->orderManagementMock->method('create')
            ->willThrowException(new \Exception('network down'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('network down'));
        $this->quoteRepositoryMock->expects($this->never())->method('save');

        $this->method->initializeRevolutOrder();

        $this->assertNull($this->method->publicId);
    }

    public function testMountDelegatesToInitializeRevolutOrder()
    {
        $method = $this->getMockBuilder(RevolutCard::class)
            ->setConstructorArgs([
                $this->validatorMock,
                $this->sessionCheckoutMock,
                $this->orderManagementMock,
                $this->quoteRepositoryMock,
                $this->loggerMock,
            ])
            ->onlyMethods(['initializeRevolutOrder'])
            ->getMock();

        $method->expects($this->once())->method('initializeRevolutOrder');

        $method->mount();
    }

    public function testRefreshInitializesOrderAndDispatchesEventWhenNoMethodSelected()
    {
        $method = $this->buildRefreshableMethod('');

        $method->expects($this->once())->method('initializeRevolutOrder');
        $method->expects($this->once())
            ->method('dispatchBrowserEvent')
            ->with('payment:method:refresh');

        $method->refresh();
    }

    public function testRefreshInitializesOrderAndDispatchesEventWhenThisMethodSelected()
    {
        $method = $this->buildRefreshableMethod(ConfigProvider::CODE);

        $method->expects($this->once())->method('initializeRevolutOrder');
        $method->expects($this->once())
            ->method('dispatchBrowserEvent')
            ->with('payment:method:refresh');

        $method->refresh();
    }

    public function testRefreshDoesNothingWhenAnotherMethodSelected()
    {
        $method = $this->buildRefreshableMethod('paypal');

        $method->expects($this->never())->method('initializeRevolutOrder');
        $method->expects($this->never())->method('dispatchBrowserEvent');

        $method->refresh();
    }

    public function testEvaluateCompletionReturnsFactorySuccessResult()
    {
        $successMock = $this->createMock(Success::class);

        $resultFactoryMock = $this->createMock(EvaluationResultFactory::class);
        $resultFactoryMock->expects($this->once())
            ->method('createSuccess')
            ->willReturn($successMock);

        $this->assertSame($successMock, $this->method->evaluateCompletion($resultFactoryMock));
    }

    /**
     * @param string $selectedMethod
     * @return RevolutCard|\PHPUnit\Framework\MockObject\MockObject
     */
    private function buildRefreshableMethod($selectedMethod)
    {
        $paymentMock = $this->createMock(Payment::class);
        $paymentMock->method('getMethod')->willReturn($selectedMethod);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getPayment')->willReturn($paymentMock);
        $this->sessionCheckoutMock->method('getQuote')->willReturn($quoteMock);

        return $this->getMockBuilder(RevolutCard::class)
            ->setConstructorArgs([
                $this->validatorMock,
                $this->sessionCheckoutMock,
                $this->orderManagementMock,
                $this->quoteRepositoryMock,
                $this->loggerMock,
            ])
            ->onlyMethods(['initializeRevolutOrder', 'dispatchBrowserEvent'])
            ->getMock();
    }

    /**
     * @return Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress'])
            ->addMethods(['getCustomerEmail'])
            ->getMock();
    }
}
