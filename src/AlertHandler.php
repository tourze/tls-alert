<?php

namespace Tourze\TLSAlert;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tourze\TLSCommon\Protocol\AlertDescription;
use Tourze\TLSCommon\Protocol\AlertLevel;
use Tourze\TLSCommon\Protocol\ContentType;
use Tourze\TLSRecord\RecordProtocol;

/**
 * TLS警告处理器核心实现
 */
class AlertHandler implements AlertHandlerInterface
{
    private bool $connectionClosed = false;
    private ?Alert $lastReceivedAlert = null;
    private ?Alert $lastSentAlert = null;

    /** @var AlertListenerInterface[] */
    private array $listeners = [];

    public function __construct(
        private readonly RecordProtocol $recordProtocol,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function handleAlert(Alert $alert): void
    {
        $this->lastReceivedAlert = $alert;

        $this->logger->info('收到TLS警告', [
            'level' => $alert->level->asString(),
            'description' => $alert->description->asString(),
            'human_readable' => $alert->getHumanReadableDescription(),
        ]);

        // 通知监听器
        foreach ($this->listeners as $listener) {
            $listener->onAlertReceived($alert);
        }

        // 处理特殊情况
        if ($alert->isCloseNotify()) {
            $this->handleCloseNotify($alert);
        } elseif ($alert->isFatal()) {
            $this->handleFatalAlert($alert);
        } else {
            $this->handleWarningAlert($alert);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendAlert(Alert $alert): void
    {
        try {
            $this->lastSentAlert = $alert;

            $this->logger->info('发送TLS警告', [
                'level' => $alert->level->asString(),
                'description' => $alert->description->asString(),
                'human_readable' => $alert->getHumanReadableDescription(),
            ]);

            // 将警告消息通过记录层发送
            $this->recordProtocol->sendRecord(
                ContentType::ALERT->value,
                $alert->toBinary()
            );

            // 通知监听器
            foreach ($this->listeners as $listener) {
                $listener->onAlertSent($alert);
            }

            // 如果是致命警告，关闭连接
            if ($alert->isFatal()) {
                $this->closeConnection($alert);
            }
        } catch (\Throwable $e) {
            $this->logger->error('发送警告失败', [
                'alert' => $alert->toArray(),
                'error' => $e->getMessage(),
            ]);

            throw new AlertException('发送警告失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addListener(AlertListenerInterface $listener): void
    {
        $this->listeners[] = $listener;
    }

    /**
     * {@inheritDoc}
     */
    public function removeListener(AlertListenerInterface $listener): void
    {
        $this->listeners = array_filter(
            $this->listeners,
            fn(AlertListenerInterface $l) => $l !== $listener
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isConnectionClosed(): bool
    {
        return $this->connectionClosed;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastReceivedAlert(): ?Alert
    {
        return $this->lastReceivedAlert;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastSentAlert(): ?Alert
    {
        return $this->lastSentAlert;
    }

    /**
     * 发送关闭通知
     *
     * @return void
     * @throws AlertException
     */
    public function sendCloseNotify(): void
    {
        $alert = new Alert(AlertLevel::WARNING, AlertDescription::CLOSE_NOTIFY);
        $this->sendAlert($alert);
    }

    /**
     * 发送致命警告并关闭连接
     *
     * @param AlertDescription $description 警告描述
     * @return void
     * @throws AlertException
     */
    public function sendFatalAlert(AlertDescription $description): void
    {
        $alert = new Alert(AlertLevel::FATAL, $description);
        $this->sendAlert($alert);
    }

    /**
     * 处理关闭通知
     *
     * @param Alert $alert
     * @return void
     */
    private function handleCloseNotify(Alert $alert): void
    {
        $this->logger->info('收到关闭通知，准备关闭连接');
        $this->closeConnection($alert);
    }

    /**
     * 处理致命警告
     *
     * @param Alert $alert
     * @return void
     */
    private function handleFatalAlert(Alert $alert): void
    {
        $this->logger->error('收到致命警告，必须关闭连接', [
            'description' => $alert->description->asString(),
            'human_readable' => $alert->getHumanReadableDescription(),
        ]);

        $this->closeConnection($alert);
    }

    /**
     * 处理警告级别的警告
     *
     * @param Alert $alert
     * @return void
     */
    private function handleWarningAlert(Alert $alert): void
    {
        $this->logger->warning('收到警告级别警告', [
            'description' => $alert->description->asString(),
            'human_readable' => $alert->getHumanReadableDescription(),
        ]);

        // 警告级别的警告不会关闭连接，但应用程序可以根据需要处理
    }

    /**
     * 关闭连接
     *
     * @param Alert $alert 导致关闭的警告
     * @return void
     */
    private function closeConnection(Alert $alert): void
    {
        if (!$this->connectionClosed) {
            $this->connectionClosed = true;

            $this->logger->info('连接已关闭', [
                'reason' => $alert->getHumanReadableDescription(),
            ]);

            // 通知监听器连接已关闭
            foreach ($this->listeners as $listener) {
                $listener->onConnectionClosed($alert);
            }
        }
    }
} 