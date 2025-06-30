<?php

namespace Tourze\TLSAlert;

use Tourze\TLSAlert\Exception\AlertException;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;

/**
 * TLS警告消息类
 *
 * 表示一个TLS警告消息，包含警告级别和描述
 * 参考: RFC 5246 Section 7.2, RFC 8446 Section 6
 */
readonly class Alert
{
    public function __construct(
        public AlertLevel $level,
        public AlertDescription $description
    ) {
    }

    /**
     * 从二进制数据创建Alert对象
     *
     * @param string $data 二进制数据（至少2字节）
     * @return self
     * @throws AlertException 当数据格式不正确时
     */
    public static function fromBinary(string $data): self
    {
        if (strlen($data) < 2) {
            throw new AlertException('警告消息数据长度不足，至少需要2字节');
        }

        $level = AlertLevel::fromInt(ord($data[0]));
        if ($level === null) {
            throw new AlertException(sprintf('未知的警告级别: %d', ord($data[0])));
        }

        $description = AlertDescription::fromInt(ord($data[1]));
        if ($description === null) {
            throw new AlertException(sprintf('未知的警告描述: %d', ord($data[1])));
        }

        return new self($level, $description);
    }

    /**
     * 将Alert对象转换为二进制数据
     *
     * @return string 2字节的二进制数据
     */
    public function toBinary(): string
    {
        return chr($this->level->value) . chr($this->description->value);
    }

    /**
     * 检查是否为致命警告
     *
     * @return bool
     */
    public function isFatal(): bool
    {
        return $this->level === AlertLevel::FATAL;
    }

    /**
     * 检查是否为警告级别
     *
     * @return bool
     */
    public function isWarning(): bool
    {
        return $this->level === AlertLevel::WARNING;
    }

    /**
     * 检查是否为关闭通知
     *
     * @return bool
     */
    public function isCloseNotify(): bool
    {
        return $this->description === AlertDescription::CLOSE_NOTIFY;
    }

    /**
     * 获取人类可读的警告描述
     *
     * @return string
     */
    public function getHumanReadableDescription(): string
    {
        return match ($this->description) {
            AlertDescription::CLOSE_NOTIFY => '连接正常关闭',
            AlertDescription::UNEXPECTED_MESSAGE => '收到意外消息',
            AlertDescription::BAD_RECORD_MAC => '记录MAC验证失败',
            AlertDescription::DECRYPTION_FAILED => '解密操作失败',
            AlertDescription::RECORD_OVERFLOW => '记录长度超出限制',
            AlertDescription::DECOMPRESSION_FAILURE => '数据解压缩失败',
            AlertDescription::HANDSHAKE_FAILURE => '无法协商安全参数',
            AlertDescription::BAD_CERTIFICATE => '证书格式错误或损坏',
            AlertDescription::UNSUPPORTED_CERTIFICATE => '不支持的证书类型',
            AlertDescription::CERTIFICATE_REVOKED => '证书已被吊销',
            AlertDescription::CERTIFICATE_EXPIRED => '证书已过期',
            AlertDescription::CERTIFICATE_UNKNOWN => '证书处理过程中出现未知错误',
            AlertDescription::ILLEGAL_PARAMETER => '协议参数超出有效范围',
            AlertDescription::UNKNOWN_CA => '无法验证证书颁发机构',
            AlertDescription::ACCESS_DENIED => '客户端证书被拒绝',
            AlertDescription::DECODE_ERROR => '消息解码失败',
            AlertDescription::DECRYPT_ERROR => '握手消息解密失败',
            AlertDescription::PROTOCOL_VERSION => '不支持的协议版本',
            AlertDescription::INSUFFICIENT_SECURITY => '安全强度不足',
            AlertDescription::INTERNAL_ERROR => '内部处理错误',
            AlertDescription::INAPPROPRIATE_FALLBACK => '不当的协议版本降级',
            AlertDescription::USER_CANCELED => '用户取消了握手',
            AlertDescription::MISSING_EXTENSION => '缺少必需的扩展',
            AlertDescription::UNSUPPORTED_EXTENSION => '收到不支持的扩展',
            AlertDescription::CERTIFICATE_UNOBTAINABLE => '无法获取证书',
            AlertDescription::UNRECOGNIZED_NAME => '无法识别的服务器名称',
            AlertDescription::BAD_CERTIFICATE_STATUS_RESPONSE => '证书状态响应无效',
            AlertDescription::BAD_CERTIFICATE_HASH_VALUE => '证书哈希值不匹配',
            AlertDescription::UNKNOWN_PSK_IDENTITY => '未知的预共享密钥标识',
            AlertDescription::CERTIFICATE_REQUIRED => '需要客户端证书',
            AlertDescription::NO_APPLICATION_PROTOCOL => '无可用的应用层协议',
            default => '未知警告: ' . $this->description->asString(),
        };
    }

    /**
     * 转换为字符串表示
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            '[%s] %s: %s',
            $this->level->asString(),
            $this->description->asString(),
            $this->getHumanReadableDescription()
        );
    }

    /**
     * 转换为数组表示
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level->value,
            'level_name' => $this->level->asString(),
            'description' => $this->description->value,
            'description_name' => $this->description->asString(),
            'human_readable' => $this->getHumanReadableDescription(),
            'is_fatal' => $this->isFatal(),
            'is_close_notify' => $this->isCloseNotify(),
        ];
    }
} 