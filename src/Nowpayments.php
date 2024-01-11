<?php

namespace PrevailExcel\Nowpayments;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use PrevailExcel\Nowpayments\Models\Logger;

/*
 * This file is part of the Laravel NOWPayments package.
 *
 * (c) Prevail Ejimadu <prevailexcellent@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Nowpayments
{

    /**
     * Enviroment - local or production
     * @var string
     */
    protected $env;

    /**
     * Issue API Key from your Nowpayments Dashboard
     * @var string
     */
    protected $apiKey;

    /**
     * Instance of Client
     * @var Client
     */
    protected $client;

    /**
     *  Response from requests made to Nowpayments
     * @var mixed
     */
    protected $response;

    /**
     * Nowpayments API base Url
     * @var string
     */
    protected $baseUrl;

    /**
     * Your callback Url. You can set this in your .env or you can add 
     * it to the $data in the methods that require that option.
     * @var string
     */
    protected $callbackUrl;

    final public const VERSION = '1.0.1';

    public function __construct()
    {
        $this->setEnv();
        $this->setKey();
        $this->setBaseUrl();
        $this->setRequestOptions();
        $this->checkStatus();
    }

    /**
     * Get Base Url from NOWPayment config file
     */
    public function setEnv()
    {
        $this->env = Config::get('nowpayments.env');
    }

    /**
     * Get Base Url from NOWPayment config file
     */
    public function setBaseUrl()
    {
        if ($this->env == "sandbox")
            $this->baseUrl = Config::get('nowpayments.sandboxUrl');
        else
            $this->baseUrl = Config::get('nowpayments.liveUrl');
        $this->callbackUrl = Config::get('nowpayments.callbackUrl');
    }

    /**
     * Get api key from NOWPayment config file
     */
    public function setKey()
    {
        $this->apiKey = Config::get('nowpayments.apiKey');
    }

    /**
     * Set options for making the Client request
     */
    private function setRequestOptions(string $jwt = null)
    {
        if (!$jwt)
            $headers = [
                'x-api-key' => $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ];
        else
            $headers = [
                'x-api-key' => $this->apiKey,
                'Authorization' => 'Bearer ' . $jwt,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ];
        $this->client = new Client(
            [
                'base_uri' => $this->baseUrl,
                'headers' => $headers
            ]
        );
    }

    /**
     * @param string $relativeUrl
     * @param string $method
     * @param array $body
     * @return Nowpayments
     * @throws IsNullException
     */
    private function setHttpResponse($relativeUrl, $method, $body = [])
    {
        if (is_null($method)) {
            throw new IsNullException("Empty method not allowed");
        }

        $this->response = $this->client->{strtolower($method)}(
            $this->baseUrl . $relativeUrl,
            ["body" => json_encode($body)]
        );
        if (strlen(stristr($relativeUrl, "?", true)) > 1)
            $relativeUrl = stristr($relativeUrl, "?", true);
        if ($relativeUrl != '/status' && $relativeUrl != '/auth')
            Logger::updateOrCreate([
                'endpoint' => $relativeUrl
            ])->increment('count');
        return $this;
    }

    /**
     * @return Boolean
     * @throws IsNullException
     */
    public function checkStatus()
    {
        $this->setRequestOptions();
        $status = $this->setHttpResponse("/status", 'GET', [])->getResponse();

        if ($status['message'] != "OK") {
            throw new IsNullException("The API is currently not available");
        }
    }

    /**
     * Get the whole response from a get operation
     * @return array
     */
    private function getResponse()
    {
        return json_decode($this->response->getBody(), true);
    }

    /**
     * @return array
     * @throws isNullException
     */
    public function getCurrencies(): array
    {
        $this->setRequestOptions();
        return $this->setHttpResponse("/currencies", 'GET', [])->getResponse();
    }

    /**
     * @param array $data
     * @return array
     * 
     */
    public function getEstimatePrice($data = null): array
    {
        if ($data == null) {
            $data =
                "amount=" . 100 . "&" .
                "currency_from=" . "usd" . "&" .
                "currency_to=" . "usdt";
        }
        if(is_array($data)){
            $data = http_build_query($data);
        }
        return $this->setHttpResponse('/estimate?' . $data, 'GET')->getResponse();
    }

    /**
     *   * This is the method to create a payment. You need to provide your data as a JSON-object payload. Next is a description of the required request fields:
     *   price_amount (required) - the fiat equivalent of the price to be paid in crypto. If the pay_amount parameter is left empty, 
     *   our system will automatically convert this fiat price into its crypto equivalent. Please note that this does not enable fiat payments, only provides a fiat price for yours and the customer’s convenience and information.
     *   price_currency (required) - the fiat currency in which the price_amount is specified (usd, eur, etc).
     *   pay_amount (optional) - the amount that users have to pay for the order stated in crypto. You can either specify it yourself, or we will automatically convert the amount you indicated in price_amount.
     *   pay_currency (required) - the crypto currency in which the pay_amount is specified (btc, eth, etc).
     *   ipn_callback_url (optional) - url to receive callbacks, should contain "http" or "https", eg. "https://nowpayments.io"
     *   order_id (optional) - inner store order ID, e.g. "RGDBP-21314"
     *   order_description (optional) - inner store order description, e.g. "Apple Macbook Pro 2019 x 1"
     *   purchase_id (optional) - id of purchase for which you want to create aother payment, only used for several payments for one order
     *   payout_address (optional) - usually the funds will go to the address you specify in your Personal account. In case you want to receive funds on another address, you can specify it in this parameter.
     *   payout_currency (optional) - currency of your external payout_address, required when payout_adress is specified.
     *   payout_extra_id(optional) - extra id or memo or tag for external payout_address.
     *   fixed_rate(optional) - boolean, can be true or false. Required for fixed-rate exchanges.
     * @param array $data
     * @return $this->getResponse()
     * 
     */

    public function createPayment($data = null): array
    {
        if ($data == null) {
            $data = [
                'price_amount' => request()->price_amount ?? 100,
                'price_currency' => request()->price_currency ?? 'usd',
                'pay_amount' => request()->pay_amount ?? null,
                'pay_currency' => request()->pay_currency ?? Currency::BTC,
                'ipn_callback_url' => request()->ipn_callback_url ?? $this->callbackUrl,
                'order_id' => request()->order_id ?? uniqid(),
                'order_description' => request()->order_description ?? null,
                'purchase_id' => request()->purchase_id ?? null,
                'payout_address' => request()->payout_address ?? 0,
                'payout_currency' => request()->payout_currency ?? 0,
                'payout_extra_id' => request()->payout_extra_id ?? null,
                'fixed_rate' => request()->fixed_rate ?? true,
                "is_fee_paid_by_user" => false
            ];
        }
        $dataForEstimate =
            "amount=" . $data['price_amount'] . "&" .
            "currency_from=" . $data['price_currency'] . "&" .
            "currency_to=" . $data['pay_currency'];

        if ($this->getEstimatePrice($dataForEstimate)['estimated_amount'] > $this->getMinimumPaymentAmount($data['pay_currency'], $data['payout_currency'])['min_amount'])
            return $this->setHttpResponse('/payment', 'POST', array_filter($data))->getResponse();
        else
            return ['success' => false, 'message' => 'Estimated Price is less than the minimun price'];
    }

    /**
     * @param int $paymentId
     * @return array
     * 
     */
    public function getPaymentStatus($paymentId): array
    {
        return $this->setHttpResponse('/payment/' . $paymentId, 'GET')->getResponse();
    }

    /**
     *   Get the minimum payment amount for a specific pair.
     *   You can provide both currencies in the pair or just currency_from, and we will calculate the minimum payment amount for currency_from and currency which you have specified as the outcome in the Store Settings.
     *   In the case of several outcome wallets we will calculate the minimum amount in the same way we route your payment to a specific wallet.
     *   @param $currency_from
     *   @param $currency_to
     *   @return array
     * 
     */
    public function getMinimumPaymentAmount($currency_from, $currency_to = null, $fiat_equivalent = null): array
    {
        if (!$currency_from) {
            $currency_from = Currency::USDT;
        }
        if (!$currency_to) {
            $currency_to = Currency::BTC;
        }
        if (!$fiat_equivalent) {
            $fiat_equivalent = "usd";
        }

        return $this->setHttpResponse(
            '/min-amount?currency_from=' . $currency_from .
                "&currency_to=" . $currency_to .
                "&fiat_equivalent=" . $fiat_equivalent,
            'GET',
        )->getResponse();
    }

    /**
     * 
     * Returns the entire list of all transactions, created with certain API key. The list of optional parameters:
     * limit - number of records in one page. (possible values: from 1 to 500)
     * page - the page number you want to get (possible values: from 0 to page count - 1)
     * @param null|string $data
     * @return bool|array
     * 
     */
    public function getListOfPayments(string $data = null, string $jwt = null): array
    {
        if ($jwt == null) {
            $jwt = $this->getJwt();
        }
        if ($data == null) {
            $data = "limit=10&page=0&sortBy=created_at&orderBy=asc&dateFrom=" . Carbon::now()->subMonth()->format('Y-m-d') . "&dateTo=" .
                Carbon::now()->format('Y-m-d');
        }
        $this->setRequestOptions($jwt);
        return $this->setHttpResponse('/payment?' . $data, 'GET')->getResponse();
    }

    /**
     * Creates invoice with url where you can complete the payment. Request fields:
     *
     * price_amount (required) - the amount that users have to pay for the order stated in fiat currency. In case you do not indicate the price in crypto, our system will automatically convert this fiat amount into its crypto equivalent.
     * price_currency (required) - the fiat currency in which the price_amount is specified (usd, eur, etc).
     * pay_currency (optional) - the crypto currency in which the pay_amount is specified (btc, eth, etc).If not specified, can be chosen on the invoice_url
     * ipn_callback_url (optional) - url to receive callbacks, should contain "http" or "https", eg. "https://nowpayments.io"
     * order_id (optional) - inner store order ID, e.g. "RGDBP-21314"
     * order_description (optional) - inner store order description, e.g. "Apple Macbook Pro 2019 x 1"
     * success_url(optional) - url where the customer will be redirected after successful payment.
     * cancel_url(optional) - url where the customer will be redirected after failed payment.
     *
     * @param array $data
     * @return array
     * 
     */
    public function createInvoice(array $data = null): array
    {
        if ($data == null) {
            $data = array_filter([
                'price_amount' => request()->price_amount ?? 100,
                'price_currency' => request()->price_currency ?? 'usd',
                'pay_amount' => request()->pay_amount ?? null,
                'pay_currency' => request()->pay_currency ?? Currency::BTC,
                'ipn_callback_url' => request()->ipn_callback_url ?? $this->callbackUrl,
                "success_url" => request()->success_url ?? $this->callbackUrl,
                "cancel_url" => request()->cancel_url ?? $this->callbackUrl,
                'order_id' => request()->order_id ?? uniqid(),
                'order_description' => request()->order_description ?? null,
                'purchase_id' => request()->purchase_id ?? null,
                "is_fee_paid_by_user" => false
            ]);
        }

        return $this->setHttpResponse('/invoice', 'POST', array_filter($data))->getResponse();
    }

    /**
     * Checks IPN received from Nowpayments IPN
     * @return bool
     */
    public function verifyIPN(): bool
    {
        $receivedHmac = request()->header('x-nowpayments-sig');
        if ($receivedHmac) {
            $requestData = collect(request()->all())->sortKeys(); // Utilize Collection for sorting
            $sortedRequestJson = json_encode($requestData, JSON_UNESCAPED_SLASHES);
            if ($requestData->isNotEmpty()) {
                $hmac = hash_hmac("sha512", $sortedRequestJson, trim(config('nowpayments.ipnSecret')));
                return $hmac === $receivedHmac;
            }
        }
        return false;
    }


    /**
     * Logins in to your nowpayments account and gets the JWT. Request fields:
     *
     * email (optional) - Uses the details provided in the .env file, except overrided by providing another email here.
     * password (optional) - Uses the details provided in the .env file, except overrided by providing another password here
     *
     * @param string $email
     * @param string $password
     * @return string JWT
     * 
     */
    public function getJwt(string $email = null, string $password = null)
    {
        if (!$email)
            $email = config('nowpayments.email');
        if (!$password)
            $password = config('nowpayments.password');

        $data = array_filter([
            'email' => $email,
            'password' => $password
        ]);
        return $this->setHttpResponse('/auth', 'POST', $data)->getResponse()['token'];
    }

    /**
     * This is the method to create a Recurring Payments plan. Every plan has its unique ID which is required for generating separate payments.
     *
     * "title": the name of your recurring payments plan,
     * "interval_day": recurring payments duration in days,
     * "amount" : amount of funds paid in fiat/crypto,
     * "currency" : crypto or fiat currency we support
     * @param array $data
     * @return array
     * 
     */
    public function createPlan(array $data = null): array
    {
        $jwt = $this->getJwt();

        if ($data == null) {
            $data = array_filter([
                "title" => request()->title ?? "Plan Title",
                "interval_day" => request()->interval ?? 1,
                "amount" => request()->amount ?? 0.5,
                "currency" => request()->currency ?? "usd"
            ]);
        }
        $this->setRequestOptions($jwt);
        return $this->setHttpResponse('/subscriptions/plans', 'POST', array_filter($data))->getResponse();
    }

    /**
     * This method allows you to add necessary changes to a created plan. 
     * They won’t affect users who have already paid; however, the changes will take effect when a new payment is to be made.
     *
     * "title": the name of your recurring payments plan,
     * "interval_day": recurring payments duration in days,
     * "amount" : amount of funds paid in fiat/crypto,
     * "currency" : crypto or fiat currency we support
     * @param array $data
     * @return array
     * 
     */
    public function updatePlan(string $plan_id = null, array $data = null): array
    {

        if ($plan_id == null)
            $plan_id = request()->plan_id;
        if ($data == null)
            $data = array_filter([
                "title" => request()->title ?? "Plan Title",
                "interval_day" => request()->interval ?? 1,
                "amount" => request()->amount ?? 0.5,
                "currency" => request()->currency ?? "usd"
            ]);

        $jwt = $this->getJwt();
        $this->setRequestOptions($jwt);
        return $this->setHttpResponse('/subscriptions/plans/' . $plan_id, 'PATCH', array_filter($data))->getResponse();
    }

    /**
     * This method allows you to obtain information about your payment plan.
     * (you need to specify your payment plan id in the request).
     * "plan_id": the plan_id of your recurring payments plan,
     * 
     * @param string $plan_id
     * @return array
     * 
     */
    public function getPlan(string $plan_id = null): array
    {
        if ($plan_id == null)
            $plan_id = request()->plan_id;

        return $this->setHttpResponse('/subscriptions/plans/' . $plan_id, 'GET', [])->getResponse();
    }

    /**
     * This method allows you to obtain information about all the payment plans you’ve created.
     * "limit": the limit of plans you want to fetch,
     * 
     * @param int $limit
     * @return array
     * 
     */
    public function getPlans(int $limit = null): array
    {
        if ($limit == null)
            $limit = request()->limit ?? 10;

        return $this->setHttpResponse('/subscriptions/plans/?limit=' . $limit, 'GET', [])->getResponse();
    }

    /**
     * This method allows you to send payment links to your customers via email.
     * A day before the paid period ends, the customer receives a new letter with a new payment link. 
     * 
     * "subscription_plan_id": the ID of the payment plan your customer chooses,
     * "email": customer's email,
     * @param array $data
     * @return array
     * 
     */
    public function emailSubscription(array $data = null): array
    {

        if ($data == null)
            $data = array_filter([
                "subscription_plan_id" => request()->plan_id,
                "email" => request()->email
            ]);

        $jwt = $this->getJwt();
        $this->setRequestOptions($jwt);
        return $this->setHttpResponse('/subscriptions', 'POST', array_filter($data))->getResponse();
    }

    /**
     * This method allows you to obtain information about all the Subscriptions you have.
     * 
     * @return array
     * 
     */
    public function getSubscriptions(): array
    {
        return $this->setHttpResponse('/subscriptions', 'GET', [])->getResponse();
    }

    /**
     * Get information about a particular recurring payment via its ID.
     * "subscription id": the id of recurring payments plan subscribed,
     * 
     * @param string $sub_id
     * @return array
     * 
     */
    public function getSubscription(string $sub_id = null): array
    {
        if ($sub_id == null)
            $sub_id = request()->sub_id;

        return $this->setHttpResponse('/subscriptions/' . $sub_id, 'GET', [])->getResponse();
    }

    /**
     * Completely removes a particular payment from the recurring payment plan.
     * You need to specify the payment plan id in the request.
     * "subscription id": the id of recurring payments plan subscribed,
     * 
     * @param string $sub_id
     * @return array
     * 
     */
    public function deleteSubscription(string $sub_id = null): array
    {
        if ($sub_id == null)
            $sub_id = request()->sub_id;

        $jwt = $this->getJwt();
        $this->setRequestOptions($jwt);
        return $this->setHttpResponse('/subscriptions/' . $sub_id, 'DELETE', [])->getResponse();
    }
}
