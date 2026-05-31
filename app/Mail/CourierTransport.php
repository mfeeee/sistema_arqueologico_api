<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class CourierTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $apiKey,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        /** @var Address|null $to */
        $to = collect($email->getTo())->first();

        /** @var Address|null $from */
        $from = collect($email->getFrom())->first();

        $payload = [
            'message' => [
                'to' => array_filter([
                    'email' => $to?->getAddress(),
                    'name' => $to?->getName() ?: null,
                ]),
                'channels' => [
                    'email' => [
                        'override' => array_filter([
                            'from' => $from ? array_filter([
                                'email' => $from->getAddress(),
                                'name' => $from->getName() ?: null,
                            ]) : null,
                            'subject' => $email->getSubject(),
                            'html' => $email->getHtmlBody(),
                            'text' => $email->getTextBody(),
                        ]),
                    ],
                ],
            ],
        ];

        Http::withToken($this->apiKey)
            ->post('https://api.courier.com/send', $payload)
            ->throw();
    }

    public function __toString(): string
    {
        return 'courier';
    }
}
