<?php


namespace Symfony\Component\Mailer\Bridge\ClickSend\Transport;

use ClickSend\Api\TransactionalEmailApi;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class ClickSendApiTransport
 * @package Symfony\Component\Mailer\Bridge\ClickSend\Transport
 * @author LAMPELK <github.com/lampelk>
 */
class ClickSendApiTransport extends AbstractApiTransport
{

    /**
     * @var string|null
     */
    protected ?string $username;

    /**
     * @var string|null
     */
    protected ?string $password;

    /**
     * @var TransactionalEmailApi|null
     */
    protected ?TransactionalEmailApi $api;

    /**
     * ClickSendApiTransport constructor.
     * @param string $username
     * @param string $password
     * @param HttpClientInterface|null $client
     * @param EventDispatcherInterface|null $dispatcher
     * @param LoggerInterface|null $logger
     */
    public function __construct(string $username, string $password, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->username = $username;
        $this->password = $password;
        parent::__construct($client, $dispatcher, $logger);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('clicksend+api://%s:%s@%s', $this->username, $this->password, $this->getEndpoint());
    }

    /**
     * @return string|null
     */
    private function getEndpoint(): ?string
    {
        return ($this->host ?: 'rest.clicksend.com') . ($this->port ? ':' . $this->port : '');
    }

    protected function doSend(SentMessage $message): void
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {

        $auth = sprintf('Basic %s', base64_encode($this->username . ":" . $this->password));

        $headers = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $auth
            ]
        ];

        $body = $email->getBody();

        $to = [];

        foreach ($envelope->getRecipients() as $recipient) {
            $to[] = $this->stringifyAddress($recipient);
        }

        if (count($to) === 0) {
            throw new LogicException('Unable to send an email, recipient is missing.');
        }

        $sender = $email->getSender();

        if ($sender === null) {
            throw new LogicException('Unable to send an email, sender is missing.');
        }

        $from = [
            'name' => $sender->getName(),
            'email_address_id' => $sender->getAddress()
        ];

        $payload = [
            'headers' => $headers,
            'body' => [
                'from' => $from,
                'to' => $to,
                'subject' => $email->getSubject(),
                'body' => $body
            ]
        ];

        $endpoint = sprintf('https://%s/v3/email/send', $this->getEndpoint());

        $response = $this->client->request(
            'POST',
            $endpoint,
            $payload
        );

        $result = $response->toArray(false);

        if (200 !== $response->getStatusCode()) {
            throw new HttpTransportException('Unable to send an email: ' . $result['message'] . sprintf(' (code %d).', $response->getStatusCode()), $response);
        }

        $sentMessage->setMessageId($result['messageId']);

        return $response;

    }

    /**
     * @param Address $address
     * @return array
     */
    private function stringifyAddress(Address $address): array
    {
        $stringifiedAddress = ['email' => $address->getAddress()];

        if ($address->getName()) {
            $stringifiedAddress['name'] = $address->getName();
        }

        return $stringifiedAddress;
    }
}