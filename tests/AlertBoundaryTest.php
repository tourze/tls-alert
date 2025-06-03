<?php

namespace Tourze\TLSAlert\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertException;
use Tourze\TLSAlert\AlertFactory;
use Tourze\TLSAlert\AlertHandler;
use Tourze\TLSAlert\Listener\StatisticsAlertListener;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;
use Tourze\TLSRecord\RecordProtocol;

class AlertBoundaryTest extends TestCase
{
    public function test_alert_fromBinary_with_empty_string(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('警告消息数据长度不足，至少需要2字节');
        
        Alert::fromBinary('');
    }

    public function test_alert_fromBinary_with_single_byte(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('警告消息数据长度不足，至少需要2字节');
        
        Alert::fromBinary('x');
    }

    public function test_alert_fromBinary_with_extra_bytes_ignored(): void
    {
        $data = chr(AlertLevel::FATAL->value) . chr(AlertDescription::HANDSHAKE_FAILURE->value) . 'extra_data';
        $alert = Alert::fromBinary($data);
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::HANDSHAKE_FAILURE, $alert->description);
    }

    public function test_alert_fromBinary_with_null_bytes(): void
    {
        $data = chr(0) . chr(0);
        
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的警告级别: 0');
        
        Alert::fromBinary($data);
    }

    public function test_alert_fromBinary_with_max_byte_values(): void
    {
        $data = chr(255) . chr(255);
        
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的警告级别: 255');
        
        Alert::fromBinary($data);
    }

    public function test_alert_fromBinary_with_invalid_level_valid_description(): void
    {
        $data = chr(99) . chr(AlertDescription::HANDSHAKE_FAILURE->value);
        
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的警告级别: 99');
        
        Alert::fromBinary($data);
    }

    public function test_alert_fromBinary_with_valid_level_invalid_description(): void
    {
        $data = chr(AlertLevel::FATAL->value) . chr(199);
        
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的警告描述: 199');
        
        Alert::fromBinary($data);
    }

    public function test_alertFactory_createFromErrorType_with_empty_string(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的错误类型: ');
        
        AlertFactory::createFromErrorType('');
    }

    public function test_alertFactory_createFromErrorType_with_whitespace(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的错误类型:   ');
        
        AlertFactory::createFromErrorType('  ');
    }

    public function test_alertFactory_createFromErrorType_with_case_sensitivity(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的错误类型: HANDSHAKE_FAILURE');
        
        AlertFactory::createFromErrorType('HANDSHAKE_FAILURE');
    }

    public function test_alertFactory_createFromErrorType_with_unicode_input(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的错误类型: 握手失败');
        
        AlertFactory::createFromErrorType('握手失败');
    }

    public function test_alertHandler_with_very_large_number_of_listeners(): void
    {
        /** @var MockObject&RecordProtocol $mockRecordProtocol */
        $mockRecordProtocol = $this->createMock(RecordProtocol::class);
        $alertHandler = new AlertHandler($mockRecordProtocol);
        
        // 添加大量监听器
        $listeners = [];
        for ($i = 0; $i < 1000; $i++) {
            $listener = new StatisticsAlertListener();
            $listeners[] = $listener;
            $alertHandler->addListener($listener);
        }
        
        // 处理警告
        $alert = AlertFactory::createHandshakeFailure();
        $alertHandler->handleAlert($alert);
        
        // 验证所有监听器都被调用
        foreach ($listeners as $listener) {
            $this->assertSame(1, $listener->getTotalAlertsReceived());
        }
    }

    public function test_alertHandler_removing_non_existent_listener(): void
    {
        /** @var MockObject&RecordProtocol $mockRecordProtocol */
        $mockRecordProtocol = $this->createMock(RecordProtocol::class);
        $alertHandler = new AlertHandler($mockRecordProtocol);
        
        $listener = new StatisticsAlertListener();
        
        // 移除未添加的监听器不应该抛出异常
        $alertHandler->removeListener($listener);
        
        // 正常操作应该继续工作
        $alertHandler->handleAlert(AlertFactory::createHandshakeFailure());
        $this->assertTrue($alertHandler->isConnectionClosed());
    }

    public function test_alertHandler_multiple_removals_of_same_listener(): void
    {
        /** @var MockObject&RecordProtocol $mockRecordProtocol */
        $mockRecordProtocol = $this->createMock(RecordProtocol::class);
        $alertHandler = new AlertHandler($mockRecordProtocol);
        
        $listener = new StatisticsAlertListener();
        $alertHandler->addListener($listener);
        
        // 多次移除同一个监听器
        $alertHandler->removeListener($listener);
        $alertHandler->removeListener($listener);
        $alertHandler->removeListener($listener);
        
        // 验证没有副作用
        $alertHandler->handleAlert(AlertFactory::createHandshakeFailure());
        $this->assertSame(0, $listener->getTotalAlertsReceived());
    }

    public function test_statisticsListener_with_extreme_values(): void
    {
        $listener = new StatisticsAlertListener();
        
        // 处理大量警告
        $alert = AlertFactory::createHandshakeFailure();
        for ($i = 0; $i < 10000; $i++) {
            $listener->onAlertReceived($alert);
            $listener->onAlertSent($alert);
            $listener->onConnectionClosed($alert);
        }
        
        $this->assertSame(10000, $listener->getTotalAlertsReceived());
        $this->assertSame(10000, $listener->getTotalAlertsSent());
        $this->assertSame(10000, $listener->getConnectionsClosedCount());
    }

    public function test_statisticsListener_integer_overflow_protection(): void
    {
        $listener = new StatisticsAlertListener();
        
        // 使用反射设置一个接近溢出的值
        $reflection = new \ReflectionClass($listener);
        $property = $reflection->getProperty('totalAlertsReceived');
        $property->setAccessible(true);
        $property->setValue($listener, PHP_INT_MAX - 1);
        
        $alert = AlertFactory::createHandshakeFailure();
        $listener->onAlertReceived($alert);
        
        // 在 PHP 中，整数溢出会转换为浮点数或抛出错误
        // 这里我们只是验证不会崩溃
        $this->assertTrue($listener->getTotalAlertsReceived() > 0);
    }

    public function test_alert_getHumanReadableDescription_with_all_descriptions(): void
    {
        // 测试所有已知的警告描述都有可读的描述
        $descriptions = [
            AlertDescription::CLOSE_NOTIFY,
            AlertDescription::UNEXPECTED_MESSAGE,
            AlertDescription::BAD_RECORD_MAC,
            AlertDescription::DECRYPTION_FAILED,
            AlertDescription::RECORD_OVERFLOW,
            AlertDescription::DECOMPRESSION_FAILURE,
            AlertDescription::HANDSHAKE_FAILURE,
            AlertDescription::BAD_CERTIFICATE,
            AlertDescription::UNSUPPORTED_CERTIFICATE,
            AlertDescription::CERTIFICATE_REVOKED,
            AlertDescription::CERTIFICATE_EXPIRED,
            AlertDescription::CERTIFICATE_UNKNOWN,
            AlertDescription::ILLEGAL_PARAMETER,
            AlertDescription::UNKNOWN_CA,
            AlertDescription::ACCESS_DENIED,
            AlertDescription::DECODE_ERROR,
            AlertDescription::DECRYPT_ERROR,
            AlertDescription::PROTOCOL_VERSION,
            AlertDescription::INSUFFICIENT_SECURITY,
            AlertDescription::INTERNAL_ERROR,
            AlertDescription::INAPPROPRIATE_FALLBACK,
            AlertDescription::USER_CANCELED,
            AlertDescription::MISSING_EXTENSION,
            AlertDescription::UNSUPPORTED_EXTENSION,
            AlertDescription::CERTIFICATE_UNOBTAINABLE,
            AlertDescription::UNRECOGNIZED_NAME,
            AlertDescription::BAD_CERTIFICATE_STATUS_RESPONSE,
            AlertDescription::BAD_CERTIFICATE_HASH_VALUE,
            AlertDescription::UNKNOWN_PSK_IDENTITY,
            AlertDescription::CERTIFICATE_REQUIRED,
            AlertDescription::NO_APPLICATION_PROTOCOL,
        ];
        
        foreach ($descriptions as $description) {
            $alert = new Alert(AlertLevel::FATAL, $description);
            $readable = $alert->getHumanReadableDescription();
            
            $this->assertIsString($readable);
            $this->assertNotEmpty($readable);
            $this->assertNotSame('未知警告: ' . $description->asString(), $readable, 
                "描述 {$description->asString()} 应该有具体的可读描述");
        }
    }

    public function test_alert_toArray_structure_consistency(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $array = $alert->toArray();
        
        $expectedKeys = [
            'level',
            'level_name',
            'description',
            'description_name',
            'human_readable',
            'is_fatal',
            'is_close_notify'
        ];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "数组表示应该包含键: {$key}");
        }
        
        $this->assertSame(count($expectedKeys), count($array), '数组不应该包含额外的键');
    }

    public function test_memory_usage_with_many_alerts(): void
    {
        $initialMemory = memory_get_usage();
        
        // 创建大量 Alert 对象
        $alerts = [];
        for ($i = 0; $i < 1000; $i++) {
            $alerts[] = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        }
        
        $memoryAfterCreation = memory_get_usage();
        $memoryUsed = $memoryAfterCreation - $initialMemory;
        
        // 验证内存使用是合理的（每个 Alert 对象应该很小）
        $this->assertLessThan(1024 * 1024, $memoryUsed, '1000个Alert对象不应该使用超过1MB内存');
        
        // 清理
        unset($alerts);
        
        $memoryAfterCleanup = memory_get_usage();
        $this->assertLessThanOrEqual($memoryAfterCreation, $memoryAfterCleanup, '内存应该被释放');
    }

    public function test_alert_binary_format_consistency(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        // 多次序列化应该产生相同的结果
        $binary1 = $alert->toBinary();
        $binary2 = $alert->toBinary();
        $binary3 = $alert->toBinary();
        
        $this->assertSame($binary1, $binary2);
        $this->assertSame($binary2, $binary3);
        
        // 反序列化后再序列化应该产生相同的结果
        $restored = Alert::fromBinary($binary1);
        $reserialized = $restored->toBinary();
        
        $this->assertSame($binary1, $reserialized);
    }
} 