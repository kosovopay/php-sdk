<?php

declare(strict_types=1);

namespace KosovoPay;

use KosovoPay\Dto\AmountValidation;
use KosovoPay\Dto\Me;
use KosovoPay\Enums\BankCode;
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Http\Connector;
use KosovoPay\Requests\Me\RetrieveMe;
use KosovoPay\Resources\BanksResource;
use KosovoPay\Resources\CurrenciesResource;
use KosovoPay\Resources\PaymentsResource;
use KosovoPay\Resources\RatesResource;
use KosovoPay\Resources\RefundsResource;
use KosovoPay\Resources\WebhookEndpointsResource;

/**
 * The KosovoPay API client. Construct with a secret key, then reach resources
 * via the typed accessors.
 *
 *     $kp = new KosovoPay('sk_test_…');
 *     $payment = $kp->payments->create($params);
 */
final class KosovoPay
{
    public const VERSION = '1.0.0';

    private readonly Connector $connector;

    public readonly PaymentsResource $payments;

    public readonly RefundsResource $refunds;

    public readonly BanksResource $banks;

    public readonly CurrenciesResource $currencies;

    public readonly RatesResource $rates;

    public readonly WebhookEndpointsResource $webhookEndpoints;

    public function __construct(
        string $apiKey,
        string $baseUrl = Config::DEFAULT_BASE_URL,
        string $apiVersion = Config::DEFAULT_API_VERSION,
        int $connectTimeout = 10,
        int $requestTimeout = 30,
        int $maxRetries = 3,
    ) {
        $this->connector = new Connector(new Config(
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            apiVersion: $apiVersion,
            connectTimeout: $connectTimeout,
            requestTimeout: $requestTimeout,
            maxRetries: $maxRetries,
        ));

        $this->payments = new PaymentsResource($this->connector);
        $this->refunds = new RefundsResource($this->connector);
        $this->banks = new BanksResource($this->connector);
        $this->currencies = new CurrenciesResource($this->connector);
        $this->rates = new RatesResource($this->connector);
        $this->webhookEndpoints = new WebhookEndpointsResource($this->connector);
    }

    /** Identify the API key — its team, mode, and usable banks. */
    public function me(): Me
    {
        $request = new RetrieveMe;

        return $request->createDtoFromResponse($this->connector->send($request));
    }

    /**
     * Client-side amount pre-check against a bank's live capabilities. Catches
     * amount_below_minimum / amount_step_invalid before a round-trip.
     */
    public function validateAmount(int $amount, CurrencyCode $currency, BankCode $bank): AmountValidation
    {
        return AmountValidator::validate($this->banks->retrieve($bank), $amount, $currency);
    }

    /** The underlying connector — use it to inject a Saloon MockClient in tests. */
    public function connector(): Connector
    {
        return $this->connector;
    }
}
