<?php

namespace Symfony\Component\Mailer\Bridge\ClickSend\Tests\Transport;

use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Bridge\ClickSend\Transport\ClickSendApiTransport;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ClickSendApiTransportTest extends TestCase
{
    /**
     * @dataProvider getTransportData
     */
    public function testToString(ClickSendApiTransport $transport, string $expected): void
    {
        self::assertSame($expected, (string)$transport);
    }

    public function getTransportData(): Generator
    {
        yield [
            new ClickSendApiTransport('USERNAME', 'PASSWORD'),
            'clicksend+api://USERNAME:PASSWORD@rest.clicksend.com',
        ];

        yield [
            (new ClickSendApiTransport('USERNAME', 'PASSWORD'))->setHost('example.com'),
            'clicksend+api://USERNAME:PASSWORD@example.com',
        ];

        yield [
            (new ClickSendApiTransport('USERNAME', 'PASSWORD'))->setHost('example.com')->setPort(99),
            'clicksend+api://USERNAME:PASSWORD@example.com:99',
        ];
    }

    public function testSendThrowsForErrorResponse(): void
    {
        $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertSame('https://rest.clicksend.com:8984/v3/email/send', $url);
            $this->assertStringContainsString('Accept: */*', $options['headers'][2] ?? $options['request_headers'][1]);

            return new MockResponse(json_encode(['message' => 'i\'m a teapot']), [
                'http_code' => 418,
                'response_headers' => [
                    'content-type' => 'application/json',
                ],
            ]);
        });

        $transport = new ClickSendApiTransport('USERNAME', 'PASSWORD', $client);
        $transport->setPort(8984);

        $mail = new Email();
        $mail->subject('Hello!')
            ->to(new Address('saif.gmati@symfony.com', 'Saif Eddin'))
            ->from(new Address('fabpot@symfony.com', 'Fabien'))
            ->text('Hello There!');

        $this->expectException(HttpTransportException::class);
        $this->expectExceptionMessage('Unable to send an email: i\'m a teapot (code 418).');
        $transport->send($mail);
    }

    public function testSend(): void
    {
        $client = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertSame('https://rest.clicksend.com:8984/v3/email/send', $url);
            $this->assertStringContainsString('Accept: */*', $options['headers'][2] ?? $options['request_headers'][1]);

            return new MockResponse(json_encode(['messageId' => 'foobar']), [
                'http_code' => 200,
            ]);
        });

        $transport = new ClickSendApiTransport('USERNAME', 'PASSWORD', $client);
        $transport->setPort(8984);

        $dataPart = new DataPart('body');
        $mail = new Email();
        $mail->subject('Hello!')
            ->to(new Address('saif.gmati@symfony.com', 'Saif Eddin'))
            ->from(new Address('fabpot@symfony.com', 'Fabien'))
            ->text('Hello here!')
            ->html('Hello there!')
            ->addCc('foo@bar.fr')
            ->addBcc('foo@bar.fr')
            ->addReplyTo('foo@bar.fr')
            ->attachPart($dataPart);

        $message = $transport->send($mail);

        self::assertSame('foobar', $message->getMessageId());
    }
}