<?php

namespace Tourze\TLSAlert\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertFactory;
use Tourze\TLSAlert\AlertHandler;
use Tourze\TLSAlert\Listener\LoggingAlertListener;
use Tourze\TLSAlert\Listener\StatisticsAlertListener;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;
use Tourze\TLSRecord\RecordProtocol;

class AlertPerformanceTest extends TestCase
{
    public function test_alert_creation_performance(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // 创建大量 Alert 对象
        $alerts = [];
        for ($i = 0; $i < 10000; $i++) {
            $alerts[] = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // 性能断言
        $this->assertLessThan(1.0, $executionTime, '创建10000个Alert对象应该在1秒内完成');
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, '10000个Alert对象不应该使用超过10MB内存');
        
        // 清理
        unset($alerts);
    }

    public function test_alert_serialization_performance(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $startTime = microtime(true);
        
        // 大量序列化操作
        for ($i = 0; $i < 100000; $i++) {
            $binary = $alert->toBinary();
            // 避免编译器优化
            $this->assertSame(2, strlen($binary));
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(1.0, $executionTime, '100000次序列化应该在1秒内完成');
    }

    public function test_alert_deserialization_performance(): void
    {
        $binaryData = chr(AlertLevel::FATAL->value) . chr(AlertDescription::HANDSHAKE_FAILURE->value);
        
        $startTime = microtime(true);
        
        // 大量反序列化操作
        for ($i = 0; $i < 50000; $i++) {
            $alert = Alert::fromBinary($binaryData);
            // 避免编译器优化
            $this->assertTrue($alert->isFatal());
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(1.0, $executionTime, '50000次反序列化应该在1秒内完成');
    }

    public function test_alertFactory_creation_performance(): void
    {
        $startTime = microtime(true);
        
        // 使用工厂方法创建大量警告
        $alerts = [];
        for ($i = 0; $i < 10000; $i++) {
            switch ($i % 4) {
                case 0:
                    $alerts[] = AlertFactory::createHandshakeFailure();
                    break;
                case 1:
                    $alerts[] = AlertFactory::createCertificateExpired();
                    break;
                case 2:
                    $alerts[] = AlertFactory::createUnknownCA();
                    break;
                case 3:
                    $alerts[] = AlertFactory::createCloseNotify();
                    break;
            }
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(1.0, $executionTime, '工厂方法创建10000个Alert对象应该在1秒内完成');
        $this->assertCount(10000, $alerts);
        
        unset($alerts);
    }

    public function test_alertHandler_listener_notification_performance(): void
    {
        /** @var MockObject&RecordProtocol $mockRecordProtocol */
        $mockRecordProtocol = $this->createMock(RecordProtocol::class);
        $alertHandler = new AlertHandler($mockRecordProtocol, new NullLogger());
        
        // 添加多个监听器
        $listeners = [];
        for ($i = 0; $i < 100; $i++) {
            $listener = new StatisticsAlertListener();
            $listeners[] = $listener;
            $alertHandler->addListener($listener);
        }
        
        $startTime = microtime(true);
        
        // 处理大量警告
        for ($i = 0; $i < 1000; $i++) {
            $alert = AlertFactory::createHandshakeFailure();
            $alertHandler->handleAlert($alert);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(2.0, $executionTime, '100个监听器处理1000个警告应该在2秒内完成');
        
        // 验证所有监听器都收到了通知
        foreach ($listeners as $listener) {
            $this->assertSame(1000, $listener->getTotalAlertsReceived());
        }
    }

    public function test_statisticsListener_performance(): void
    {
        $listener = new StatisticsAlertListener();
        $alert = AlertFactory::createHandshakeFailure();
        
        $startTime = microtime(true);
        
        // 大量统计操作
        for ($i = 0; $i < 100000; $i++) {
            $listener->onAlertReceived($alert);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(1.0, $executionTime, '100000次统计更新应该在1秒内完成');
        $this->assertSame(100000, $listener->getTotalAlertsReceived());
    }

    public function test_loggingListener_performance(): void
    {
        $logger = new NullLogger(); // 无实际输出的日志记录器
        $listener = new LoggingAlertListener($logger);
        $alert = AlertFactory::createHandshakeFailure();
        
        $startTime = microtime(true);
        
        // 大量日志操作
        for ($i = 0; $i < 10000; $i++) {
            $listener->onAlertReceived($alert);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(2.0, $executionTime, '10000次日志记录应该在2秒内完成');
    }

    public function test_memory_efficiency_with_listener_removal(): void
    {
        /** @var MockObject&RecordProtocol $mockRecordProtocol */
        $mockRecordProtocol = $this->createMock(RecordProtocol::class);
        $alertHandler = new AlertHandler($mockRecordProtocol, new NullLogger());
        
        $initialMemory = memory_get_usage();
        
        // 添加大量监听器
        $listeners = [];
        for ($i = 0; $i < 1000; $i++) {
            $listener = new StatisticsAlertListener();
            $listeners[] = $listener;
            $alertHandler->addListener($listener);
        }
        
        $memoryAfterAdd = memory_get_usage();
        
        // 移除所有监听器
        foreach ($listeners as $listener) {
            $alertHandler->removeListener($listener);
        }
        
        // 强制垃圾回收
        unset($listeners);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $memoryAfterRemoval = memory_get_usage();
        
        // 验证内存使用是合理的
        $memoryGrowth = $memoryAfterRemoval - $initialMemory;
        $this->assertLessThan(5 * 1024 * 1024, $memoryGrowth, '添加和移除1000个监听器后内存增长不应超过5MB');
    }

    public function test_concurrent_listener_operations_simulation(): void
    {
        /** @var MockObject&RecordProtocol $mockRecordProtocol */
        $mockRecordProtocol = $this->createMock(RecordProtocol::class);
        $alertHandler = new AlertHandler($mockRecordProtocol, new NullLogger());
        
        $startTime = microtime(true);
        
        // 模拟并发场景：同时添加监听器、处理警告、移除监听器
        $listeners = [];
        
        for ($i = 0; $i < 100; $i++) {
            // 添加监听器
            $listener = new StatisticsAlertListener();
            $listeners[] = $listener;
            $alertHandler->addListener($listener);
            
            // 处理警告
            $alertHandler->handleAlert(AlertFactory::createHandshakeFailure());
            
            // 每隔几次移除一些监听器
            if ($i % 10 === 9 && count($listeners) > 5) {
                $toRemove = array_splice($listeners, 0, 5);
                foreach ($toRemove as $listenerToRemove) {
                    $alertHandler->removeListener($listenerToRemove);
                }
            }
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(2.0, $executionTime, '并发操作模拟应该在2秒内完成');
        $this->assertTrue($alertHandler->isConnectionClosed(), '连接应该因致命警告而关闭');
    }

    public function test_alert_string_conversion_performance(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $startTime = microtime(true);
        
        // 大量字符串转换操作
        for ($i = 0; $i < 50000; $i++) {
            $string = (string) $alert;
            // 避免编译器优化
            $this->assertNotNull($string);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(1.0, $executionTime, '50000次字符串转换应该在1秒内完成');
    }

    public function test_alert_array_conversion_performance(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $startTime = microtime(true);
        
        // 大量数组转换操作
        for ($i = 0; $i < 50000; $i++) {
            $array = $alert->toArray();
            // 避免编译器优化
            $this->assertNotEmpty($array);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(1.0, $executionTime, '50000次数组转换应该在1秒内完成');
    }

    public function test_factory_error_type_mapping_performance(): void
    {
        $errorTypes = [
            'handshake_failure',
            'certificate_expired',
            'unknown_ca',
            'decode_error',
            'protocol_version',
            'internal_error',
            'close_notify'
        ];
        
        $startTime = microtime(true);
        
        // 大量错误类型映射操作
        for ($i = 0; $i < 10000; $i++) {
            $errorType = $errorTypes[$i % count($errorTypes)];
            $alert = AlertFactory::createFromErrorType($errorType);
            // 避免编译器优化
            $this->assertInstanceOf(Alert::class, $alert);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(1.0, $executionTime, '10000次错误类型映射应该在1秒内完成');
    }

    public function test_performance_with_realistic_load(): void
    {
        /** @var MockObject&RecordProtocol $mockRecordProtocol */
        $mockRecordProtocol = $this->createMock(RecordProtocol::class);
        $alertHandler = new AlertHandler($mockRecordProtocol, new NullLogger());
        
        // 添加现实场景中可能的监听器数量
        $listeners = [
            new StatisticsAlertListener(),
            new LoggingAlertListener(new NullLogger()),
        ];
        
        foreach ($listeners as $listener) {
            $alertHandler->addListener($listener);
        }
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // 模拟现实负载：处理大量不同类型的警告
        $alertTypes = [
            fn() => AlertFactory::createHandshakeFailure(),
            fn() => AlertFactory::createCertificateExpired(),
            fn() => AlertFactory::createUnknownCA(),
            fn() => AlertFactory::createDecodeError(),
            fn() => AlertFactory::createProtocolVersion(),
        ];
        
        for ($i = 0; $i < 5000; $i++) {
            $alertFactory = $alertTypes[$i % count($alertTypes)];
            $alert = $alertFactory();
            $alertHandler->handleAlert($alert);
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        $this->assertLessThan(2.0, $executionTime, '现实负载测试应该在2秒内完成');
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, '现实负载不应该使用超过10MB内存');
        
        // 验证统计正确性
        /** @var StatisticsAlertListener $statsListener */
        $statsListener = $listeners[0];
        $this->assertSame(5000, $statsListener->getTotalAlertsReceived());
    }
} 