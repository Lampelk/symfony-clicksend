<?php


namespace Symfony\Component\Mailer\Bridge\ClickSend\Transport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class ClickSendSmtpTransport
 * @package Symfony\Component\Mailer\Bridge\ClickSend\Transport
 * @author LAMPELK <github.com/lampelk>
 */
class ClickSendSmtpTransport extends EsmtpTransport
{

    public function __construct(string $username, string $password, string $host = 'smtp.clicksend.com', int $port = 25, bool $tls = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        parent::__construct($host, $port, $tls, $dispatcher, $logger);
        $this->setUsername($username);
        $this->setPassword($password);
    }

}