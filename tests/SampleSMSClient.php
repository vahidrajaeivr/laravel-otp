<?php

namespace Rajaei\OTP\Tests;

use Rajaei\OTP\Contracts\SMSClient;
use Rajaei\OTP\Notifications\Messages\MessagePayload;

class SampleSMSClient implements SMSClient
{
    public function sendMessage(MessagePayload $payload): mixed
    {
        return null;
        // dump($payload->to(),$payload->content());
    }
}
