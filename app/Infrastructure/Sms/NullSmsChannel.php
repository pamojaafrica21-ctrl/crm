<?php

namespace App\Infrastructure\Sms;

class NullSmsChannel implements SmsChannelInterface
{
    public function send(string $to, string $message): bool
    {
        return true;
    }
}
