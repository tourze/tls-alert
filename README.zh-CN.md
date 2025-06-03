# TLS 警告协议实现

基于 RFC 5246 和 RFC 8446 标准的 PHP TLS 警告协议实现。

## 特性

- 完整的 TLS 警告消息处理
- 支持所有标准警告类型和级别
- 可扩展的监听器系统用于警告监控
- 内置日志记录和统计收集
- 与 `tls-common` 和 `tls-record` 包完全集成
- 全面的测试覆盖

## 安装

```bash
composer require tourze/tls-alert
```

## 基本用法

### 创建警告消息

```php
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertFactory;
use Tourze\TLSCommon\Protocol\AlertLevel;
use Tourze\TLSCommon\Protocol\AlertDescription;

// 手动创建警告
$alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);

// 或使用工厂方法创建常见警告
$closeNotify = AlertFactory::createCloseNotify();
$handshakeFailure = AlertFactory::createHandshakeFailure();
$certificateExpired = AlertFactory::createCertificateExpired();

// 从错误类型字符串创建
$alert = AlertFactory::createFromErrorType('handshake_failure');
```

### 警告处理器

```php
use Tourze\TLSAlert\AlertHandler;
use Tourze\TLSRecord\RecordProtocol;
use Psr\Log\LoggerInterface;

// 使用记录协议和可选的日志记录器初始化
$alertHandler = new AlertHandler($recordProtocol, $logger);

// 处理接收到的警告
$alertHandler->handleAlert($alert);

// 发送警告
$alertHandler->sendAlert($alert);

// 便捷方法
$alertHandler->sendCloseNotify();
$alertHandler->sendFatalAlert(AlertDescription::CERTIFICATE_EXPIRED);

// 检查连接状态
if ($alertHandler->isConnectionClosed()) {
    // 处理已关闭的连接
}
```

### 警告监听器

```php
use Tourze\TLSAlert\Listener\LoggingAlertListener;
use Tourze\TLSAlert\Listener\StatisticsAlertListener;

// 添加日志监听器
$loggingListener = new LoggingAlertListener($logger);
$alertHandler->addListener($loggingListener);

// 添加统计监听器
$statsListener = new StatisticsAlertListener();
$alertHandler->addListener($statsListener);

// 获取统计信息
$stats = $statsListener->getStatistics();
echo "总接收警告数: " . $stats['total_alerts_received'];
echo "致命警告数: " . $stats['fatal_alerts_received'];
```

### 自定义警告监听器

```php
use Tourze\TLSAlert\AlertListenerInterface;
use Tourze\TLSAlert\Alert;

class CustomAlertListener implements AlertListenerInterface
{
    public function onAlertReceived(Alert $alert): void
    {
        // 处理接收到的警告
        if ($alert->isFatal()) {
            // 处理致命警告
        }
    }

    public function onAlertSent(Alert $alert): void
    {
        // 处理发送的警告
    }

    public function onConnectionClosed(Alert $alert): void
    {
        // 处理连接关闭
    }
}

$customListener = new CustomAlertListener();
$alertHandler->addListener($customListener);
```

### 二进制序列化

```php
// 将警告转换为二进制格式
$binary = $alert->toBinary();

// 从二进制数据创建警告
$alert = Alert::fromBinary($binary);

// 获取人类可读的信息
echo $alert->getHumanReadableDescription();
echo (string) $alert; // 完整格式化字符串
$array = $alert->toArray(); // 数组表示
```

## 警告类型

该包支持所有标准 TLS 警告描述：

- `CLOSE_NOTIFY` - 正常连接关闭
- `UNEXPECTED_MESSAGE` - 收到意外消息
- `BAD_RECORD_MAC` - MAC 验证失败
- `HANDSHAKE_FAILURE` - 握手协商失败
- `CERTIFICATE_EXPIRED` - 证书已过期
- `CERTIFICATE_REVOKED` - 证书已被吊销
- `UNKNOWN_CA` - 未知证书颁发机构
- `ACCESS_DENIED` - 访问被拒绝
- `DECODE_ERROR` - 消息解码失败
- `DECRYPT_ERROR` - 解密失败
- `PROTOCOL_VERSION` - 不支持的协议版本
- `INSUFFICIENT_SECURITY` - 安全级别不足
- `INTERNAL_ERROR` - 内部处理错误
- 以及更多...

## 警告级别

- `WARNING` - 非致命警告（通常只有 `CLOSE_NOTIFY`）
- `FATAL` - 致命警告，需要终止连接

## 集成

该包与其他 TLS 包无缝集成：

- `tourze/tls-common` - 提供协议枚举和常量
- `tourze/tls-record` - 处理记录层传输

## 测试

```bash
vendor/bin/phpunit tests
```

## 许可证

MIT 许可证。详情请参阅 LICENSE 文件。
