<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ConsumerHeartbeatExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ConsumerHeartbeatExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConsumerHeartbeat */
    protected $consumerHeartbeat;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    protected $logger;

    /** @var Context */
    protected $context;

    /** @var ConsumerHeartbeatExtension */
    protected $extension;

    protected function setUp()
    {
        $this->consumerHeartbeat = $this->createMock(ConsumerHeartbeat::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context = new Context($this->createMock(SessionInterface::class));
        $this->context->setLogger($this->logger);

        $this->extension = new ConsumerHeartbeatExtension(15, $this->consumerHeartbeat);
    }

    public function testOnBeforeReceiveOnStartConsumption()
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Update the consumer state time.');
        $this->consumerHeartbeat->expects($this->once())
            ->method('tick');

        $this->extension->onBeforeReceive($this->context);
    }

    public function testOnBeforeReceiveWithNonExpiredPeriod()
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Update the consumer state time.');
        $this->consumerHeartbeat->expects($this->once())
            ->method('tick');

        $this->extension->onBeforeReceive($this->context);
        $this->extension->onBeforeReceive($this->context);
    }

    public function testOnBeforeReceiveWithTurnedOffFunctionality()
    {
        $this->extension = new ConsumerHeartbeatExtension(0, $this->consumerHeartbeat);

        $this->logger->expects($this->never())
            ->method('info');
        $this->consumerHeartbeat->expects($this->never())
            ->method('tick');

        $this->extension->onBeforeReceive($this->context);
        $this->extension->onBeforeReceive($this->context);
    }
}
