<?php

namespace App\Infrastructure\Sms;

interface SmsChannelInterface
{
    public function send(string $to, string $message): bool;
}
