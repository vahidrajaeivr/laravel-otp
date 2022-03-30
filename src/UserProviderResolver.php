<?php

namespace Rajaei\OTP;

use Exception;
use Rajaei\OTP\Contracts\NotifiableRepositoryInterface;
use Rajaei\OTP\Contracts\OTPNotifiable;
use Illuminate\Config\Repository as Config;
use InvalidArgumentException;

class UserProviderResolver
{
    public function __construct(private Config $config)
    {
    }

    /**
     * @throws Exception
     */
    public function resolve(string $name = null): NotifiableRepositoryInterface
    {
        $config = $this->getUserProviderConfiguration($name ?: $this->getDefaultProvider());

        if (empty($config)) {
            throw new InvalidArgumentException("User provider [{$name}] is not defined.");
        }

        $model = $config['model'];
        $repository = $config['repository'];

        if (! is_subclass_of($model, OTPNotifiable::class)) {
            throw new Exception('Your model must implement "Rajaei\OTP\Contracts\OTPNotifiable".');
        }

        if (! is_subclass_of($repository, NotifiableRepositoryInterface::class)) {
            throw new Exception('Your repository must implement "Rajaei\OTP\Contracts\NotifiableRepositoryInterface".');
        }

        return new $repository(new $model());
    }

    protected function getDefaultProvider(): string
    {
        return $this->config->get('otp.default_provider');
    }

    private function getUserProviderConfiguration(string $userProvider): ?array
    {
        return $this->config->get('otp.user_providers.'.$userProvider);
    }
}
