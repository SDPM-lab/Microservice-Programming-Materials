<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use \SDPMlab\Anser\Service\Action;
use \Psr\Http\Message\ResponseInterface;

class CreateOrder extends BaseController
{
    use ResponseTrait;

    /**
     * The first step action of this orch.
     *
     * @var Action
     */
    protected Action $step1Action;

    /**
     * The second step action of this orch.
     *
     * @var Action
     */
    protected Action $step2Action;

    /**
     * The third step action of this orch.
     *
     * @var Action
     */
    protected Action $step3Action;

    /**
     * The fourth step action of this orch.
     *
     * @var Action
     */
    protected Action $step4Action;

    /**
     * The fifth step action of this orch.
     *
     * @var Action
     */
    protected Action $step5Action;

    /**
     * The data field of response.
     *
     * @var array
     */
    protected array $resultData;

    public function createOrderByAction()
    {
        $this->defineEachStepAction();

        $this->resultData["step1Result"] = $this->step1Action->do()->getMeaningData();
        $this->resultData["step2Result"] = $this->step2Action->do()->getMeaningData();
        $this->resultData["step3Result"] = $this->step3Action->do()->getMeaningData();
        $this->resultData["step4Result"] = $this->step4Action->do()->getMeaningData();
        $this->resultData["step5Result"] = $this->step5Action->do()->getMeaningData();

        return $this->respond([
            "status" => "ok",
            "data"   => $this->resultData
        ]);
    }

    private function defineEachStepAction()
    {
        /**
         * User Service port 8080
         * Produce Service port 8081
         * Order Service port 8082
         */
        $produceDetail = [
            [
                "p_key"  => 1,
                "price"  => 150,
                "amount" => 10
            ]
        ];

        $produceKey   = $produceDetail[0]["p_key"];
        $orderKey     = 'order_' . random_int(0, 100000000000000000);
        $reduceAmount = $produceDetail[0]["amount"];
        $type         = 'reduce';
        $total        = $produceDetail[0]["price"];
        $userKey      = 1;

        $this->step1Action = (new Action(
            "http://user-service:8080",
            "POST",
            "/api/v1/user/login"
        ))->addOption("json", [
            "email"    => "user1@anser.io",
            "password" => "password"
        ])->doneHandler($this->anserDoneHandler());

        $this->step2Action = (new Action(
            "http://production-service:8080",
            "GET",
            "/api/v1/products/$produceKey"
        ))->doneHandler($this->anserDoneHandler());

        $this->step3Action = (new Action(
            "http://production-service:8080",
            "POST",
            "/api/v1/inventory/reduceInventory"
        ))->addOption("form_params", [
            "p_key"   => $produceKey,
            "o_key"   => $orderKey,
            "reduceAmount" => $reduceAmount,
            "type"         => $type
        ])->doneHandler($this->anserDoneHandler());

        $this->step4Action = (new Action(
            "http://order-service:8080",
            "POST",
            "/api/v1/order"
        ))->addOption("json", [
            "o_key"   => $orderKey,
            "product_detail" => $produceDetail
        ])->addOption("headers", [
            "X-User-Key" => $userKey,
        ])->doneHandler($this->anserDoneHandler());

        $this->step5Action = (new Action(
            "http://user-service:8080",
            "POST",
            "/api/v1/wallet/charge"
        ))->addOption("form_params", [
            "o_key"   => $orderKey,
            "total"   => $total
        ])->addOption("headers", [
            "X-User-Key" => $userKey,
        ])->doneHandler($this->anserDoneHandler());
    }

    private function anserDoneHandler(): callable
    {
        return function (
            ResponseInterface $response,
            Action $runtimeAction
        ) {
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $runtimeAction->setMeaningData($data);
        };
    }
}
