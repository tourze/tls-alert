<?php

namespace Tourze\TLSAlert\Listener;

use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertListenerInterface;

/**
 * 警告统计监听器
 *
 * 收集警告的统计信息
 */
class StatisticsAlertListener implements AlertListenerInterface
{
    private int $totalAlertsReceived = 0;
    private int $totalAlertsSent = 0;
    private int $fatalAlertsReceived = 0;
    private int $fatalAlertsSent = 0;
    private int $warningAlertsReceived = 0;
    private int $warningAlertsSent = 0;
    private int $connectionsClosedCount = 0;

    /** @var array<string, int> */
    private array $receivedAlertsByType = [];

    /** @var array<string, int> */
    private array $sentAlertsByType = [];

    /**
     * {@inheritDoc}
     */
    public function onAlertReceived(Alert $alert): void
    {
        $this->totalAlertsReceived++;

        if ($alert->isFatal()) {
            $this->fatalAlertsReceived++;
        } else {
            $this->warningAlertsReceived++;
        }

        $type = $alert->description->asString();
        $this->receivedAlertsByType[$type] = ($this->receivedAlertsByType[$type] ?? 0) + 1;
    }

    /**
     * {@inheritDoc}
     */
    public function onAlertSent(Alert $alert): void
    {
        $this->totalAlertsSent++;

        if ($alert->isFatal()) {
            $this->fatalAlertsSent++;
        } else {
            $this->warningAlertsSent++;
        }

        $type = $alert->description->asString();
        $this->sentAlertsByType[$type] = ($this->sentAlertsByType[$type] ?? 0) + 1;
    }

    /**
     * {@inheritDoc}
     */
    public function onConnectionClosed(Alert $alert): void
    {
        $this->connectionsClosedCount++;
    }

    /**
     * 获取总的接收警告数量
     */
    public function getTotalAlertsReceived(): int
    {
        return $this->totalAlertsReceived;
    }

    /**
     * 获取总的发送警告数量
     */
    public function getTotalAlertsSent(): int
    {
        return $this->totalAlertsSent;
    }

    /**
     * 获取接收的致命警告数量
     */
    public function getFatalAlertsReceived(): int
    {
        return $this->fatalAlertsReceived;
    }

    /**
     * 获取发送的致命警告数量
     */
    public function getFatalAlertsSent(): int
    {
        return $this->fatalAlertsSent;
    }

    /**
     * 获取接收的警告级别警告数量
     */
    public function getWarningAlertsReceived(): int
    {
        return $this->warningAlertsReceived;
    }

    /**
     * 获取发送的警告级别警告数量
     */
    public function getWarningAlertsSent(): int
    {
        return $this->warningAlertsSent;
    }

    /**
     * 获取连接关闭次数
     */
    public function getConnectionsClosedCount(): int
    {
        return $this->connectionsClosedCount;
    }

    /**
     * 获取接收警告按类型统计
     *
     * @return array<string, int>
     */
    public function getReceivedAlertsByType(): array
    {
        return $this->receivedAlertsByType;
    }

    /**
     * 获取发送警告按类型统计
     *
     * @return array<string, int>
     */
    public function getSentAlertsByType(): array
    {
        return $this->sentAlertsByType;
    }

    /**
     * 获取完整统计信息
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return [
            'total_alerts_received' => $this->totalAlertsReceived,
            'total_alerts_sent' => $this->totalAlertsSent,
            'fatal_alerts_received' => $this->fatalAlertsReceived,
            'fatal_alerts_sent' => $this->fatalAlertsSent,
            'warning_alerts_received' => $this->warningAlertsReceived,
            'warning_alerts_sent' => $this->warningAlertsSent,
            'connections_closed_count' => $this->connectionsClosedCount,
            'received_alerts_by_type' => $this->receivedAlertsByType,
            'sent_alerts_by_type' => $this->sentAlertsByType,
        ];
    }

    /**
     * 重置所有统计数据
     */
    public function reset(): void
    {
        $this->totalAlertsReceived = 0;
        $this->totalAlertsSent = 0;
        $this->fatalAlertsReceived = 0;
        $this->fatalAlertsSent = 0;
        $this->warningAlertsReceived = 0;
        $this->warningAlertsSent = 0;
        $this->connectionsClosedCount = 0;
        $this->receivedAlertsByType = [];
        $this->sentAlertsByType = [];
    }
} 