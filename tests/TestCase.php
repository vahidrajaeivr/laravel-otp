<?php

declare(strict_types=1);

namespace Rajaei\OTP\Tests;

use Rajaei\OTP\ServiceProvider;
use Rajaei\OTP\Tests\Models\OTPNotifiableUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Rajaei\\OTP\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    /**
     * @param  Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('otp.model', OTPNotifiableUser::class);
        $app['config']->set('otp.sms_client', SampleSMSClient::class);
        $app['config']->set('otp.prefix', '');
    }
}
