<?php

declare(strict_types=1);

use KosovoPay\KosovoPay;
use Saloon\Http\Faking\MockClient;

/** Build a client wired to a Saloon MockClient — no real HTTP. */
function fakeClient(MockClient $mock): KosovoPay
{
    $kp = new KosovoPay('sk_test_fake', baseUrl: 'https://api.kosovo.sh');
    $kp->connector()->withMockClient($mock);

    return $kp;
}
