<?php

namespace Rajaei\OTP\Database\Factories;

use Rajaei\OTP\Tests\Models\OTPNotifiableUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class OTPNotifiableUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OTPNotifiableUser::class;

    /**
     * Define the model's default state.
     *
     */
    public function definition(): array
    {
        return [
            'mobile' => '+447850236106',
        ];
    }
}
