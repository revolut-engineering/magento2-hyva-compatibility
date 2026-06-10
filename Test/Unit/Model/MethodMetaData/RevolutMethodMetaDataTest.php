<?php

namespace Revolut\PaymentHyva\Test\Unit\Model\MethodMetaData;

use Hyva\Checkout\Model\ConfigData\HyvaThemes\SystemConfigPayment;
use Hyva\Checkout\Model\MethodMetaData\IconRenderer;
use Hyva\Checkout\Model\MethodMetaData\SubtitleRenderer;
use Magento\Payment\Model\MethodInterface;
use PHPUnit\Framework\TestCase;
use Revolut\PaymentHyva\Model\MethodMetaData\RevolutMethodMetaData;

class RevolutMethodMetaDataTest extends TestCase
{
    /**
     * @var IconRenderer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $iconRendererMock;

    /**
     * @var SubtitleRenderer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subtitleRendererMock;

    /**
     * @var MethodInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $methodMock;

    /**
     * @var SystemConfigPayment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $systemConfigPaymentMock;

    protected function setUp(): void
    {
        $this->iconRendererMock = $this->createMock(IconRenderer::class);
        $this->subtitleRendererMock = $this->createMock(SubtitleRenderer::class);
        $this->methodMock = $this->createMock(MethodInterface::class);
        $this->systemConfigPaymentMock = $this->createMock(SystemConfigPayment::class);
    }

    private function createMetaData(array $data): RevolutMethodMetaData
    {
        return new RevolutMethodMetaData(
            $this->iconRendererMock,
            $this->subtitleRendererMock,
            $this->methodMock,
            $this->systemConfigPaymentMock,
            $data
        );
    }

    public function testRenderIconRendersEveryEntryWhenIconIsList()
    {
        $first = ['src' => 'a.svg'];
        $second = ['src' => 'b.svg'];
        $renderedWith = [];

        $this->iconRendererMock->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function ($entry) use (&$renderedWith, $first) {
                $renderedWith[] = $entry;

                return $entry === $first ? '<a>' : '<b>';
            });

        $metaData = $this->createMetaData(['icon' => [$first, $second]]);

        $this->assertSame('<a><b>', $metaData->renderIcon());
        $this->assertSame([$first, $second], $renderedWith);
    }

    public function testRenderIconWrapsStringEntryAsSrcArray()
    {
        $this->iconRendererMock->expects($this->once())
            ->method('render')
            ->with(['src' => 'plain.svg'])
            ->willReturn('<img>');

        $metaData = $this->createMetaData(['icon' => ['plain.svg']]);

        $this->assertSame('<img>', $metaData->renderIcon());
    }

    public function testRenderIconDelegatesToParentForSingleIcon()
    {
        $icon = ['src' => 'single.svg'];

        $this->iconRendererMock->expects($this->once())
            ->method('render')
            ->with($icon)
            ->willReturn('<single>');

        $metaData = $this->createMetaData(['icon' => $icon]);

        $this->assertSame('<single>', $metaData->renderIcon());
    }

    public function testRenderIconReturnsEmptyStringWhenIconAbsent()
    {
        $this->iconRendererMock->expects($this->never())->method('render');

        $metaData = $this->createMetaData([]);

        $this->assertSame('', $metaData->renderIcon());
    }
}
