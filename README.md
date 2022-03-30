# laravel-otp
OTP package is a tools to validate users via sending sms. This package allow users to send/receive sms and  for OTP authentication validattion.

## Version Compatibility

Laravel  | Laravel-OTP
:---------|:----------
9.0.x          | 3.0.x
6.0.x to 8.0.x | 1.0.x

## Basic Usage:

```php
<?php

/**
 * Send OTP via SMS.
 */
OTP()->send('+447812456789');
// or
OTP('+447812456789');

/**
 * Send OTP via channels.
 */
OTP()->channel(['otp_sms', 'mail', \App\Channels\CustomSMSChannel::class])
     ->send('+447812456789');
// or
OTP('+447812456789', ['otp_sms', 'mail', \App\Channels\CustomSMSChannel::class]);

/**
 * Send OTP for specific user provider
 */
OTP()->useProvider('admins')
     ->send('+447812456789');

/**
 * Validate OTP
 */
OTP()->validate('+447812456789', 'token_123');
// or
OTP('+447812456789', 'token_123');
// or
OTP()->useProvider('users')
     ->validate('+447812456789', 'token_123');
```

## Installation

You can install the package via composer:

```shell
composer require vahidrajaeivr/laravel-otp
```

## Configuration

As next step, let's publish config file `config/otp.php` by executing:

```
php artisan vendor:publish --provider="Rajaei\OTP\ServiceProvider" --tag="config"
```

### Token Storage

Package allows you to store the generated one-time password on either `cache` or `database` driver, the default is `cache`.

You can change the preferred driver through config file that we published earlier:

```php
// config/otp.php

<?php

return [
    /**
    |Supported drivers: "cache", "database"
    */
    'token_storage' => 'cache',
];
```

##### Cache

`Laravel OTP` package uses the `cache` driver to storage token which is already configured, if you have not configured
one yet or have not planned to do it, then you can use `database` instead.

##### Database

It means after migrating, a table will be created which your application needs to store verification tokens.

> If you’re using another column name for `mobile` phone or even `otp_tokens` table, you can customize their values in config file:

```php
// config/otp.php

<?php

return [

    'mobile_column' => 'mobile',

    'token_table'   => 'otp_token',

    //...
];

```

Depending on the `token_storage` config, the package will create a token table. Also, a `mobile` column will be added to
your `users` ([default provider](#user-providers)) table to show user verification state and store user's mobile phone.

All right! Now you should migrate the database:

```
php artisan migrate
```

> **Note:** When you are using OTP to login user, consider all columns must be nullable except for the `mobile` column. Because, after verifying OTP, a user record will be created if the user does not exist.

## User providers

You may wish to use the OTP for variant users. Laravel OTP allows you to define and manage many user providers that you
need. In order to set up, you should open `config/otp.php` file and define your providers:

```php
// config/otp.php

<?php

return [
    //...

    'default_provider' => 'users',

    'user_providers'  => [
        'users' => [
            'table'      => 'users',
            'model'      => \App\Models\User::class,
            'repository' => \Rajaei\OTP\NotifiableRepository::class,
        ],

       // 'admins' => [
       //   'model'      => \App\Models\Admin::class,
       //   'repository' => \Rajaei\OTP\NotifiableRepository::class,
       // ],
    ],

    //...
];
```

> **Note:** You may also change the default repository and replace your own repository. however, every repository must implement `Rajaei\OTP\Contracts\NotifiableRepositoryInterface` interface.

#### Model Preparation

Every model must implement `Rajaei\OTP\Contracts\OTPNotifiable` and also use
this `Rajaei\OTP\Concerns\HasOTPNotify` trait:

```php
<?php

namespace App\Models;

use Rajaei\OTP\Concerns\HasOTPNotify;
use Rajaei\OTP\Contracts\OTPNotifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements OTPNotifiable
{
    use Notifiable;
    use HasOTPNotify;

    // ...
}
```

### SMS Client

You can use any SMS services for sending OTP message(it depends on your choice).

For sending notifications via this package, first you need to implement the `Rajaei\OTP\Contracts\SMSClient`
contract. This contract requires you to implement `sendMessage` method.

This method will return your SMS service API results via a `Rajaei\OTP\Notifications\Messages\MessagePayload` object
which contains user **mobile** and **token** message:

```php
<?php

namespace App;

use Rajaei\OTP\Contracts\SMSClient;
use Rajaei\OTP\Notifications\Messages\MessagePayload;

class SampleSMSClient implements SMSClient
{
    public function __construct(protected SampleSMSService $SMSService)
    {
    }

    public function sendMessage(MessagePayload $payload): mixed
    {
        return $this->SMSService->send($payload->to(), $payload->content());
    }

    // ...
}
```

> In above example, `SMSService` can be replaced with your chosen SMS service along with its respective method.

Next, you should set the client wrapper `SampleSMSClient` class in config file:

```php
// config/otp.php

<?php

return [

  'sms_client' => \App\SampleSMSClient::class,

  //...
];
```

## Practical Example

Here we have prepared a practical example. Suppose you are going to login/register a customer by sending an OTP:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Rajaei\OTP\Exceptions\InvalidOTPTokenException;
use Rajaei\OTP\OTPBroker as OTPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AuthController
{
    public function __construct(private OTPService $OTPService)
    {
    }

    public function sendOTP(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->OTPService->send($request->get('mobile'));
        } catch (Throwable $ex) {
          // or prepare and return a view.
           return response()->json(['message'=>'An unexpected error occurred.'], 500);
        }

        return response()->json(['message'=>'A token has been sent to:'. $user->mobile]);
    }

    public function verifyOTPAndLogin(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->OTPService->validate($request->get('mobile'), $request->get('token'));

            // and do login actions...

        } catch (InvalidOTPTokenException $exception){
             return response()->json(['error'=>$exception->getMessage()],$exception->getCode());
        } catch (Throwable $ex) {
            return response()->json(['message'=>'An unexpected error occurred.'], 500);
        }

         return response()->json(['message'=>'Logged in successfully.']);
    }
}

```

## Customization

### Notification Default Channel Customization

For sending OTP notification there is a default channel. But this package allows you to use your own notification
channel. In order to replace, you should specify channel class here:

```php
//config/otp.php
<?php
return [
    // ...

    'channel' => \Rajaei\OTP\Notifications\Channels\OTPSMSChannel::class,
];
```

> **Note:** If you change the default sms channel, the `sms_client` will be an optional config. Otherwise, you must define your sms client.

### Notification SMS and Email Customization

OTP notification prepares a default sms and email format that are satisfied for most application. However, you can
customize how the mail/sms message is constructed.

To get started, pass a closure to the `toSMSUsing/toMailUsing` method provided by
the `Rajaei\OTP\Notifications\OTPNotification` notification. The closure will receive the notifiable model instance
that is receiving the notification as well as the `token` for validating. Typically, you should call the those methods
from the boot method of your application's `App\Providers\AuthServiceProvider` class:

```php
<?php

use Rajaei\OTP\Notifications\OTPNotification;
use Rajaei\OTP\Notifications\Messages\OTPMessage;
use Illuminate\Notifications\Messages\MailMessage;

public function boot()
{
    // ...

    // SMS Customization
    OTPNotification::toSMSUsing(fn($notifiable, $token) =>(new OTPMessage())
                    ->to($notifiable->mobile)
                    ->content('Your OTP Token is: '.$token));

    //Email Customization
    OTPNotification::toMailUsing(fn ($notifiable, $token) =>(new MailMessage)
            ->subject('OTP Request')
            ->line('Your OTP Token is: '.$token));
}
```

## Translates

To publish translation file you may use this command:

```
php artisan vendor:publish --provider="Rajaei\OTP\ServiceProvider" --tag="lang"
```

you can customize in provided language file:

```php
// resources/lang/vendor/OTP/en/otp.php

<?php

return [
    'otp_token' => 'Your OTP Token is: :token.',

    'otp_subject' => 'OTP request',
];
```

## Testing

```sh
composer test
```
