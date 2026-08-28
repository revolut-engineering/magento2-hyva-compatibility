<?php
declare(strict_types=1);

namespace Revolut\PaymentHyva\Magewire\Checkout\Payment\Method;

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magewirephp\Magewire\Component;
use Psr\Log\LoggerInterface;
use Rakit\Validation\Validator;
use Revolut\Payment\Api\OrderManagementInterface;
use Revolut\Payment\Observer\DataAssignObserver;

abstract class AbstractRevolutMethod extends Component\Form implements EvaluationInterface
{
    public ?string $publicId = null;
    public ?string $email = null;

    /**
     * @var array<string, mixed>
     */
    public array $billingAddress = [];

    protected SessionCheckout $sessionCheckout;
    protected OrderManagementInterface $orderManagement;
    protected CartRepositoryInterface $quoteRepository;
    protected LoggerInterface $logger;

    /**
     * @var array<string, string>
     */
    protected $listeners = [
        'billing_address_activated' => 'refresh',
        'billing_as_shipping_address_updated' => 'refresh',
        'shipping_method_selected' => 'refresh',
        'billing_method_selected' => 'refresh',
        'payment_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh',
    ];

    public function __construct(
        Validator $validator,
        SessionCheckout $sessionCheckout,
        OrderManagementInterface $orderManagement,
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($validator);
        $this->sessionCheckout = $sessionCheckout;
        $this->orderManagement = $orderManagement;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    abstract protected function getMethodCode(): string;

    public function refresh(): void
    {
        if (!$this->isSelectedMethod()) {
            return;
        }

        $this->dispatchBrowserEvent('payment:method:refresh', ['method' => $this->getMethodCode()]);
    }

    protected function isSelectedMethod(): bool
    {
        $selectedMethod = $this->getQuote()->getPayment()->getMethod();

        return empty($selectedMethod) || $selectedMethod === $this->getMethodCode();
    }

    public function getQuote(): Quote
    {
        return $this->sessionCheckout->getQuote();
    }

    public function syncQuoteData(): void
    {
        $quote = $this->getQuote();
        $bAddr = $quote->getBillingAddress();

        $this->email = $quote->getCustomerEmail() ?: ($bAddr ? $bAddr->getEmail() : '');

        if ($bAddr) {
            $this->billingAddress = [
                'countryCode' => $bAddr->getCountryId(),
                'region' => $bAddr->getRegionCode() ?: $bAddr->getRegion(),
                'city' => $bAddr->getCity(),
                'streetLine1' => $bAddr->getStreetLine(1),
                'streetLine2' => $bAddr->getStreetLine(2),
                'postcode' => $bAddr->getPostcode(),
                'firstname' => $bAddr->getFirstname(),
                'lastname' => $bAddr->getLastname(),
                'telephone' => $bAddr->getTelephone()
            ];
        }
    }

    public function initializeRevolutOrder(): void
    {
        try {
            $response = $this->orderManagement->create();

            if (!$response->getSuccess() || !$response->getPublicId()) {
                $this->logger->warning('Revolut order initialization failed: ' . $response->getMessage());
                return;
            }

            $this->publicId = $response->getPublicId();

            $quote = $this->getQuote();
            $quote->getPayment()->setAdditionalInformation(DataAssignObserver::PUBLIC_ID, $this->publicId);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->logger->error('Revolut order initialization error: ' . $e->getMessage());
        }
    }

    public function cancelRevolutOrder(string $reason): void
    {
        if (!$this->publicId) {
            return;
        }

        $this->orderManagement->cancel($this->publicId, $reason);
        $this->publicId = null;
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        // Once the frontend validate() resolves to true, Magewire Evaluation naturally succeeds.
        return $resultFactory->createSuccess();
    }
}
