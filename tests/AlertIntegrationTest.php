<?php

namespace Tourze\TLSAlert\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertFactory;
use Tourze\TLSAlert\AlertHandler;
use Tourze\TLSAlert\AlertListenerInterface;
use Tourze\TLSAlert\Listener\LoggingAlertListener;
use Tourze\TLSAlert\Listener\StatisticsAlertListener;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\ContentType;
use Tourze\TLSRecord\RecordProtocol;

class AlertIntegrationTest extends TestCase
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

    public function test_complete_alert_handling_workflow(): void
    {
        // 创建监听器
        $statsListener = new StatisticsAlertListener();
        $loggingListener = new LoggingAlertListener($this->mockLogger);
        
        // 添加监听器
        $this->alertHandler->addListener($statsListener);
        $this->alertHandler->addListener($loggingListener);
        
        // 模拟接收警告
        $receivedAlert = AlertFactory::createHandshakeFailure();
        
        // 配置日志模拟
        $this->mockLogger->expects($this->atLeastOnce())
            ->method('info');
        $this->mockLogger->expects($this->atLeastOnce())
            ->method('error');
        $this->mockLogger->expects($this->atLeastOnce())
            ->method('critical');
        
        // 处理接收到的警告
        $this->alertHandler->handleAlert($receivedAlert);
        
        // 验证状态
        $this->assertSame($receivedAlert, $this->alertHandler->getLastReceivedAlert());
        $this->assertTrue($this->alertHandler->isConnectionClosed());
        $this->assertSame(1, $statsListener->getTotalAlertsReceived());
        $this->assertSame(1, $statsListener->getFatalAlertsReceived());
        $this->assertSame(1, $statsListener->getConnectionsClosedCount());
        
        // 发送警告响应
        $responseAlert = AlertFactory::createCloseNotify();
        
        $this->mockRecordProtocol->expects($this->once())
            ->method('sendRecord')
            ->with(ContentType::ALERT->value, $responseAlert->toBinary());
        
        $this->alertHandler->sendAlert($responseAlert);
        
        // 验证发送状态
        $this->assertSame($responseAlert, $this->alertHandler->getLastSentAlert());
        $this->assertSame(1, $statsListener->getTotalAlertsSent());
        $this->assertSame(1, $statsListener->getWarningAlertsSent());
    }

    public function test_multiple_alert_types_handling(): void
    {
        $statsListener = new StatisticsAlertListener();
        $this->alertHandler->addListener($statsListener);
        
        // 模拟各种类型的警告
        $alerts = [
            AlertFactory::createHandshakeFailure(),
            AlertFactory::createCertificateExpired(),
            AlertFactory::createUnknownCA(),
            AlertFactory::createDecodeError(),
        ];
        
        foreach ($alerts as $alert) {
            $this->alertHandler->handleAlert($alert);
        }
        
        // 验证统计
        $this->assertSame(4, $statsListener->getTotalAlertsReceived());
        $this->assertSame(4, $statsListener->getFatalAlertsReceived());
        $this->assertSame(0, $statsListener->getWarningAlertsReceived());
        
        $receivedByType = $statsListener->getReceivedAlertsByType();
        $this->assertSame(1, $receivedByType['handshake_failure']);
        $this->assertSame(1, $receivedByType['certificate_expired']);
        $this->assertSame(1, $receivedByType['unknown_ca']);
        $this->assertSame(1, $receivedByType['decode_error']);
    }

    public function test_alert_binary_serialization_roundtrip(): void
    {
        $originalAlert = AlertFactory::createCertificateExpired();
        
        // 序列化
        $binary = $originalAlert->toBinary();
        $this->assertSame(2, strlen($binary));
        
        // 反序列化
        $restoredAlert = Alert::fromBinary($binary);
        
        // 验证完整性
        $this->assertSame($originalAlert->level, $restoredAlert->level);
        $this->assertSame($originalAlert->description, $restoredAlert->description);
        $this->assertSame($originalAlert->isFatal(), $restoredAlert->isFatal());
        $this->assertSame($originalAlert->getHumanReadableDescription(), $restoredAlert->getHumanReadableDescription());
    }

    public function test_listener_chain_execution_order(): void
    {
        $executionOrder = [];
        
        $listener1 = new class($executionOrder) implements AlertListenerInterface {
            public function __construct(private array &$executionOrder) {}
            
            public function onAlertReceived(Alert $alert): void {
                $this->executionOrder[] = 'listener1_received';
            }
            
            public function onAlertSent(Alert $alert): void {
                $this->executionOrder[] = 'listener1_sent';
            }
            
            public function onConnectionClosed(Alert $alert): void {
                $this->executionOrder[] = 'listener1_closed';
            }
        };
        
        $listener2 = new class($executionOrder) implements AlertListenerInterface {
            public function __construct(private array &$executionOrder) {}
            
            public function onAlertReceived(Alert $alert): void {
                $this->executionOrder[] = 'listener2_received';
            }
            
            public function onAlertSent(Alert $alert): void {
                $this->executionOrder[] = 'listener2_sent';
            }
            
            public function onConnectionClosed(Alert $alert): void {
                $this->executionOrder[] = 'listener2_closed';
            }
        };
        
        // 按顺序添加监听器
        $this->alertHandler->addListener($listener1);
        $this->alertHandler->addListener($listener2);
        
        // 处理警告
        $alert = AlertFactory::createHandshakeFailure();
        $this->alertHandler->handleAlert($alert);
        
        // 验证执行顺序
        $expectedOrder = [
            'listener1_received',
            'listener2_received',
            'listener1_closed',
            'listener2_closed'
        ];
        
        $this->assertSame($expectedOrder, $executionOrder);
    }

    public function test_factory_integration_with_handler(): void
    {
        $this->mockRecordProtocol->expects($this->exactly(3))
            ->method('sendRecord');
        
        // 使用工厂创建并发送不同类型的警告
        $this->alertHandler->sendAlert(AlertFactory::createHandshakeFailure());
        $this->alertHandler->sendAlert(AlertFactory::createCertificateExpired());
        $this->alertHandler->sendAlert(AlertFactory::createCloseNotify());
        
        // 验证最后发送的警告
        $lastSent = $this->alertHandler->getLastSentAlert();
        $this->assertNotNull($lastSent);
        $this->assertTrue($lastSent->isCloseNotify());
    }

    public function test_error_handling_during_send(): void
    {
        $statsListener = new StatisticsAlertListener();
        $this->alertHandler->addListener($statsListener);
        
        // 模拟网络错误
        $this->mockRecordProtocol->expects($this->once())
            ->method('sendRecord')
            ->willThrowException(new \Exception('网络错误'));
        
        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                '发送警告失败',
                $this->callback(function ($context) {
                    return isset($context['alert']) && isset($context['error']);
                })
            );
        
        $alert = AlertFactory::createHandshakeFailure();
        
        try {
            $this->alertHandler->sendAlert($alert);
            $this->fail('应该抛出 AlertException');
        } catch (\Tourze\TLSAlert\AlertException $e) {
            $this->assertStringContainsString('发送警告失败', $e->getMessage());
            $this->assertStringContainsString('网络错误', $e->getMessage());
        }
        
        // 验证统计不受影响（因为发送失败）
        $this->assertSame(0, $statsListener->getTotalAlertsSent());
    }

    public function test_listener_removal_during_operation(): void
    {
        $statsListener = new StatisticsAlertListener();
        $this->alertHandler->addListener($statsListener);
        
        // 处理第一个警告
        $this->alertHandler->handleAlert(AlertFactory::createHandshakeFailure());
        $this->assertSame(1, $statsListener->getTotalAlertsReceived());
        
        // 移除监听器
        $this->alertHandler->removeListener($statsListener);
        
        // 处理第二个警告
        $this->alertHandler->handleAlert(AlertFactory::createCertificateExpired());
        
        // 统计不应该更新
        $this->assertSame(1, $statsListener->getTotalAlertsReceived());
    }

    public function test_concurrent_alert_processing_simulation(): void
    {
        $statsListener = new StatisticsAlertListener();
        $this->alertHandler->addListener($statsListener);
        
        // 模拟快速连续的警告处理
        $alerts = [
            AlertFactory::createHandshakeFailure(),
            AlertFactory::createCertificateExpired(),
            AlertFactory::createUnknownCA(),
            AlertFactory::createDecodeError(),
            AlertFactory::createProtocolVersion()
        ];
        
        foreach ($alerts as $alert) {
            $this->alertHandler->handleAlert($alert);
        }
        
        // 验证所有警告都被正确处理
        $this->assertSame(5, $statsListener->getTotalAlertsReceived());
        $this->assertSame(5, $statsListener->getFatalAlertsReceived());
        $this->assertTrue($this->alertHandler->isConnectionClosed());
        
        // 验证最后接收的警告
        $lastReceived = $this->alertHandler->getLastReceivedAlert();
        $this->assertNotNull($lastReceived);
        $this->assertSame(AlertDescription::PROTOCOL_VERSION, $lastReceived->description);
    }
} 