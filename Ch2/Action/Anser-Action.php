<?php
require './vendor/autoload.php';

use \SDPMlab\Anser\Service\Action;
use \Psr\Http\Message\ResponseInterface;

$action = (new Action(
    "https://datacenter.taichung.gov.tw",
    "GET",
    "/swagger/OpenData/3fb669e0-aacf-4dc4-aa5e-d94d2e6f0fe3"
))->addOption("query", [
    "limit" => "1"
])->doneHandler(function (
    ResponseInterface $response,
    Action $runtimeAction
) {
    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);
    $runtimeAction->setMeaningData($data);
});

$data = $action->do()->getMeaningData();

print_r($data);