# TLS Alert Protocol Implementation

A PHP implementation of the TLS Alert Protocol as defined in RFC 5246 and RFC 8446.

## Features

- Complete TLS Alert message handling
- Support for all standard alert types and levels
- Extensible listener system for alert monitoring
- Built-in logging and statistics collection
- Full integration with `tls-common` and `tls-record` packages
- Comprehensive test coverage

## Installation

```bash
composer require tourze/tls-alert
```

## Basic Usage

### Creating Alert Messages

```php
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertFactory;
use Tourze\TLSCommon\Protocol\AlertLevel;
use Tourze\TLSCommon\Protocol\AlertDescription;

// Create alerts manually
$alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);

// Or use the factory for common alerts
$closeNotify = AlertFactory::createCloseNotify();
$handshakeFailure = AlertFactory::createHandshakeFailure();
$certificateExpired = AlertFactory::createCertificateExpired();

// Create from error type string
$alert = AlertFactory::createFromErrorType('handshake_failure');
```

### Alert Handler

```php
use Tourze\TLSAlert\AlertHandler;
use Tourze\TLSRecord\RecordProtocol;
use Psr\Log\LoggerInterface;

// Initialize with record protocol and optional logger
$alertHandler = new AlertHandler($recordProtocol, $logger);

// Handle incoming alerts
$alertHandler->handleAlert($alert);

// Send alerts
$alertHandler->sendAlert($alert);

// Convenience methods
$alertHandler->sendCloseNotify();
$alertHandler->sendFatalAlert(AlertDescription::CERTIFICATE_EXPIRED);

// Check connection status
if ($alertHandler->isConnectionClosed()) {
    // Handle closed connection
}
```

### Alert Listeners

```php
use Tourze\TLSAlert\Listener\LoggingAlertListener;
use Tourze\TLSAlert\Listener\StatisticsAlertListener;

// Add logging listener
$loggingListener = new LoggingAlertListener($logger);
$alertHandler->addListener($loggingListener);

// Add statistics listener
$statsListener = new StatisticsAlertListener();
$alertHandler->addListener($statsListener);

// Get statistics
$stats = $statsListener->getStatistics();
echo "Total alerts received: " . $stats['total_alerts_received'];
echo "Fatal alerts: " . $stats['fatal_alerts_received'];
```

### Custom Alert Listeners

```php
use Tourze\TLSAlert\AlertListenerInterface;
use Tourze\TLSAlert\Alert;

class CustomAlertListener implements AlertListenerInterface
{
    public function onAlertReceived(Alert $alert): void
    {
        // Handle received alert
        if ($alert->isFatal()) {
            // Handle fatal alert
        }
    }

    public function onAlertSent(Alert $alert): void
    {
        // Handle sent alert
    }

    public function onConnectionClosed(Alert $alert): void
    {
        // Handle connection closure
    }
}

$customListener = new CustomAlertListener();
$alertHandler->addListener($customListener);
```

### Binary Serialization

```php
// Convert alert to binary format
$binary = $alert->toBinary();

// Create alert from binary data
$alert = Alert::fromBinary($binary);

// Get human-readable information
echo $alert->getHumanReadableDescription();
echo (string) $alert; // Full formatted string
$array = $alert->toArray(); // Array representation
```

## Alert Types

The package supports all standard TLS alert descriptions:

- `CLOSE_NOTIFY` - Normal connection closure
- `UNEXPECTED_MESSAGE` - Unexpected message received
- `BAD_RECORD_MAC` - MAC verification failed
- `HANDSHAKE_FAILURE` - Handshake negotiation failed
- `CERTIFICATE_EXPIRED` - Certificate has expired
- `CERTIFICATE_REVOKED` - Certificate has been revoked
- `UNKNOWN_CA` - Unknown certificate authority
- `ACCESS_DENIED` - Access denied
- `DECODE_ERROR` - Message decoding failed
- `DECRYPT_ERROR` - Decryption failed
- `PROTOCOL_VERSION` - Unsupported protocol version
- `INSUFFICIENT_SECURITY` - Security level insufficient
- `INTERNAL_ERROR` - Internal processing error
- And many more...

## Alert Levels

- `WARNING` - Non-fatal alerts (typically only `CLOSE_NOTIFY`)
- `FATAL` - Fatal alerts that require connection termination

## Integration

This package integrates seamlessly with other TLS packages:

- `tourze/tls-common` - Provides protocol enums and constants
- `tourze/tls-record` - Handles record layer transmission

## Testing

```bash
vendor/bin/phpunit tests
```

## License

MIT License. See LICENSE file for details.
