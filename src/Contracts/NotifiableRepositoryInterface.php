<?php

namespace Rajaei\OTP\Contracts;

interface NotifiableRepositoryInterface
{
    public function findOrCreateByMobile(string $mobile): OTPNotifiable;

    public function findByMobile(string $mobile): ?OTPNotifiable;

    public function getModel(): OTPNotifiable;
}
