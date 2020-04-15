<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminNotification\Test\Unit\Model\System\Message;

use Magento\AdminNotification\Model\System\Message\CacheOutdated;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CacheOutdatedTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $_authorizationMock;

    /**
     * @var MockObject
     */
    protected $_cacheTypeListMock;

    /**
     * @var MockObject
     */
    protected $_urlInterfaceMock;

    /**
     * @var CacheOutdated
     */
    protected $_messageModel;

    protected function setUp(): void
    {
        $this->_authorizationMock = $this->getMockForAbstractClass(AuthorizationInterface::class);
        $this->_urlInterfaceMock = $this->getMockForAbstractClass(UrlInterface::class);
        $this->_cacheTypeListMock = $this->getMockForAbstractClass(TypeListInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $arguments = [
            'authorization' => $this->_authorizationMock,
            'urlBuilder' => $this->_urlInterfaceMock,
            'cacheTypeList' => $this->_cacheTypeListMock,
        ];
        $this->_messageModel = $objectManagerHelper->getObject(
            CacheOutdated::class,
            $arguments
        );
    }

    /**
     * @param string $expectedSum
     * @param array $cacheTypes
     * @dataProvider getIdentityDataProvider
     */
    public function testGetIdentity($expectedSum, $cacheTypes)
    {
        $this->_cacheTypeListMock->expects(
            $this->any()
        )->method(
            'getInvalidated'
        )->willReturn(
            $cacheTypes
        );
        $this->assertEquals($expectedSum, $this->_messageModel->getIdentity());
    }

    /**
     * @return array
     */
    public function getIdentityDataProvider()
    {
        $cacheTypeMock1 = $this->createPartialMock(\stdClass::class, ['getCacheType']);
        $cacheTypeMock1->expects($this->any())->method('getCacheType')->willReturn('Simple');

        $cacheTypeMock2 = $this->createPartialMock(\stdClass::class, ['getCacheType']);
        $cacheTypeMock2->expects($this->any())->method('getCacheType')->willReturn('Advanced');

        return [
            ['c13cfaddc2c53e8d32f59bfe89719beb', [$cacheTypeMock1]],
            ['69aacdf14d1d5fcef7168b9ac308215e', [$cacheTypeMock1, $cacheTypeMock2]]
        ];
    }

    /**
     * @param bool $expected
     * @param bool $allowed
     * @param array $cacheTypes
     * @dataProvider isDisplayedDataProvider
     */
    public function testIsDisplayed($expected, $allowed, $cacheTypes)
    {
        $this->_authorizationMock->expects($this->once())->method('isAllowed')->willReturn($allowed);
        $this->_cacheTypeListMock->expects(
            $this->any()
        )->method(
            'getInvalidated'
        )->willReturn(
            $cacheTypes
        );
        $this->assertEquals($expected, $this->_messageModel->isDisplayed());
    }

    /**
     * @return array
     */
    public function isDisplayedDataProvider()
    {
        $cacheTypesMock = $this->createPartialMock(\stdClass::class, ['getCacheType']);
        $cacheTypesMock->expects($this->any())->method('getCacheType')->willReturn('someVal');
        $cacheTypes = [$cacheTypesMock, $cacheTypesMock];
        return [
            [false, false, []],
            [false, false, $cacheTypes],
            [false, true, []],
            [true, true, $cacheTypes]
        ];
    }

    public function testGetText()
    {
        $messageText = 'One or more of the Cache Types are invalidated';

        $this->_cacheTypeListMock->expects($this->any())->method('getInvalidated')->willReturn([]);
        $this->_urlInterfaceMock->expects($this->once())->method('getUrl')->willReturn('someURL');
        $this->assertStringContainsString($messageText, $this->_messageModel->getText());
    }

    public function testGetLink()
    {
        $url = 'backend/admin/cache';
        $this->_urlInterfaceMock->expects($this->once())->method('getUrl')->willReturn($url);
        $this->assertEquals($url, $this->_messageModel->getLink());
    }
}
