<?php

namespace Tourze\TLSAlert;

/**
 * TLS警告处理器接口
 */
interface AlertHandlerInterface
{
    /**
     * 处理接收到的警告
     *
     * @param Alert $alert 接收到的警告
     * @return void
     * @throws Exception\AlertException 处理失败时抛出异常
     */
    public function handleAlert(Alert $alert): void;

    /**
     * 发送警告给对端
     *
     * @param Alert $alert 要发送的警告
     * @return void
     * @throws Exception\AlertException 发送失败时抛出异常
     */
    public function sendAlert(Alert $alert): void;

    /**
     * 注册警告监听器
     *
     * @param AlertListenerInterface $listener 警告监听器
     * @return void
     */
    public function addListener(AlertListenerInterface $listener): void;

    /**
     * 移除警告监听器
     *
     * @param AlertListenerInterface $listener 警告监听器
     * @return void
     */
    public function removeListener(AlertListenerInterface $listener): void;

    /**
     * 检查连接是否因致命警告而关闭
     *
     * @return bool
     */
    public function isConnectionClosed(): bool;

    /**
     * 获取最后接收到的警告
     *
     * @return Alert|null
     */
    public function getLastReceivedAlert(): ?Alert;

    /**
     * 获取最后发送的警告
     *
     * @return Alert|null
     */
    public function getLastSentAlert(): ?Alert;
} 