<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

$client = new Client([
    'base_uri' => 'https://datacenter.taichung.gov.tw'
]);

$action = function () use ($client) {
    try {
        $response = $client->request('GET', '/swagger/OpenData/3fb669e0-aacf-4dc4-aa5e-d94d2e6f0fe3', [
            'query' => ['limit' => '1']
        ]);

        return json_decode($response->getBody(), true);
    } catch (RequestException $e) {
        echo $e->getMessage();
    }
};

$data = $action();

print_r($data);
