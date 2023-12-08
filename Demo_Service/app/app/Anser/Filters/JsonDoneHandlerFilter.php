<?php
namespace App\Anser\Filters;

use SDPMlab\Anser\Service\FilterInterface;
use SDPMlab\Anser\Service\ActionInterface;
use \Psr\Http\Message\ResponseInterface;

class JsonDoneHandlerFilter implements FilterInterface
{
    public function beforeCallService(ActionInterface $action)
    {
        $action->doneHandler(static function (
            ResponseInterface $response,
            ActionInterface $action
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $data['code'] = $response->getStatusCode();
            $action->setMeaningData($data);
        });
    }

    public function afterCallService(ActionInterface $action)
    {
        //do nothing
    }
}