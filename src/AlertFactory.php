<?php

namespace Tourze\TLSAlert;

use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;

/**
 * TLS警告工厂类
 *
 * 提供创建常见警告的便捷方法
 */
final class AlertFactory
{
    /**
     * 创建关闭通知
     */
    public static function createCloseNotify(): Alert
    {
        return new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
    }

    /**
     * 创建握手失败警告
     */
    public static function createHandshakeFailure(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
    }

    /**
     * 创建证书过期警告
     */
    public static function createCertificateExpired(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::CERTIFICATE_EXPIRED);
    }

    /**
     * 创建证书吊销警告
     */
    public static function createCertificateRevoked(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::CERTIFICATE_REVOKED);
    }

    /**
     * 创建证书错误警告
     */
    public static function createBadCertificate(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::BAD_CERTIFICATE);
    }

    /**
     * 创建不支持的证书警告
     */
    public static function createUnsupportedCertificate(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::UNSUPPORTED_CERTIFICATE);
    }

    /**
     * 创建未知CA警告
     */
    public static function createUnknownCA(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::UNKNOWN_CA);
    }

    /**
     * 创建访问拒绝警告
     */
    public static function createAccessDenied(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::ACCESS_DENIED);
    }

    /**
     * 创建解码错误警告
     */
    public static function createDecodeError(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::DECODE_ERROR);
    }

    /**
     * 创建解密错误警告
     */
    public static function createDecryptError(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::DECRYPT_ERROR);
    }

    /**
     * 创建协议版本不支持警告
     */
    public static function createProtocolVersion(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::PROTOCOL_VERSION);
    }

    /**
     * 创建安全性不足警告
     */
    public static function createInsufficientSecurity(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::INSUFFICIENT_SECURITY);
    }

    /**
     * 创建内部错误警告
     */
    public static function createInternalError(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::INTERNAL_ERROR);
    }

    /**
     * 创建用户取消警告
     */
    public static function createUserCanceled(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::USER_CANCELED);
    }

    /**
     * 创建不支持的扩展警告
     */
    public static function createUnsupportedExtension(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::UNSUPPORTED_EXTENSION);
    }

    /**
     * 创建缺少扩展警告
     */
    public static function createMissingExtension(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::MISSING_EXTENSION);
    }

    /**
     * 创建无法识别的名称警告
     */
    public static function createUnrecognizedName(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::UNRECOGNIZED_NAME);
    }

    /**
     * 创建证书状态响应错误警告
     */
    public static function createBadCertificateStatusResponse(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::BAD_CERTIFICATE_STATUS_RESPONSE);
    }

    /**
     * 创建未知PSK身份警告
     */
    public static function createUnknownPskIdentity(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::UNKNOWN_PSK_IDENTITY);
    }

    /**
     * 创建需要证书警告
     */
    public static function createCertificateRequired(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::CERTIFICATE_REQUIRED);
    }

    /**
     * 创建无应用协议警告
     */
    public static function createNoApplicationProtocol(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::NO_APPLICATION_PROTOCOL);
    }

    /**
     * 创建记录MAC错误警告
     */
    public static function createBadRecordMac(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::BAD_RECORD_MAC);
    }

    /**
     * 创建记录溢出警告
     */
    public static function createRecordOverflow(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::RECORD_OVERFLOW);
    }

    /**
     * 创建意外消息警告
     */
    public static function createUnexpectedMessage(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::UNEXPECTED_MESSAGE);
    }

    /**
     * 创建非法参数警告
     */
    public static function createIllegalParameter(): Alert
    {
        return new Alert(AlertLevel::FATAL, AlertDescription::ILLEGAL_PARAMETER);
    }

    /**
     * 根据错误类型创建对应的警告
     *
     * @param string $errorType 错误类型
     * @return Alert
     * @throws AlertException 当错误类型无法识别时
     */
    public static function createFromErrorType(string $errorType): Alert
    {
        return match ($errorType) {
            'handshake_failure' => self::createHandshakeFailure(),
            'certificate_expired' => self::createCertificateExpired(),
            'certificate_revoked' => self::createCertificateRevoked(),
            'bad_certificate' => self::createBadCertificate(),
            'unsupported_certificate' => self::createUnsupportedCertificate(),
            'unknown_ca' => self::createUnknownCA(),
            'access_denied' => self::createAccessDenied(),
            'decode_error' => self::createDecodeError(),
            'decrypt_error' => self::createDecryptError(),
            'protocol_version' => self::createProtocolVersion(),
            'insufficient_security' => self::createInsufficientSecurity(),
            'internal_error' => self::createInternalError(),
            'user_canceled' => self::createUserCanceled(),
            'unsupported_extension' => self::createUnsupportedExtension(),
            'missing_extension' => self::createMissingExtension(),
            'unrecognized_name' => self::createUnrecognizedName(),
            'bad_certificate_status_response' => self::createBadCertificateStatusResponse(),
            'unknown_psk_identity' => self::createUnknownPskIdentity(),
            'certificate_required' => self::createCertificateRequired(),
            'no_application_protocol' => self::createNoApplicationProtocol(),
            'bad_record_mac' => self::createBadRecordMac(),
            'record_overflow' => self::createRecordOverflow(),
            'unexpected_message' => self::createUnexpectedMessage(),
            'illegal_parameter' => self::createIllegalParameter(),
            'close_notify' => self::createCloseNotify(),
            default => throw new AlertException("未知的错误类型: {$errorType}"),
        };
    }
} 