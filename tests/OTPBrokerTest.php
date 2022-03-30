<?php

namespace Rajaei\OTP\Tests;

use Rajaei\OTP\Contracts\OTPNotifiable;
use Rajaei\OTP\Exceptions\InvalidOTPTokenException;
use Rajaei\OTP\Exceptions\UserNotFoundByMobileException;
use Rajaei\OTP\Notifications\Channels\OTPSMSChannel;
use Rajaei\OTP\Notifications\OTPNotification;
use Rajaei\OTP\Tests\Models\OTPNotifiableUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class OTPBrokerTest extends TestCase
{
    protected const MOBILE = '09389599530';

    /**
     * @test
     */
    public function it_can_send_token_to_an_exist_user(): void
    {
        Notification::fake();

        $user = OTPNotifiableUser::factory()->create();
        $this->assertInstanceOf(OTPNotifiable::class, OTP()->send($user->mobile, true));

        Notification::assertSentTo(
            $user,
            OTPNotification::class
        );
    }

    /**
     * @test
     */
    public function it_can_throw_not_found_if_user_exists_is_true(): void
    {
        $this->expectException(UserNotFoundByMobileException::class);
        OTP()->send(self::MOBILE, true);
    }

    /**
     * @test
     */
    public function it_can_send_token_to_user_that_does_not_exist(): void
    {
        Notification::fake();

        $user = OTP(self::MOBILE);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        Notification::assertSentTo(
            $user,
            OTPNotification::class
        );
    }

    /**
     * @test
     */
    public function it_can_send_token_with_using_default_channel(): void
    {
        Notification::fake();

        $user = OTP()->send(self::MOBILE);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        Notification::assertSentTo(
            $user,
            fn (OTPNotification $notification, $channels) => $channels[0] == config('otp.channel')
        );
    }

    /**
     * @test
     */
    public function it_can_send_token_with_using_specified_channels(): void
    {
        Notification::fake();

        $useChannels = [OTPSMSChannel::class, 'mail'];
        $user = OTP(self::MOBILE, $useChannels);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        Notification::assertSentTo(
            $user,
            fn (OTPNotification $notification, $channels) => $channels == $useChannels
        );
    }

    /**
     * @test
     */
    public function it_can_send_token_with_using_extended_channel(): void
    {
        Notification::fake();

        $user = OTP()->channel('otp_sms')->send(self::MOBILE);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        Notification::assertSentTo(
            $user,
            fn (OTPNotification $notification, $channels) => $channels == ['otp_sms']
        );
    }

    /**
     * @test
     */
    public function it_can_send_token_with_using_custom_channel(): void
    {
        Notification::fake();

        $user = OTP(self::MOBILE, [CustomOTPChannel::class]);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        Notification::assertSentTo(
            $user,
            fn (OTPNotification $notification, $channels) => $channels == [CustomOTPChannel::class]
        );
    }

    /**
     * @test
     */
    public function it_can_not_validate_a_token_when_token_is_expired_or_invalid(): void
    {
        $user = OTPNotifiableUser::factory()->create();

        $this->expectException(InvalidOTPTokenException::class);

        OTP()->validate($user->mobile, '12345');
    }

    /**
     * @test
     */
    public function it_can_validate_a_valid_token(): void
    {
        $user = OTPNotifiableUser::factory()->create();

        OTP()->send($user->mobile);

        $user = OTP()->validate($user->mobile, Cache::get($user->mobile)['token']);

        $this->assertInstanceOf(OTPNotifiable::class, $user);

        // Database Storage
        config()->set('otp.token_storage', 'database');
        $otp = OTP();
        $otp->send($user->mobile);
        $this->assertInstanceOf(OTPNotifiable::class, OTP($user->mobile, $otp->getToken()));
    }

    /**
     * @test
     */
    public function it_can_validate_a_valid_token_and_then_create_user(): void
    {
        $otp = OTP();

        $otp->send(self::MOBILE, false);

        $user = $otp->validate(self::MOBILE, Cache::get(self::MOBILE)['token'], true);

        $this->assertInstanceOf(OTPNotifiable::class, $user);
    }

    /**
     * @test
     */
    public function it_can_revoke_a_token_successfully(): void
    {
        $user = OTPNotifiableUser::factory()->create();

        OTP($user->mobile);

        $this->assertTrue(OTP()->revoke($user));
        $this->assertFalse(OTP()->revoke($user));
    }

    /**
     * @test
     */
    public function it_can_send_by_using_provider(): void
    {
        Notification::fake();

        $user = OTP(self::MOBILE);

        $this->assertInstanceOf(OTPNotifiable::class, OTP()->useProvider('users')->send($user->mobile));

        Notification::assertSentTo(
            $user,
            OTPNotification::class
        );
    }

    public function setUp(): void
    {
        parent::setUp();
        config()->set('otp.user_providers.users.model', OTPNotifiableUser::class);
    }
}
