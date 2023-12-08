<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use \SDPMlab\Anser\Service\Action;
use \Psr\Http\Message\ResponseInterface;
use App\Anser\Orchestrators\CreateOrderOrchestrator;
use App\Anser\Orchestrators\UserLoginOrchestrator;
use App\Anser\Services\Models\OrderProductDetail;

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

    public function createOrderByAnser()
    {
        $userLoginOrchestrator = new UserLoginOrchestrator();

        $getUserResult = $userLoginOrchestrator->build("user1@anser.io", "password");
        
        $product = [
            "p_key"  => 1,
            "price"  => 150,
            "amount" => 10
        ];

        $productList = array_map(function ($product) {
            return new OrderProductDetail(
                p_key: $product['p_key'],
                price: $product['price'],
                amount: $product['amount']
            );
        }, [$product]);

        $userKey = $getUserResult["token"];

        $createOrderOrchestrator = new CreateOrderOrchestrator($userKey, $productList);

        $result = $createOrderOrchestrator->build();

        return $this->respond($result);
    }

    public function createOrderSaga()
    {
        $userLoginOrchestrator = new UserLoginOrchestrator();

        $getUserResult = $userLoginOrchestrator->build("user1@anser.io", "password");

        $product = [
            "p_key"  => 1,
            "price"  => 150,
            "amount" => 1000000000
        ];

        $productList = array_map(function ($product) {
            return new OrderProductDetail(
                p_key: $product['p_key'],
                price: $product['price'],
                amount: $product['amount']
            );
        }, [$product]);

        $userKey = $getUserResult["token"];

        $createOrderOrchestrator = new CreateOrderOrchestrator($userKey, $productList);

        $result = $createOrderOrchestrator->build();

        return $this->respond($result);
    }

    private function defineEachStepAction()
    {
        $produceDetail = [
            [
                "p_key"  => 1,
                "price"  => 150,
                "amount" => 10
            ]
        ];

        $produceKey   = $produceDetail[0]["p_key"];
        $orderKey     = 1;
        // $orderKey     = md5('order_001' . random_int(0, 100000000000000000) . date('m/d/Y h:i:s a', time()));
        $reduceAmount = $produceDetail[0]["amount"];
        $type         = 'reduce';
        $total        = $produceDetail[0]["price"];
        $userKey      = 1;

        /**
         * Step 1
         * Task:     使用者驗證
         * Service:  User Service 
         * API Spec: [POST] /api/v1/user/login
         */
        $this->step1Action = (new Action(
            "http://user-service:8080",
            "POST",
            "/api/v1/user/login"
        ))->addOption("json", [
            "email"    => "user1@anser.io",
            "password" => "password"
        ])->doneHandler($this->anserDoneHandler());

        /**
         * Step 2
         * Task:     取得商品資訊
         * Service:  Produce Service 
         * API Spec: [GET] /api/v1/products/{produceKey}
         */
        $this->step2Action = (new Action(
            "http://production-service:8080",
            "GET",
            "/api/v1/products/$produceKey"
        ))->doneHandler($this->anserDoneHandler());

        /**
         * Step 3
         * Task:     商品庫存扣除
         * Service:  Produce Service 
         * API Spec: [POST] /api/v1/inventory/reduceInventory
         */
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

        /**
         * Step 4
         * Task:     新增訂單
         * Service:  Order Service 
         * API Spec: [POST] /api/v1/order
         */
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

        /**
         * Step 5
         * Task:     使用者錢包扣款
         * Service:  User Service
         * API Spec: [POST] /api/v1/wallet/charge
         */
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
