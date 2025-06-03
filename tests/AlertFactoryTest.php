<?php

namespace Tourze\TLSAlert\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\TLSAlert\AlertException;
use Tourze\TLSAlert\AlertFactory;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;

class AlertFactoryTest extends TestCase
{
    public function test_createCloseNotify(): void
    {
        $alert = AlertFactory::createCloseNotify();
        
        $this->assertSame(AlertLevel::WARNING, $alert->level);
        $this->assertSame(AlertDescription::CLOSE_NOTIFY, $alert->description);
        $this->assertTrue($alert->isCloseNotify());
    }

    public function test_createHandshakeFailure(): void
    {
        $alert = AlertFactory::createHandshakeFailure();
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::HANDSHAKE_FAILURE, $alert->description);
        $this->assertTrue($alert->isFatal());
    }

    public function test_createCertificateExpired(): void
    {
        $alert = AlertFactory::createCertificateExpired();
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::CERTIFICATE_EXPIRED, $alert->description);
        $this->assertTrue($alert->isFatal());
    }

    public function test_createBadCertificate(): void
    {
        $alert = AlertFactory::createBadCertificate();
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::BAD_CERTIFICATE, $alert->description);
        $this->assertTrue($alert->isFatal());
    }

    public function test_createUnknownCA(): void
    {
        $alert = AlertFactory::createUnknownCA();
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::UNKNOWN_CA, $alert->description);
        $this->assertTrue($alert->isFatal());
    }

    public function test_createDecodeError(): void
    {
        $alert = AlertFactory::createDecodeError();
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::DECODE_ERROR, $alert->description);
        $this->assertTrue($alert->isFatal());
    }

    public function test_createProtocolVersion(): void
    {
        $alert = AlertFactory::createProtocolVersion();
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::PROTOCOL_VERSION, $alert->description);
        $this->assertTrue($alert->isFatal());
    }

    public function test_createInternalError(): void
    {
        $alert = AlertFactory::createInternalError();
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::INTERNAL_ERROR, $alert->description);
        $this->assertTrue($alert->isFatal());
    }

    public function test_createFromErrorType_with_valid_types(): void
    {
        $testCases = [
            'handshake_failure' => AlertDescription::HANDSHAKE_FAILURE,
            'certificate_expired' => AlertDescription::CERTIFICATE_EXPIRED,
            'bad_certificate' => AlertDescription::BAD_CERTIFICATE,
            'unknown_ca' => AlertDescription::UNKNOWN_CA,
            'decode_error' => AlertDescription::DECODE_ERROR,
            'protocol_version' => AlertDescription::PROTOCOL_VERSION,
            'internal_error' => AlertDescription::INTERNAL_ERROR,
            'close_notify' => AlertDescription::CLOSE_NOTIFY,
        ];

        foreach ($testCases as $errorType => $expectedDescription) {
            $alert = AlertFactory::createFromErrorType($errorType);
            $this->assertSame($expectedDescription, $alert->description);
        }
    }

    public function test_createFromErrorType_with_close_notify_creates_warning(): void
    {
        $alert = AlertFactory::createFromErrorType('close_notify');
        
        $this->assertSame(AlertLevel::WARNING, $alert->level);
        $this->assertSame(AlertDescription::CLOSE_NOTIFY, $alert->description);
    }

    public function test_createFromErrorType_with_fatal_errors_creates_fatal(): void
    {
        $alert = AlertFactory::createFromErrorType('handshake_failure');
        
        $this->assertSame(AlertLevel::FATAL, $alert->level);
        $this->assertSame(AlertDescription::HANDSHAKE_FAILURE, $alert->description);
    }

    public function test_createFromErrorType_with_unknown_type_throws_exception(): void
    {
        $this->expectException(AlertException::class);
        $this->expectExceptionMessage('未知的错误类型: unknown_error_type');
        
        AlertFactory::createFromErrorType('unknown_error_type');
    }

    public function test_all_factory_methods_create_valid_alerts(): void
    {
        $factoryMethods = [
            'createCloseNotify',
            'createHandshakeFailure',
            'createCertificateExpired',
            'createCertificateRevoked',
            'createBadCertificate',
            'createUnsupportedCertificate',
            'createUnknownCA',
            'createAccessDenied',
            'createDecodeError',
            'createDecryptError',
            'createProtocolVersion',
            'createInsufficientSecurity',
            'createInternalError',
            'createUserCanceled',
            'createUnsupportedExtension',
            'createMissingExtension',
            'createUnrecognizedName',
            'createBadCertificateStatusResponse',
            'createUnknownPskIdentity',
            'createCertificateRequired',
            'createNoApplicationProtocol',
            'createBadRecordMac',
            'createRecordOverflow',
            'createUnexpectedMessage',
            'createIllegalParameter',
        ];

        foreach ($factoryMethods as $method) {
            $alert = AlertFactory::$method();
            $this->assertInstanceOf(\Tourze\TLSAlert\Alert::class, $alert);
            $this->assertNotNull($alert->level);
            $this->assertNotNull($alert->description);
        }
    }
} 