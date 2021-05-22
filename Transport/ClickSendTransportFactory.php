<?php


namespace Symfony\Component\Mailer\Bridge\ClickSend\Transport;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Class ClickSendTransportFactory
 * @package Symfony\Component\Mailer\Bridge\ClickSend\Transport
 * @author LAMPELK <github.com/lampelk>
 */
class ClickSendTransportFactory extends AbstractTransportFactory
{

    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();

        if ('clicksend+smtp' === $scheme) {
            return new ClickSendSmtpTransport(
                $this->getUser($dsn),
                $this->getPassword($dsn),
                $dsn->getPort(25),
                $dsn->getOption('tls', null),
                $dsn->getHost(),
                $this->dispatcher,
                $this->logger
            );
        }

        if ('clicksend+api' === $scheme) {
            return new ClickSendApiTransport(
                $this->getUser($dsn),
                $this->getPassword($dsn),
                $this->client,
                $this->dispatcher,
                $this->logger
            );
        }

        throw new UnsupportedSchemeException($dsn, 'clicksend', $this->getSupportedSchemes());

    }

    protected function getSupportedSchemes(): array
    {
        return ['clicksend', 'clicksend+api', 'clicksend+smtp'];
    }
}