<?php

namespace Tourze\TLSAlert\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Tourze\TLSAlert\AlertException;

class AlertExceptionTest extends TestCase
{
    public function test_constructor_with_message_only(): void
    {
        $message = '测试错误消息';
        $exception = new AlertException($message);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function test_constructor_with_message_and_code(): void
    {
        $message = '带代码的错误消息';
        $code = 12345;
        $exception = new AlertException($message, $code);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function test_constructor_with_message_code_and_previous(): void
    {
        $message = '链式异常消息';
        $code = 999;
        $previous = new Exception('原始异常');
        $exception = new AlertException($message, $code, $previous);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_inherits_from_exception(): void
    {
        $exception = new AlertException('测试');
        
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function test_constructor_with_empty_message(): void
    {
        $exception = new AlertException('');
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function test_constructor_with_negative_code(): void
    {
        $exception = new AlertException('测试', -1);
        
        $this->assertSame(-1, $exception->getCode());
    }

    public function test_constructor_with_zero_code(): void
    {
        $exception = new AlertException('测试', 0);
        
        $this->assertSame(0, $exception->getCode());
    }

    public function test_exception_can_be_thrown_and_caught(): void
    {
        $message = '可抛出异常';
        
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage($message);
        
        throw new AlertException($message);
    }

    public function test_exception_preserves_stack_trace(): void
    {
        try {
            $this->throwAlertException();
        } catch (AlertException $e) {
            $trace = $e->getTrace();
            $this->assertIsArray($trace);
            $this->assertNotEmpty($trace);
            $this->assertArrayHasKey('function', $trace[0]);
            $this->assertSame('throwAlertException', $trace[0]['function']);
        }
    }

    public function test_exception_with_unicode_message(): void
    {
        $message = '包含unicode字符的错误: 🚨❌💥';
        $exception = new AlertException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function test_exception_with_very_long_message(): void
    {
        $message = str_repeat('A', 10000);
        $exception = new AlertException($message);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(10000, strlen($exception->getMessage()));
    }

    public function test_exception_toString_includes_message(): void
    {
        $message = '字符串表示测试';
        $exception = new AlertException($message);
        $string = (string) $exception;
        
        $this->assertStringContainsString($message, $string);
        $this->assertStringContainsString('AlertException', $string);
    }

    /**
     * 辅助方法：抛出 AlertException 用于测试堆栈跟踪
     */
    private function throwAlertException(): void
    {
        throw new AlertException('堆栈跟踪测试');
    }
} 