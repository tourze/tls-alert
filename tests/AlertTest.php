<?php

namespace Tourze\TLSAlert\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertException;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;

class AlertTest extends TestCase
{
    public function test_construct_with_valid_parameters(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::HANDSHAKE_FAILURE, $alert->description);
    }

    public function test_fromBinary_with_valid_data(): void
    {
        $data = chr(AlertLevel::FATAL->value) . chr(AlertDescription::HANDSHAKE_FAILURE->value);
        $alert = Alert::fromBinary($data);
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::HANDSHAKE_FAILURE, $alert->description);
    }

    public function test_fromBinary_with_insufficient_data(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('警告消息数据长度不足，至少需要2字节');
        
        Alert::fromBinary('x');
    }

    public function test_fromBinary_with_unknown_level(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的警告级别: 255');
        
        Alert::fromBinary(chr(255) . chr(AlertDescription::HANDSHAKE_FAILURE->value));
    }

    public function test_fromBinary_with_unknown_description(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的警告描述: 255');
        
        Alert::fromBinary(chr(AlertLevel::FATAL->value) . chr(255));
    }

    public function test_toBinary(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $binary = $alert->toBinary();
        
        $this->assertSame(2, strlen($binary));
        $this->assertSame(AlertLevel::FATAL->value, ord($binary[0]));
        $this->assertSame(AlertDescription::HANDSHAKE_FAILURE->value, ord($binary[1]));
    }

    public function test_isFatal_returns_true_for_fatal_alert(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $this->assertTrue($alert->isFatal());
    }

    public function test_isFatal_returns_false_for_warning_alert(): void
    {
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        $this->assertFalse($alert->isFatal());
    }

    public function test_isWarning_returns_true_for_warning_alert(): void
    {
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        $this->assertTrue($alert->isWarning());
    }

    public function test_isWarning_returns_false_for_fatal_alert(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $this->assertFalse($alert->isWarning());
    }

    public function test_isCloseNotify_returns_true_for_close_notify(): void
    {
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        $this->assertTrue($alert->isCloseNotify());
    }

    public function test_isCloseNotify_returns_false_for_other_alerts(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $this->assertFalse($alert->isCloseNotify());
    }

    public function test_getHumanReadableDescription_for_close_notify(): void
    {
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        $this->assertSame('连接正常关闭', $alert->getHumanReadableDescription());
    }

    public function test_getHumanReadableDescription_for_handshake_failure(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $this->assertSame('无法协商安全参数', $alert->getHumanReadableDescription());
    }

    public function test_toString(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $expected = '[致命] handshake_failure: 无法协商安全参数';
        $this->assertSame($expected, (string) $alert);
    }

    public function test_toArray(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $array = $alert->toArray();
        
        $this->assertSame(AlertLevel::FATAL->value, $array['level']);
        $this->assertSame('致命', $array['level_name']);
        $this->assertSame(AlertDescription::HANDSHAKE_FAILURE->value, $array['description']);
        $this->assertSame('handshake_failure', $array['description_name']);
        $this->assertSame('无法协商安全参数', $array['human_readable']);
        $this->assertTrue($array['is_fatal']);
        $this->assertFalse($array['is_close_notify']);
    }

    public function test_binary_roundtrip(): void
    {
        $originalAlert = new Alert(AlertLevel::FATAL, AlertDescription::CERTIFICATE_EXPIRED);
        $binary = $originalAlert->toBinary();
        $restoredAlert = Alert::fromBinary($binary);
        
        $this->assertSame($originalAlert->level, $restoredAlert->level);
        $this->assertSame($originalAlert->description, $restoredAlert->description);
    }
} 