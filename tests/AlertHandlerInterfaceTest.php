<?php

namespace Tourze\TLSAlert\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertHandlerInterface;
use Tourze\TLSAlert\AlertListenerInterface;

class AlertHandlerInterfaceTest extends TestCase
{
    public function test_interface_exists(): void
    {
        $this->assertTrue(interface_exists(AlertHandlerInterface::class));
    }

    public function test_interface_has_correct_methods(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $methods = $reflection->getMethods();
        
        $expectedMethods = [
            'handleAlert',
            'sendAlert',
            'addListener',
            'removeListener',
            'isConnectionClosed',
            'getLastReceivedAlert',
            'getLastSentAlert'
        ];
        
        $actualMethods = array_map(fn(ReflectionMethod $method) => $method->getName(), $methods);
        
        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains($expectedMethod, $actualMethods, "接口应该包含方法: {$expectedMethod}");
        }
        
        $this->assertCount(count($expectedMethods), $methods, '接口方法数量应该匹配');
    }

    public function test_handleAlert_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $method = $reflection->getMethod('handleAlert');
        
        $this->assertTrue($method->isPublic());
        $this->assertSame('void', $method->getReturnType()?->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('alert', $parameters[0]->getName());
        $this->assertSame(Alert::class, $parameters[0]->getType()?->getName());
    }

    public function test_sendAlert_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $method = $reflection->getMethod('sendAlert');
        
        $this->assertTrue($method->isPublic());
        $this->assertSame('void', $method->getReturnType()?->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('alert', $parameters[0]->getName());
        $this->assertSame(Alert::class, $parameters[0]->getType()?->getName());
    }

    public function test_addListener_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $method = $reflection->getMethod('addListener');
        
        $this->assertTrue($method->isPublic());
        $this->assertSame('void', $method->getReturnType()?->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('listener', $parameters[0]->getName());
        $this->assertSame(AlertListenerInterface::class, $parameters[0]->getType()?->getName());
    }

    public function test_removeListener_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $method = $reflection->getMethod('removeListener');
        
        $this->assertTrue($method->isPublic());
        $this->assertSame('void', $method->getReturnType()?->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('listener', $parameters[0]->getName());
        $this->assertSame(AlertListenerInterface::class, $parameters[0]->getType()?->getName());
    }

    public function test_isConnectionClosed_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $method = $reflection->getMethod('isConnectionClosed');
        
        $this->assertTrue($method->isPublic());
        $this->assertSame('bool', $method->getReturnType()?->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    public function test_getLastReceivedAlert_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $method = $reflection->getMethod('getLastReceivedAlert');
        
        $this->assertTrue($method->isPublic());
        $returnType = $method->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertSame(Alert::class, $returnType->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    public function test_getLastSentAlert_method_signature(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $method = $reflection->getMethod('getLastSentAlert');
        
        $this->assertTrue($method->isPublic());
        $returnType = $method->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertSame(Alert::class, $returnType->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    public function test_interface_can_be_implemented(): void
    {
        $implementation = new class implements AlertHandlerInterface {
            public function handleAlert(Alert $alert): void {}
            public function sendAlert(Alert $alert): void {}
            public function addListener(AlertListenerInterface $listener): void {}
            public function removeListener(AlertListenerInterface $listener): void {}
            public function isConnectionClosed(): bool { return false; }
            public function getLastReceivedAlert(): ?Alert { return null; }
            public function getLastSentAlert(): ?Alert { return null; }
        };
        
        $this->assertInstanceOf(AlertHandlerInterface::class, $implementation);
    }

    public function test_interface_methods_documentation(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        
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
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $this->assertSame('Tourze\\TLSAlert', $reflection->getNamespaceName());
    }

    public function test_interface_is_interface_not_class(): void
    {
        $reflection = new ReflectionClass(AlertHandlerInterface::class);
        $this->assertTrue($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
        $this->assertFalse($reflection->isEnum());
    }
} 