<?php
namespace App\Anser\Filters;

use SDPMlab\Anser\Service\FilterInterface;
use SDPMlab\Anser\Service\ActionInterface;
use SDPMlab\Anser\Exception\ActionException;

class FailHandlerFilter implements FilterInterface
{
    public function beforeCallService(ActionInterface $action)
    {
        $action->failHandler(static function (
            ActionException $e
        ) {
            if($e->isClientError()){
                $msg = $e->getAction()->getResponse()->getBody()->getContents();
                file_put_contents(LOG_PATH . "actionClientErrorlog.txt", "[" . date("Y-m-d H:i:s") . "] " . $msg . PHP_EOL, FILE_APPEND);
                $error = json_decode($msg, true)['error'] ?? "unknow error";
                $e->getAction()->setMeaningData([
                    "code" => $e->getAction()->getResponse()->getStatusCode(),
                    "msg" => $error,
                    "requestRawBody" => $msg
                ]);
            }else if ($e->isServerError()){
                $serverBody = $e->getAction()->getResponse()->getBody()->getContents();
                file_put_contents(LOG_PATH . "actionServerErrorlog.txt", "[" . date("Y-m-d H:i:s") . "] " . $serverBody . PHP_EOL, FILE_APPEND);
                $e->getAction()->setMeaningData([
                    "code" => 500,
                    "msg" => "server error"
                ]);
            }else if($e->isConnectError()){
                file_put_contents(LOG_PATH . "actionConnectErrorlog.txt", "[" . date("Y-m-d H:i:s") . "] " . $e->getMessage() . PHP_EOL, FILE_APPEND);
                $e->getAction()->setMeaningData([
                    "code" => 500,
                    "msg" => $e->getMessage()
                ]);
            }
        });
    }

    public function afterCallService(ActionInterface $action)
    {
        //do nothing
    }
}