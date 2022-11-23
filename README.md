# laravel-nowpayments

> A Laravel Package for working with NOWPayments seamlessly

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



Once Laravel Paystack is installed, you need to register the service provider. Open up `config/app.php` and add the following to the `providers` key.

```php
'providers' => [
    ...
    PrevailExcel\Nowpayments\NowpaymentsServiceProvider::class,
    ...
]
```

> If you use **Laravel >= 5.5** you can skip this step and go to [**`configuration`**](https://github.com/unicodeveloper/laravel-nowpayments#configuration)

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
    'apiKey' => getenv('NOWPAYMENTS_API_KEY'),

    /**
     * You enviroment can either be live or sandbox.
     * Make sure to add the appropriate API key after changing the enviroment in .env
     *
     */
    'env' => getenv('NOWPAYMENTS_ENV', 'sandbox'),

    /**
     * NOWPayments Live URL
     *
     */
    'liveUrl' => getenv('NOWPAYMENTS_LIVE_URL', "https://api.nowpayments.io/v1"),

    /**
     * NOWPayments Sandbox URL
     *
     */
    'sandboxUrl' => getenv('NOWPAYMENTS_SANDBOX_URL', "https://api-sandbox.nowpayments.io/v1"),

    /**
     * Your callback URL
     *
     */
    'callbackUrl' => getenv('NOWPAYMENTS_CALLBACK_URL'),
];
```


## General e-commerce payment flow

### 1
API - Check API availability with the "GET API status" method. If required, check the list of available payment currencies with the "GET available currencies" method.
### 2
UI - Ask a customer to select item/items for purchase to determine the total sum;
### 3
UI - Ask a customer to select payment currency
### 4
API - Get the minimum payment amount for the selected currency pair (payment currency to your Outcome Wallet currency) with the "GET Minimum payment amount" method;
### 5
API - Get the estimate of the total amount in crypto with "GET Estimated price" and check that it is larger than the minimum payment amount from step 4;
### 6
API - Call the "POST Create payment" method to create a payment and get the deposit address (in our example, the generated BTC wallet address is returned from this method);
### 7
UI - Ask a customer to send the payment to the generated deposit address (in our example, user has to send BTC coins);
### 8
UI - A customer sends coins, NOWPayments processes and exchanges them (if required), and settles the payment to your Outcome Wallet (in our example, to your ETH address);
### 9
API - You can get the payment status either via our IPN callbacks or manually, using "GET Payment Status" and display it to a customer so that they know when their payment has been processed.
### 10
API - you call the list of payments made to your account via the "GET List of payments" method. Additionally, you can see all of this information in your Account on NOWPayments website.


## Usage

Open your .env file and add your api key, env, callback url like so:

```php
NOWPAYMENTS_LIVE_URL="https://api.nowpayments.io/v1"
NOWPAYMENTS_SANDBOX_URL="https://api-sandbox.nowpayments.io/v1"
NOWPAYMENTS_ENV="live"
NOWPAYMENTS_API_KEY="*******-*******-*******-*******"
NOWPAYMENTS_CALLBACK_URL="https://yourcallback.com"
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
                'order_id' => request()->order_id ?? uniqid(),
                'pay_currency' => request()->pay_currency ?? 'btc',
            ];

           $paymentDetails = Nowpayments::createPayment($data);
            
            dd($paymentDetails);
            // Now you have the payment details,
            // you can then redirect or do whatever you want

            return Redirect::back()->with(['msg'=> "Payment created successfully", 'type'=>'success'], 'data'=>$response);
        }catch(\Exception $e) {
            return Redirect::back()->withMessage(['msg'=> "There's an error in the data", 'type'=>'error']);
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
Nowpayments::createPayment(array $data);

/**
 * Gets the payment details of a particular transaction including the status with the paymentId 
 * @returns array
 */
Nowpayments::getPaymentStatus($paymentId);

/**
 * Get all currenices
 * @returns array
 */
Nowpayments::getCurrencies()

/**
     *   Get the minimum payment amount for a specific pair.
     *   You can provide both currencies in the pair or just currency_from, and we will calculate the minimum *   payment amount for currency_from and currency which you have specified as the outcome in the Store *   Settings.
     *   
 * @returns array
 */
Nowpayments::getMinimumPaymentAmount(string $currency_from, string $currency_to);

/**
 *  Creates invoice with url where you can complete the payment.
 * @returns array
 */
Nowpayments::createInvoice(array $data);
/**
     * Returns the entire list of all transactions, created with certain API key.
 * @returns array
 */
Nowpayments::getListOfPayments();

/**
* This method gets the estitmate price of an amount in different pairs
* @return array
*/
Nowpayments::getEstimatePrice();
```

## Todo

* Charge Returning Customers
* Add Comprehensive Tests
* Implement Transaction Dashboard to see all of the transactions in your laravel app

## Contributing

Please feel free to fork this package and contribute by submitting a pull request to enhance the functionalities.

## How can I thank you?

Why not star the github repo? I'd love the attention! Why not share the link for this repository on Twitter or HackerNews? Spread the word!

Don't forget to [follow me on twitter](https://twitter.com/EjimaduPrevail)!

Thanks!
Prevail Ejimadu.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
