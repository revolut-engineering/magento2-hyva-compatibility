<?php

namespace Revolut\PaymentHyva\Test\Unit\Plugin\Checkout\Payment;

use Hyva\Checkout\Model\AbstractMethodMetaData;
use Hyva\Checkout\ViewModel\Checkout\Payment\MethodList;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\MethodInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Revolut\Payment\Model\Ui\ConfigProvider;
use Revolut\PaymentHyva\Model\MethodMetaData\RevolutMethodMetaData;
use Revolut\PaymentHyva\Model\MethodMetaData\RevolutMethodMetaDataFactory;
use Revolut\PaymentHyva\Plugin\Checkout\Payment\MethodListIconPlugin;

class MethodListIconPluginTest extends TestCase
{
    private const ICON_ATTRIBUTES = ['height' => '24', 'class' => 'inline-block ml-1 h-6 w-auto align-middle'];

    private const SRC_MASTERCARD = 'Revolut_Payment::images/mastercard.svg';
    private const SRC_VISA = 'Revolut_Payment::images/visa.svg';
    private const SRC_AMEX = 'Revolut_Payment::images/amex.svg';
    private const SRC_REVOLUT = 'Revolut_Payment::images/revolut.svg';
    private const SRC_APPLE_PAY = 'Revolut_Payment::images/apple-pay-logo.svg';
    private const SRC_GOOGLE_PAY = 'Revolut_Payment::images/g-pay-logo.png';

    /**
     * @var MethodListIconPlugin
     */
    private $plugin;

    /**
     * @var RevolutMethodMetaDataFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $metaDataFactoryMock;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configProviderMock;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var MethodList|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subjectMock;

    /**
     * @var Template|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentMock;

    /**
     * @var RevolutMethodMetaData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $createdMetaData;

    protected function setUp(): void
    {
        $this->metaDataFactoryMock = $this->createMock(RevolutMethodMetaDataFactory::class);
        $this->configProviderMock = $this->createMock(ConfigProvider::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->subjectMock = $this->createMock(MethodList::class);
        $this->parentMock = $this->createMock(Template::class);

        $this->plugin = new MethodListIconPlugin(
            $this->metaDataFactoryMock,
            $this->configProviderMock,
            $this->loggerMock
        );
    }

    private function methodWithCode(string $code): MethodInterface
    {
        $method = $this->createMock(MethodInterface::class);
        $method->method('getCode')->willReturn($code);

        return $method;
    }

    private function resultWithData(array $data): AbstractMethodMetaData
    {
        $result = $this->createMock(AbstractMethodMetaData::class);
        $result->method('getData')->willReturn($data);

        return $result;
    }

    private function configWithBrands(array $brands): array
    {
        return [
            'payment' => [
                ConfigProvider::GATEWAY_CODE => [
                    'availableCardBrands' => $brands,
                ],
            ],
        ];
    }

    public function testNonRevolutMethodReturnsResultUnchanged()
    {
        $this->metaDataFactoryMock->expects($this->never())->method('create');
        $this->configProviderMock->expects($this->never())->method('getConfig');

        $result = $this->resultWithData(['label' => 'Check / Money order']);
        $method = $this->methodWithCode('checkmo');

        $out = $this->plugin->afterGetMethodMetaData($this->subjectMock, $result, $this->parentMock, $method);

        $this->assertSame($result, $out);
    }

    public function testRevolutCardWithAmexAddsThreeIcons()
    {
        $this->configProviderMock->method('getConfig')
            ->willReturn($this->configWithBrands(['visa', 'mastercard', 'amex']));

        $captured = $this->captureCreate();

        $result = $this->resultWithData(['label' => 'Revolut Pay by Card', 'sort_order' => 5]);
        $method = $this->methodWithCode(ConfigProvider::CODE);

        $out = $this->plugin->afterGetMethodMetaData($this->subjectMock, $result, $this->parentMock, $method);

        $this->assertSame($this->createdMetaData, $out);
        $this->assertSame($method, $captured->arg['method']);
        $this->assertSame('Revolut Pay by Card', $captured->arg['data']['label']);
        $this->assertSame(5, $captured->arg['data']['sort_order']);

        $icons = $captured->arg['data']['icon'];
        $this->assertCount(3, $icons);
        $this->assertSame(
            [self::SRC_MASTERCARD, self::SRC_VISA, self::SRC_AMEX],
            array_column($icons, 'src')
        );
        $this->assertSame(self::ICON_ATTRIBUTES, $icons[0]['attributes']);
    }

    public function testRevolutCardWithoutAmexAddsTwoIcons()
    {
        $this->configProviderMock->method('getConfig')
            ->willReturn($this->configWithBrands(['visa', 'mastercard']));

        $captured = $this->captureCreate();

        $result = $this->resultWithData(['label' => 'Revolut Pay by Card']);
        $method = $this->methodWithCode(ConfigProvider::CODE);

        $this->plugin->afterGetMethodMetaData($this->subjectMock, $result, $this->parentMock, $method);

        $icons = $captured->arg['data']['icon'];
        $this->assertSame([self::SRC_MASTERCARD, self::SRC_VISA], array_column($icons, 'src'));
    }

    public function testRevolutPayPrependsRevolutIconToCardIcons()
    {
        $this->configProviderMock->method('getConfig')
            ->willReturn($this->configWithBrands(['visa', 'mastercard', 'amex']));

        $captured = $this->captureCreate();

        $result = $this->resultWithData(['label' => 'Revolut Pay']);
        $method = $this->methodWithCode(ConfigProvider::REVOLUT_PAY_CODE);

        $this->plugin->afterGetMethodMetaData($this->subjectMock, $result, $this->parentMock, $method);

        $icons = $captured->arg['data']['icon'];
        $this->assertSame(
            [self::SRC_REVOLUT, self::SRC_MASTERCARD, self::SRC_VISA, self::SRC_AMEX],
            array_column($icons, 'src')
        );
    }

    public function testGooglePayUsesGooglePayIconOnly()
    {
        $this->configProviderMock->method('getConfig')
            ->willReturn($this->configWithBrands(['visa', 'mastercard', 'amex']));

        $captured = $this->captureCreate();

        $result = $this->resultWithData(['label' => 'Google Pay']);
        $method = $this->methodWithCode(ConfigProvider::REVOLUT_GOOGLE_PAY_CODE);

        $this->plugin->afterGetMethodMetaData($this->subjectMock, $result, $this->parentMock, $method);

        $icons = $captured->arg['data']['icon'];
        $this->assertSame([self::SRC_GOOGLE_PAY], array_column($icons, 'src'));
    }

    public function testApplePayUsesApplePayIconOnly()
    {
        $this->configProviderMock->method('getConfig')
            ->willReturn($this->configWithBrands(['visa', 'mastercard', 'amex']));

        $captured = $this->captureCreate();

        $result = $this->resultWithData(['label' => 'Apple Pay']);
        $method = $this->methodWithCode(ConfigProvider::REVOLUT_APPLE_PAY_CODE);

        $this->plugin->afterGetMethodMetaData($this->subjectMock, $result, $this->parentMock, $method);

        $icons = $captured->arg['data']['icon'];
        $this->assertSame([self::SRC_APPLE_PAY], array_column($icons, 'src'));
    }

    public function testGetConfigThrowingDisablesAmexAndLogs()
    {
        $this->configProviderMock->method('getConfig')
            ->willThrowException(new \RuntimeException('config failure'));
        $this->loggerMock->expects($this->once())->method('debug');

        $captured = $this->captureCreate();

        $result = $this->resultWithData(['label' => 'Revolut Pay by Card']);
        $method = $this->methodWithCode(ConfigProvider::CODE);

        $this->plugin->afterGetMethodMetaData($this->subjectMock, $result, $this->parentMock, $method);

        $icons = $captured->arg['data']['icon'];
        $this->assertSame([self::SRC_MASTERCARD, self::SRC_VISA], array_column($icons, 'src'));
    }

    public function testIconMapIsMemoizedAcrossCalls()
    {
        $this->configProviderMock->expects($this->once())->method('getConfig')
            ->willReturn($this->configWithBrands(['visa', 'mastercard']));

        $this->metaDataFactoryMock->method('create')
            ->willReturn($this->createMock(RevolutMethodMetaData::class));

        $cardResult = $this->resultWithData(['label' => 'Card']);
        $payResult = $this->resultWithData(['label' => 'Pay']);

        $this->plugin->afterGetMethodMetaData(
            $this->subjectMock,
            $cardResult,
            $this->parentMock,
            $this->methodWithCode(ConfigProvider::CODE)
        );
        $this->plugin->afterGetMethodMetaData(
            $this->subjectMock,
            $payResult,
            $this->parentMock,
            $this->methodWithCode(ConfigProvider::REVOLUT_PAY_CODE)
        );
    }

    private function captureCreate(): \stdClass
    {
        $this->createdMetaData = $this->createMock(RevolutMethodMetaData::class);
        $captured = new \stdClass();
        $captured->arg = null;

        $this->metaDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturnCallback(function ($arg) use ($captured) {
                $captured->arg = $arg;

                return $this->createdMetaData;
            });

        return $captured;
    }
}
