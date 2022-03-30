<?php

namespace Rajaei\OTP\Contracts;

interface TokenRepositoryInterface
{
    /**
     * Create a new token record.
     */
    public function create(OTPNotifiable $user): string;

    /**
     * Determine if a token record exists and is valid.
     */
    public function exists(OTPNotifiable $user, string $token): bool;

    /**
     * Delete all existing tokens from the storage.
     */
    public function deleteExisting(OTPNotifiable $user): bool;
}
