<?php

namespace Tourze\TLSAlert\Tests\Listener;

use PHPUnit\Framework\TestCase;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\Listener\StatisticsAlertListener;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;

class StatisticsAlertListenerTest extends TestCase
{
    private StatisticsAlertListener $listener;

    protected function setUp(): void
    {
        $this->listener = new StatisticsAlertListener();
    }

    public function test_initial_statistics_are_zero(): void
    {
        $this->assertSame(0, $this->listener->getTotalAlertsReceived());
        $this->assertSame(0, $this->listener->getTotalAlertsSent());
        $this->assertSame(0, $this->listener->getFatalAlertsReceived());
        $this->assertSame(0, $this->listener->getFatalAlertsSent());
        $this->assertSame(0, $this->listener->getWarningAlertsReceived());
        $this->assertSame(0, $this->listener->getWarningAlertsSent());
        $this->assertSame(0, $this->listener->getConnectionsClosedCount());
        $this->assertEmpty($this->listener->getReceivedAlertsByType());
        $this->assertEmpty($this->listener->getSentAlertsByType());
    }

    public function test_onAlertReceived_increments_counters(): void
    {
        $fatalAlert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $warningAlert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);

        $this->listener->onAlertReceived($fatalAlert);
        $this->listener->onAlertReceived($warningAlert);

        $this->assertSame(2, $this->listener->getTotalAlertsReceived());
        $this->assertSame(1, $this->listener->getFatalAlertsReceived());
        $this->assertSame(1, $this->listener->getWarningAlertsReceived());
    }

    public function test_onAlertSent_increments_counters(): void
    {
        $fatalAlert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $warningAlert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);

        $this->listener->onAlertSent($fatalAlert);
        $this->listener->onAlertSent($warningAlert);

        $this->assertSame(2, $this->listener->getTotalAlertsSent());
        $this->assertSame(1, $this->listener->getFatalAlertsSent());
        $this->assertSame(1, $this->listener->getWarningAlertsSent());
    }

    public function test_onConnectionClosed_increments_counter(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);

        $this->listener->onConnectionClosed($alert);
        $this->listener->onConnectionClosed($alert);

        $this->assertSame(2, $this->listener->getConnectionsClosedCount());
    }

    public function test_received_alerts_by_type_tracking(): void
    {
        $handshakeFailure = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $closeNotify = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);

        $this->listener->onAlertReceived($handshakeFailure);
        $this->listener->onAlertReceived($handshakeFailure);
        $this->listener->onAlertReceived($closeNotify);

        $receivedByType = $this->listener->getReceivedAlertsByType();

        $this->assertSame(2, $receivedByType['handshake_failure']);
        $this->assertSame(1, $receivedByType['close_notify']);
    }

    public function test_sent_alerts_by_type_tracking(): void
    {
        $handshakeFailure = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $closeNotify = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);

        $this->listener->onAlertSent($handshakeFailure);
        $this->listener->onAlertSent($closeNotify);
        $this->listener->onAlertSent($closeNotify);

        $sentByType = $this->listener->getSentAlertsByType();

        $this->assertSame(1, $sentByType['handshake_failure']);
        $this->assertSame(2, $sentByType['close_notify']);
    }

    public function test_getStatistics_returns_complete_data(): void
    {
        $fatalAlert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);
        $warningAlert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);

        $this->listener->onAlertReceived($fatalAlert);
        $this->listener->onAlertSent($warningAlert);
        $this->listener->onConnectionClosed($fatalAlert);

        $statistics = $this->listener->getStatistics();

        $this->assertSame(1, $statistics['total_alerts_received']);
        $this->assertSame(1, $statistics['total_alerts_sent']);
        $this->assertSame(1, $statistics['fatal_alerts_received']);
        $this->assertSame(0, $statistics['fatal_alerts_sent']);
        $this->assertSame(0, $statistics['warning_alerts_received']);
        $this->assertSame(1, $statistics['warning_alerts_sent']);
        $this->assertSame(1, $statistics['connections_closed_count']);
        $this->assertArrayHasKey('received_alerts_by_type', $statistics);
        $this->assertArrayHasKey('sent_alerts_by_type', $statistics);
    }

    public function test_reset_clears_all_statistics(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);

        // 添加一些统计数据
        $this->listener->onAlertReceived($alert);
        $this->listener->onAlertSent($alert);
        $this->listener->onConnectionClosed($alert);

        // 验证数据存在
        $this->assertGreaterThan(0, $this->listener->getTotalAlertsReceived());

        // 重置
        $this->listener->reset();

        // 验证所有数据都被清零
        $this->assertSame(0, $this->listener->getTotalAlertsReceived());
        $this->assertSame(0, $this->listener->getTotalAlertsSent());
        $this->assertSame(0, $this->listener->getFatalAlertsReceived());
        $this->assertSame(0, $this->listener->getFatalAlertsSent());
        $this->assertSame(0, $this->listener->getWarningAlertsReceived());
        $this->assertSame(0, $this->listener->getWarningAlertsSent());
        $this->assertSame(0, $this->listener->getConnectionsClosedCount());
        $this->assertEmpty($this->listener->getReceivedAlertsByType());
        $this->assertEmpty($this->listener->getSentAlertsByType());
    }

    public function test_multiple_alerts_of_same_type_increment_correctly(): void
    {
        $alert = new Alert(AlertLevel::FATAL, AlertDescription::HANDSHAKE_FAILURE);

        for ($i = 0; $i < 5; $i++) {
            $this->listener->onAlertReceived($alert);
        }

        $this->assertSame(5, $this->listener->getTotalAlertsReceived());
        $this->assertSame(5, $this->listener->getFatalAlertsReceived());
        $this->assertSame(0, $this->listener->getWarningAlertsReceived());

        $receivedByType = $this->listener->getReceivedAlertsByType();
        $this->assertSame(5, $receivedByType['handshake_failure']);
    }
} 