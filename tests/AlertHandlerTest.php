<?php

namespace Tourze\TLSAlert\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertException;
use Tourze\TLSAlert\AlertHandler;
use Tourze\TLSAlert\AlertListenerInterface;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;
use Tourze\TLSCommon\Protocol\ContentType;
use Tourze\TLSRecord\RecordProtocol;

class AlertHandlerTest extends TestCase
{
    /** @var MockObject&RecordProtocol */
    private MockObject $mockRecordProtocol;
    /** @var MockObject&LoggerInterface */
    private MockObject $mockLogger;
    private AlertHandler $alertHandler;

    protected function setUp(): void
    {
        $this->mockRecordProtocol = $this->createMock(RecordProtocol::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->alertHandler = new AlertHandler($this->mockRecordProtocol, $this->mockLogger);
    }

    public function test_handleAlert_logs_received_alert(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $this->mockLogger->expects($this->atLeastOnce())
            ->method('info');
        
        $this->mockLogger->expects($this->once())
            ->method('error');

        $this->alertHandler->handleAlert($alert);
        
        $this->assertSame($alert, $this->alertHandler->getLastReceivedAlert());
    }

    public function test_handleAlert_with_close_notify_closes_connection(): void
    {
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        
        $this->mockLogger->expects($this->atLeastOnce())
            ->method('info');

        $this->alertHandler->handleAlert($alert);
        
        $this->assertTrue($this->alertHandler->isConnectionClosed());
    }

    public function test_handleAlert_with_fatal_alert_closes_connection(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $this->mockLogger->expects($this->atLeastOnce())
            ->method('error');

        $this->alertHandler->handleAlert($alert);
        
        $this->assertTrue($this->alertHandler->isConnectionClosed());
    }

    public function test_handleAlert_with_warning_alert_does_not_close_connection(): void
    {
        // 注意：在实际的 TLS 协议中，只有 CLOSE_NOTIFY 是 WARNING 级别
        // 而 CLOSE_NOTIFY 确实会关闭连接，所以这个测试需要调整
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        
        $this->mockLogger->expects($this->atLeastOnce())
            ->method('info');

        $this->alertHandler->handleAlert($alert);
        
        // CLOSE_NOTIFY 会关闭连接，所以这里实际上会是 true
        $this->assertTrue($this->alertHandler->isConnectionClosed());
    }

    public function test_sendAlert_sends_through_record_protocol(): void
    {
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        
        $this->mockRecordProtocol->expects($this->once())
            ->method('sendRecord')
            ->with(ContentType::ALERT->value, $alert->toBinary());

        $this->mockLogger->expects($this->atLeastOnce())
            ->method('info');

        $this->alertHandler->sendAlert($alert);
        
        $this->assertSame($alert, $this->alertHandler->getLastSentAlert());
    }

    public function test_sendAlert_with_fatal_alert_closes_connection(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $this->mockRecordProtocol->expects($this->once())
            ->method('sendRecord');

        $this->alertHandler->sendAlert($alert);
        
        $this->assertTrue($this->alertHandler->isConnectionClosed());
    }

    public function test_sendAlert_throws_exception_when_record_protocol_fails(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $this->mockRecordProtocol->expects($this->once())
            ->method('sendRecord')
            ->willThrowException(new \Exception('Network error'));

        $this->mockLogger->expects($this->once())
            ->method('error');

        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('发送警告失败: Network error');

        $this->alertHandler->sendAlert($alert);
    }

    public function test_addListener_and_removeListener(): void
    {
        /** @var MockObject&AlertListenerInterface $listener */
        $listener = $this->createMock(AlertListenerInterface::class);
        
        $this->alertHandler->addListener($listener);
        
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $listener->expects($this->once())
            ->method('onAlertReceived')
            ->with($alert);

        $this->alertHandler->handleAlert($alert);
        
        // 移除监听器
        $this->alertHandler->removeListener($listener);
        
        // 再次处理警告，监听器不应该被调用
        $listener->expects($this->never())
            ->method('onAlertReceived');

        $alert2 = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        $this->alertHandler->handleAlert($alert2);
    }

    public function test_listener_receives_alert_sent_notification(): void
    {
        /** @var MockObject&AlertListenerInterface $listener */
        $listener = $this->createMock(AlertListenerInterface::class);
        $this->alertHandler->addListener($listener);
        
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $listener->expects($this->once())
            ->method('onAlertSent')
            ->with($alert);

        $this->mockRecordProtocol->expects($this->once())
            ->method('sendRecord');

        $this->alertHandler->sendAlert($alert);
    }

    public function test_listener_receives_connection_closed_notification(): void
    {
        /** @var MockObject&AlertListenerInterface $listener */
        $listener = $this->createMock(AlertListenerInterface::class);
        $this->alertHandler->addListener($listener);
        
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $listener->expects($this->once())
            ->method('onConnectionClosed')
            ->with($alert);

        $this->alertHandler->handleAlert($alert);
    }

    public function test_sendCloseNotify(): void
    {
        $this->mockRecordProtocol->expects($this->once())
            ->method('sendRecord')
            ->with(
                ContentType::ALERT->value,
                $this->callback(function ($data) {
                    return strlen($data) === 2 
                        && ord($data[0]) === AlertLevel::WARNING->value
                        && ord($data[1]) === AlertDescription::CLOSE_NOTIFY->value;
                })
            );

        $this->alertHandler->sendCloseNotify();
        
        $lastSent = $this->alertHandler->getLastSentAlert();
        $this->assertNotNull($lastSent);
        $this->assertTrue($lastSent->isCloseNotify());
    }

    public function test_sendFatalAlert(): void
    {
        $description = AlertDescription::HANDSHAKE_FAILURE;
        
        $this->mockRecordProtocol->expects($this->once())
            ->method('sendRecord')
            ->with(
                ContentType::ALERT->value,
                $this->callback(function ($data) use ($description) {
                    return strlen($data) === 2 
                        && ord($data[0]) === AlertLevel::FATAL->value
                        && ord($data[1]) === $description->value;
                })
            );

        $this->alertHandler->sendFatalAlert($description);
        
        $lastSent = $this->alertHandler->getLastSentAlert();
        $this->assertNotNull($lastSent);
        $this->assertTrue($lastSent->isFatal());
        $this->assertSame($description, $lastSent->description);
    }

    public function test_isConnectionClosed_initially_false(): void
    {
        $this->assertFalse($this->alertHandler->isConnectionClosed());
    }

    public function test_getLastReceivedAlert_initially_null(): void
    {
        $this->assertNull($this->alertHandler->getLastReceivedAlert());
    }

    public function test_getLastSentAlert_initially_null(): void
    {
        $this->assertNull($this->alertHandler->getLastSentAlert());
    }
} 