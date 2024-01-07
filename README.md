# laravel-nowpayments

[![Latest Stable Version](https://poser.pugx.org/prevailexcel/laravel-nowpayments/v/stable.svg)](https://packagist.org/packages/prevailexcel/laravel-nowpayments)
[![License](https://poser.pugx.org/prevailexcel/laravel-nowpayments/license.svg)](LICENSE.md)

> A Laravel Package for working with NOWPayments seamlessly 
##
> This package comes now with a default dashboard that show the list of all payments and also shows a log of endpoints that have been accessed from your application, fluent methods to handle payments easily and removes the bulk of the heavy work from you.
## Installation

[PHP](https://php.net) 5.4+ or [HHVM](http://hhvm.com) 3.3+, and [Composer](https://getcomposer.org) are required.

To get the latest version of Laravel NOWPayments, simply require it

```bash
composer require prevailexcel/laravel-nowpayments
```

Or add the following line to the require block of your `composer.json` file.

```
"prevailexcel/laravel-nowpayments": "1.0.*"
```

You'll then need to run `composer install` or `composer update` to download it and have the autoloader updated.



Once Laravel NOWPayments is installed, you need to register the service provider. Open up `config/app.php` and add the following to the `providers` key.

```php
'providers' => [
    ...
    PrevailExcel\Nowpayments\NowpaymentsServiceProvider::class,
    ...
]
```

> If you use **Laravel >= 5.5** you can skip this step and go to [**`configuration`**](https://github.com/PrevailExcel/laravel-nowpayments#configuration)

* `PrevailExcel\Nowpayments\NowpaymentsServiceProvider::class`

Also, register the Facade like so:

```php
'aliases' => [
    ...
    'Nowpayments' => PrevailExcel\Nowpayments\Facades\Nowpayments::class,
    ...
]
```

## Configuration

You can publish the configuration file using this command:

```bash
php artisan vendor:publish --provider="PrevailExcel\Nowpayments\NowpaymentsServiceProvider"
```

A configuration-file named `nowpayments.php` with some sensible defaults will be placed in your `config` directory:

```php
<?php


return [

    /**
     * API Key From NOWPayments Dashboard
     *
     */
    'apiKey' => env('NOWPAYMENTS_API_KEY'),
    
    /**
     * IPN Secret from NOWPayments Dashboard
     */
    'ipnSecret' => env('NOWPAYMENTS_IPN_SECRET'),
    
    /**
     * You enviroment can either be live or sandbox.
     * Make sure to add the appropriate API key after changing the enviroment in .env
     *
     */
    'env' => env('NOWPAYMENTS_ENV', 'sandbox'),

    /**
     * NOWPayments Live URL
     *
     */
    'liveUrl' => env('NOWPAYMENTS_LIVE_URL', "https://api.nowpayments.io/v1"),

    /**
     * NOWPayments Sandbox URL
     *
     */
    'sandboxUrl' => env('NOWPAYMENTS_SANDBOX_URL', "https://api-sandbox.nowpayments.io/v1"),

    /**
     * Your callback URL
     *
     */
    'callbackUrl' => env('NOWPAYMENTS_CALLBACK_URL'),

    /**
     * Your URL Path
     *
     */
    'path' => 'laravel-nowpayments',

    /**
     * You can add your custom middleware to access the dashboard here
     *
     */
    'middleware' => null, // "Authorise::class",

    /**
     * Your Nowpayment email here
     *
     */
    'email' => env('NOWPAYMENTS_EMAIL'),
    
    /**
     * Your Nowpayment password here
     *
     */
    'password' =>  env('NOWPAYMENTS_PASSWORD'),
];
```

Remeber to run your migration to add one table to the database for logging with this command

```bash
php artisan migrate
```

You can test the dashboard to see if your set up is ready by serving your application and going to /laravel-nowpayments

```bash
127.0.0.1:8000/laravel-nowpayments
```
You can change this default path/url by changing the 'path' from the config file at `nowpayments.php` in your `config` folder

```php
<?php

return [
    ...
    // 'path' => 'laravel-nowpayments',
    'path' => 'new-endpoint',
];
```

## General E-commerce Payment Flow

### 1 UI - Ask a customer to select item/items for purchase to determine the total sum
### 2 UI - Ask a customer to select payment currency
### 3 API - Call Nowpayments::createPayment() method;
Pass the data from the User as an array. This method does the magic. First it gets the minimum payment amount for the currency pair, then it gets the estimate of the total amount in crypto and checks that it is larger than the minimum payment amount.

If it's true, it sends the payload and gets the payment data that inclues the generated wallet address for the user.
### 4 UI - Ask a customer to send the payment to the generated deposit address.
### 5 UI - A customer sends coins, NOWPayments processes and exchanges them (if required), and settles the payment to your Outcome Wallet.
### 6 API - Check the payment status
You can get the payment status either via NOWPayments IPN callbacks or manually using "nowpayments()->getPaymentStatus()" and display it to a customer so that they know when their payment has been processed.
### 7 Check the dashboard for a list of all your payments
This package comes with a default Dashboard that show a list of all payments and also shows a log of endpoints thta have been accessed from your application.
Additionally, you can see all of this information in your Account on NOWPayments website.


## Usage

Open your .env file and add your api key, env, callback url like so:

```php
NOWPAYMENTS_ENV="live"
NOWPAYMENTS_API_KEY="*******-*******-*******-*******"
NOWPAYMENTS_CALLBACK_URL="https://yourcallback.com"
NOWPAYMENTS_EMAIL="hello@example.com"
NOWPAYMENTS_PASSWORD="your password"
```
*If you are using a hosting service like heroku, ensure to add the above details to your configuration variables.*


```php
// Laravel 5.1.17 and above
Route::post('/pay', 'PaymentController@createCryptoPayment')->name('pay');
```

OR

```php
Route::post('/pay', [
    'uses' => 'PaymentController@createCryptoPayment',
    'as' => 'pay'
]);
```
OR

```php
// Laravel 8 & 9
Route::post('/pay', [App\Http\Controllers\PaymentController::class, 'createCryptoPayment'])->name('pay');
```


```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use PrevailExcel\Nowpayments\Facades\Nowpayments;

class PaymentController extends Controller
{

    /**
     * Collect Order data and create Payment
     * @return Url
     */
    public function createCryptoPayment()
    {
        try{
            $data = [
                'price_amount' => request()->price_amount ?? 100,
                'price_currency' => request()->price_currency ?? 'usd',
                'order_id' => request()->order_id ?? uniqid(), // you can generate your order id as you wish
                'pay_currency' => request()->pay_currency ?? 'btc',
                'payout_currency' => request()->payout_currency ?? 'btc',
            ];

           $paymentDetails = Nowpayments::createPayment($data);
            
            dd($paymentDetails);
            // Now you have the payment details,
            // you can then redirect or do whatever you want

            return Redirect::back()->with(['msg'=>"Payment created successfully", 'type'=>'success'], 'data'=>$paymentDetails);
        }catch(\Exception $e) {
            return Redirect::back()->withMessage(['msg'=>"There's an error in the data", 'type'=>'error']);
        }        
    }
}
```

Some fluent methods this package provides are listed here.
```php

/**
 * This is the method to create a payment. You need to provide your data as an array.
 * @returns array
 */
Nowpayments::createPayment();

/**
 * Alternatively, use the helper.
 */
nowpayments()->createPayment();


/**
 * Gets the payment details of a particular transaction including the status with the paymentId 
 * @returns array
 */
Nowpayments::getPaymentStatus();

/**
 * Alternatively, use the helper.
 */
nowpayments()->getPaymentStatus();


/**
 * Get all currenices
 * @returns array
 */
Nowpayments::getCurrencies()

/**
 * Alternatively, use the helper.
 */
nowpayments()->getCurrencies();


/**
 *   Get the minimum payment amount for a specific pair.
 */
Nowpayments::getMinimumPaymentAmount();

/**
 * Alternatively, use the helper.
 */
nowpayments()->getMinimumPaymentAmount();


/**
 *  Creates invoice with url where user can complete the payment.
 * @returns array
 */
Nowpayments::createInvoice();

/**
 * Alternatively, use the helper.
 */
nowpayments()->createInvoice();


/**
 * This method allows you to obtain information about all the payment plans you’ve created.
 * @returns array
 */
Nowpayments::getPlans();
/**
 * Alternatively, use the helper.
 */
nowpayments()->getPlans();


/**
 * Get information about a particular recurring payment via its ID.
 * @returns array
 */
Nowpayments::getSubscription();
/**
 * Alternatively, use the helper.
 */
nowpayments()->getSubscription();


/**
 * This method allows you to send payment links to your customers via email.
 * @returns array
 */
Nowpayments::emailSubscription();
/**
 * Alternatively, use the helper.
 */
nowpayments()->emailSubscription();


/**
 * Completely removes a particular payment from the recurring payment plan.
 * @returns array
 */
Nowpayments::deleteSubscription();
/**
 * Alternatively, use the helper.
 */
nowpayments()->deleteSubscription();


/**
     * Returns the entire list of all transactions, created with certain API key.
 * @returns array
 */
Nowpayments::getListOfPayments();

/**
 * Alternatively, use the helper.
 */
nowpayments()->getListOfPayments();


/**
* This method gets the estitmate price of an amount in different pairs
* @return array
*/
Nowpayments::getEstimatePrice();

/**
 * Alternatively, use the helper.
 */
nowpayments()->getEstimatePrice();

```

## Todo

* Add Comprehensive Tests
* Add Support For Billing Endpoints
* Add Support For Payout Endpoints

## Contributing

Please feel free to fork this package and contribute by submitting a pull request to enhance the functionalities.

## How can I thank you?

Why not star the github repo? I'd love the attention! Why not share the link for this repository on Twitter or HackerNews? Spread the word!

Don't forget to [follow me on twitter](https://twitter.com/EjimaduPrevail)!
Also check out my page on medium to catch articles and tutorials on Laravel [follow me on medium](https://medium.com/@prevailexcellent)!

Thanks!
Chimeremeze Prevail Ejimadu.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
