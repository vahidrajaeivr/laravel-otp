<?php

namespace Rajaei\OTP\Notifications\Channels;

use Rajaei\OTP\Contracts\OTPNotifiable;
use Rajaei\OTP\Contracts\SMSClient;
use Rajaei\OTP\Notifications\Messages\OTPMessage;
use Illuminate\Notifications\Notification;

class OTPSMSChannel
{
    public function __construct(protected SMSClient $SMSClient)
    {
    }

    public function send(OTPNotifiable $notifiable, Notification $notification): mixed
    {
        if (! $notifiable->routeNotificationFor('otp', $notification)) {
            return null;
        }

        /** @var OTPMessage $message */
        $message = $notification->toSMS($notifiable);

        return $this->SMSClient->sendMessage($message->getPayload());
    }
}
