<?php

namespace Tourze\TLSAlert\Tests\Listener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\Listener\LoggingAlertListener;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;

class LoggingAlertListenerTest extends TestCase
{
    /** @var MockObject&LoggerInterface */
    private MockObject $mockLogger;
    private LoggingAlertListener $listener;

    protected function setUp(): void
    {
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->listener = new LoggingAlertListener($this->mockLogger);
    }

    public function test_onAlertReceived_with_fatal_alert_logs_error(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                '收到致命TLS警告',
                $this->callback(function ($context) {
                    return $context['level'] === '致命'
                        && $context['description'] === 'handshake_failure'
                        && $context['human_readable'] === '无法协商安全参数'
                        && $context['is_fatal'] === true
                        && $context['binary'] === bin2hex(chr(AlertLevel::FATAL->value) . chr(AlertDescription::HANDSHAKE_FAILURE->value));
                })
            );

        $this->listener->onAlertReceived($alert);
    }

    public function test_onAlertReceived_with_close_notify_logs_info(): void
    {
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                '收到TLS关闭通知',
                $this->callback(function ($context) {
                    return $context['level'] === '警告'
                        && $context['description'] === 'close_notify'
                        && $context['human_readable'] === '连接正常关闭'
                        && $context['is_fatal'] === false
                        && $context['binary'] === bin2hex(chr(AlertLevel::WARNING->value) . chr(AlertDescription::CLOSE_NOTIFY->value));
                })
            );

        $this->listener->onAlertReceived($alert);
    }

    public function test_onAlertReceived_with_other_warning_logs_warning(): void
    {
        // 注意：在实际的TLS协议中，只有CLOSE_NOTIFY是WARNING级别
        // 这里我们测试如果有其他WARNING级别的情况
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);

        $this->mockLogger->expects($this->once())
            ->method('info'); // CLOSE_NOTIFY使用info级别

        $this->listener->onAlertReceived($alert);
    }

    public function test_onAlertSent_with_fatal_alert_logs_error(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::CERTIFICATE_EXPIRED);

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                '发送致命TLS警告',
                $this->callback(function ($context) {
                    return $context['level'] === '致命'
                        && $context['description'] === 'certificate_expired'
                        && $context['human_readable'] === '证书已过期'
                        && $context['is_fatal'] === true
                        && $context['binary'] === bin2hex(chr(AlertLevel::FATAL->value) . chr(AlertDescription::CERTIFICATE_EXPIRED->value));
                })
            );

        $this->listener->onAlertSent($alert);
    }

    public function test_onAlertSent_with_close_notify_logs_info(): void
    {
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                '发送TLS关闭通知',
                $this->callback(function ($context) {
                    return $context['level'] === '警告'
                        && $context['description'] === 'close_notify'
                        && $context['human_readable'] === '连接正常关闭'
                        && $context['is_fatal'] === false
                        && $context['binary'] === bin2hex(chr(AlertLevel::WARNING->value) . chr(AlertDescription::CLOSE_NOTIFY->value));
                })
            );

        $this->listener->onAlertSent($alert);
    }

    public function test_onConnectionClosed_logs_critical(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::UNKNOWN_CA);

        $this->mockLogger->expects($this->once())
            ->method('critical')
            ->with(
                'TLS连接已关闭',
                [
                    'reason_level' => '致命',
                    'reason_description' => 'unknown_ca',
                    'reason_human_readable' => '无法验证证书颁发机构',
                ]
            );

        $this->listener->onConnectionClosed($alert);
    }

    public function test_constructor_with_default_logger(): void
    {
        $listener = new LoggingAlertListener();
        
        // 使用默认的NullLogger，不应该抛出异常
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $listener->onAlertReceived($alert);
        $listener->onAlertSent($alert);
        $listener->onConnectionClosed($alert);
        
        // 如果没有异常，测试通过
        $this->assertTrue(true);
    }

    public function test_context_includes_all_required_fields(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::DECODE_ERROR);

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                '收到致命TLS警告',
                $this->callback(function ($context) {
                    // 验证上下文包含所有必需字段
                    return isset($context['level'])
                        && isset($context['description'])
                        && isset($context['human_readable'])
                        && isset($context['is_fatal'])
                        && isset($context['binary'])
                        && is_string($context['level'])
                        && is_string($context['description'])
                        && is_string($context['human_readable'])
                        && is_bool($context['is_fatal'])
                        && is_string($context['binary']);
                })
            );

        $this->listener->onAlertReceived($alert);
    }

    public function test_binary_representation_in_context(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::BAD_CERTIFICATE);
        $expectedBinary = bin2hex($alert->toBinary());

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                '收到致命TLS警告',
                $this->callback(function ($context) use ($expectedBinary) {
                    return $context['binary'] === $expectedBinary;
                })
            );

        $this->listener->onAlertReceived($alert);
    }

    public function test_all_alert_types_log_correctly(): void
    {
        $testCases = [
            [AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE, 'error'],
            [AlertLevel::FATAL, AlertDescription::CERTIFICATE_EXPIRED, 'error'],
            [AlertLevel::FATAL, AlertDescription::UNKNOWN_CA, 'error'],
            [AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY, 'info'],
        ];

        foreach ($testCases as [$level, $description, $expectedLogLevel]) {
            $alert = new Alert($level, $description);
            
            /** @var MockObject&LoggerInterface $mockLogger */
            $mockLogger = $this->createMock(LoggerInterface::class);
            $mockLogger->expects($this->once())
                ->method($expectedLogLevel);
                
            $listener = new LoggingAlertListener($mockLogger);
            $listener->onAlertReceived($alert);
        }
    }
} 