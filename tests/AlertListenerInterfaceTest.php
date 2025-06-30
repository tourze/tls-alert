<?php

namespace Tourze\TLSAlert\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertListenerInterface;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;

class AlertListenerInterfaceTest extends TestCase
{
    public function test_interface_exists(): void
    {
        $this->assertTrue(interface_exists(AlertListenerInterface::class));
    }

    public function test_interface_has_correct_methods(): void
    {
        $reflection = new ReflectionClass(AlertListenerInterface::class);
        $methods = $reflection->getMethods();
        
        $expectedMethods = [
            'onAlertReceived',
            'onAlertSent',
            'onConnectionClosed'
        ];
        
        $actualMethods = array_map(fn(ReflectionMethod $method) => $method->getName(), $methods);
        
        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains($expectedMethod, $actualMethods, "接口应该包含方法: {$expectedMethod}");
        }
        
        $this->assertCount(count($expectedMethods), $methods, '接口方法数量应该匹配');
    }

    public function test_onAlertReceived_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertListenerInterface::class);
        $method = $reflection->getMethod('onAlertReceived');
        
        $this->assertTrue($method->isPublic());
        $this->assertSame('void', (string) $method->getReturnType());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('alert', $parameters[0]->getName());
        $this->assertSame(Alert::class, (string) $parameters[0]->getType());
    }

    public function test_onAlertSent_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertListenerInterface::class);
        $method = $reflection->getMethod('onAlertSent');
        
        $this->assertTrue($method->isPublic());
        $this->assertSame('void', (string) $method->getReturnType());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('alert', $parameters[0]->getName());
        $this->assertSame(Alert::class, (string) $parameters[0]->getType());
    }

    public function test_onConnectionClosed_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertListenerInterface::class);
        $method = $reflection->getMethod('onConnectionClosed');
        
        $this->assertTrue($method->isPublic());
        $this->assertSame('void', (string) $method->getReturnType());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('alert', $parameters[0]->getName());
        $this->assertSame(Alert::class, (string) $parameters[0]->getType());
    }

    public function test_interface_can_be_implemented(): void
    {
        $implementation = new class implements AlertListenerInterface {
            public function onAlertReceived(Alert $alert): void {}
            public function onAlertSent(Alert $alert): void {}
            public function onConnectionClosed(Alert $alert): void {}
        };
        
        $this->assertInstanceOf(AlertListenerInterface::class, $implementation);
    }

    public function test_interface_methods_can_be_called(): void
    {
        $callCount = 0;
        
        $implementation = new class($callCount) implements AlertListenerInterface {
            public function __construct(private int &$callCount) {}
            
            public function onAlertReceived(Alert $alert): void {
                $this->callCount++;
            }
            
            public function onAlertSent(Alert $alert): void {
                $this->callCount++;
            }
            
            public function onConnectionClosed(Alert $alert): void {
                $this->callCount++;
            }
        };
        
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $implementation->onAlertReceived($alert);
        $implementation->onAlertSent($alert);
        $implementation->onConnectionClosed($alert);
        
        $this->assertSame(3, $callCount);
    }

    public function test_interface_methods_documentation(): void
    {
        $reflection = new ReflectionClass(AlertListenerInterface::class);
        
        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotFalse($docComment, "方法 {$method->getName()} 应该有文档注释");
            
            if (count($method->getParameters()) > 0) {
                $this->assertStringContainsString('@param', $docComment, "方法 {$method->getName()} 的文档应该包含参数说明");
            }
            
            $this->assertStringContainsString('@return', $docComment, "方法 {$method->getName()} 的文档应该包含返回值说明");
        }
    }

    public function test_interface_has_proper_namespace(): void
    {
        $reflection = new ReflectionClass(AlertListenerInterface::class);
        $this->assertSame('Tourze\\TLSAlert', $reflection->getNamespaceName());
    }

    public function test_interface_is_interface_not_class(): void
    {
        $reflection = new ReflectionClass(AlertListenerInterface::class);
        $this->assertTrue($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
        $this->assertFalse($reflection->isEnum());
    }

    public function test_multiple_implementations_can_coexist(): void
    {
        $impl1 = new class implements AlertListenerInterface {
            public function onAlertReceived(Alert $alert): void {}
            public function onAlertSent(Alert $alert): void {}
            public function onConnectionClosed(Alert $alert): void {}
        };
        
        $impl2 = new class implements AlertListenerInterface {
            public function onAlertReceived(Alert $alert): void {}
            public function onAlertSent(Alert $alert): void {}
            public function onConnectionClosed(Alert $alert): void {}
        };
        
        $this->assertInstanceOf(AlertListenerInterface::class, $impl1);
        $this->assertInstanceOf(AlertListenerInterface::class, $impl2);
        $this->assertNotSame($impl1, $impl2);
    }

    public function test_interface_methods_accept_different_alert_types(): void
    {
        $receivedAlerts = [];
        
        $implementation = new class($receivedAlerts) implements AlertListenerInterface {
            /** @phpstan-ignore property.onlyWritten */
            public function __construct(private array &$receivedAlerts) {}
            
            public function onAlertReceived(Alert $alert): void {
                $this->receivedAlerts[] = ['type' => 'received', 'alert' => $alert];
            }
            
            public function onAlertSent(Alert $alert): void {
                $this->receivedAlerts[] = ['type' => 'sent', 'alert' => $alert];
            }
            
            public function onConnectionClosed(Alert $alert): void {
                $this->receivedAlerts[] = ['type' => 'closed', 'alert' => $alert];
            }
        };
        
        $fatalAlert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $warningAlert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        
        $implementation->onAlertReceived($fatalAlert);
        $implementation->onAlertSent($warningAlert);
        $implementation->onConnectionClosed($fatalAlert);
        
        $this->assertCount(3, $receivedAlerts);
        $this->assertSame('received', $receivedAlerts[0]['type']);
        $this->assertSame($fatalAlert, $receivedAlerts[0]['alert']);
        $this->assertSame('sent', $receivedAlerts[1]['type']);
        $this->assertSame($warningAlert, $receivedAlerts[1]['alert']);
        $this->assertSame('closed', $receivedAlerts[2]['type']);
        $this->assertSame($fatalAlert, $receivedAlerts[2]['alert']);
    }
} 