<?php

namespace App\Services;

use App\Models\EmailConfiguration;
use App\Models\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class MessageSender
{
    public function send(string $channel, string $to, ?string $subject, string $body): void
    {
        $channel === 'sms'
            ? $this->sms($to, $body)
            : $this->email($to, $subject ?: 'Notification', $body);
    }

    public function email(string $to, string $subject, string $body): void
    {
        $config = EmailConfiguration::active();

        if (! $config) {
            // Active config nai hole Laravel-er default mailer use hobe
            Mail::html(nl2br(e($body)), fn ($m) => $m->to($to)->subject($subject));
            return;
        }

        config([
            'mail.mailers.dynamic' => [
                'transport' => 'smtp',
                'host' => $config->host,
                'port' => $config->port,
                'username' => $config->username,
                'password' => $config->password,
                'encryption' => $config->encryption === 'none' ? null : $config->encryption, // Laravel 10
                'scheme' => $config->encryption === 'ssl' ? 'smtps' : null,                  // Laravel 11+
                'timeout' => 15,
            ],
        ]);

        Mail::purge('dynamic');
        Mail::mailer('dynamic')->html(nl2br(e($body)), function ($m) use ($to, $subject, $config) {
            $m->to($to)->subject($subject)->from($config->from_address, $config->from_name);
        });
    }

    /**
     * Generic HTTP SMS gateway (BulkSMSBD-style params).
     * Tomar provider-er param name alada hole ekhane mil kore nio.
     */
    public function sms(string $to, string $message): void
    {
        $gateway = SmsGateway::active();

        if (! $gateway) {
            throw new RuntimeException('Kono active SMS gateway nai.');
        }

        $params = [
            'api_key' => $gateway->api_key,
            'senderid' => $gateway->sender_id,
            'number' => $to,
            'message' => $message,
            'type' => 'text',
        ];

        if ($gateway->api_secret) {
            $params['secret_key'] = $gateway->api_secret;
        }

        $response = $gateway->http_method === 'POST'
            ? Http::asForm()->timeout(15)->post($gateway->api_url, $params)
            : Http::timeout(15)->get($gateway->api_url, $params);

        if (! $response->successful()) {
            throw new RuntimeException('SMS gateway error: ' . $response->body());
        }
    }
}
