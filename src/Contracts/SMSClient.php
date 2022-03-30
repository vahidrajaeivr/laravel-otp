<?php

namespace Rajaei\OTP\Contracts;

use Rajaei\OTP\Notifications\Messages\MessagePayload;

interface SMSClient
{
    public function sendMessage(MessagePayload $payload): mixed;
}
