<?php

namespace Tourze\TLSAlert\Listener;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tourze\TLSAlert\Alert;
use Tourze\TLSAlert\AlertListenerInterface;

/**
 * 日志记录警告监听器
 */
class LoggingAlertListener implements AlertListenerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function onAlertReceived(Alert $alert): void
    {
        $context = [
            'level' => $alert->level->asString(),
            'description' => $alert->description->asString(),
            'human_readable' => $alert->getHumanReadableDescription(),
            'is_fatal' => $alert->isFatal(),
            'binary' => bin2hex($alert->toBinary()),
        ];

        if ($alert->isFatal()) {
            $this->logger->error('收到致命TLS警告', $context);
        } elseif ($alert->isCloseNotify()) {
            $this->logger->info('收到TLS关闭通知', $context);
        } else {
            $this->logger->warning('收到TLS警告', $context);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onAlertSent(Alert $alert): void
    {
        $context = [
            'level' => $alert->level->asString(),
            'description' => $alert->description->asString(),
            'human_readable' => $alert->getHumanReadableDescription(),
            'is_fatal' => $alert->isFatal(),
            'binary' => bin2hex($alert->toBinary()),
        ];

        if ($alert->isFatal()) {
            $this->logger->error('发送致命TLS警告', $context);
        } elseif ($alert->isCloseNotify()) {
            $this->logger->info('发送TLS关闭通知', $context);
        } else {
            $this->logger->warning('发送TLS警告', $context);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onConnectionClosed(Alert $alert): void
    {
        $this->logger->critical('TLS连接已关闭', [
            'reason_level' => $alert->level->asString(),
            'reason_description' => $alert->description->asString(),
            'reason_human_readable' => $alert->getHumanReadableDescription(),
        ]);
    }
} 