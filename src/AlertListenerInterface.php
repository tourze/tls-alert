<?php

namespace Tourze\TLSAlert;

/**
 * TLS警告监听器接口
 */
interface AlertListenerInterface
{
    /**
     * 当接收到警告时调用
     *
     * @param Alert $alert 接收到的警告
     * @return void
     */
    public function onAlertReceived(Alert $alert): void;

    /**
     * 当发送警告时调用
     *
     * @param Alert $alert 发送的警告
     * @return void
     */
    public function onAlertSent(Alert $alert): void;

    /**
     * 当连接因致命警告关闭时调用
     *
     * @param Alert $alert 导致关闭的警告
     * @return void
     */
    public function onConnectionClosed(Alert $alert): void;
}
