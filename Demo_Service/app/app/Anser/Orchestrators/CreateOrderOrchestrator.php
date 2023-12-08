<?php

namespace App\Anser\Orchestrators;

use SDPMlab\Anser\Orchestration\Orchestrator;
use App\Anser\Services\OrderService;
use App\Anser\Services\ProductionService;
use App\Anser\Services\UserService;
use App\Anser\Services\Models\OrderProductDetail;
use App\Anser\Orchestrators\Sagas\CreateOrderSaga;

class CreateOrderOrchestrator extends Orchestrator
{
    public UserService $userService;
    public OrderService $orderService;
    public ProductionService $productionService;

    protected string $authToken;

    /**  
     * @var OrderProductDetail[]
     */
    public array $orderProducts;

    public ?string $orderId = null;

    /**
     * CreateOrderOrchestrator
     *
     * @param string $authToken
     * @param OrderProductDetail[] $orderProducts
     */
    public function __construct(string $authToken, array $orderProducts)
    {
        $this->authToken = $authToken;
        $this->orderProducts = $orderProducts;
        $this->userService = new UserService();
        $this->orderService = new OrderService();
        $this->productionService = new ProductionService();
        $this->orderId = $this->generateOrderId();
    }

    /**
     * definition of orchestrator
     *
     * @return void
     */
    protected function definition()
    {
        //Step0 取得使用者資訊（驗證使用者）
        $this->setStep()->addAction(
            alias: 'userInfo',
            action: $this->userService->userInfoAction($this->authToken)
        );

        //Step1 取得產品最新資訊（用於取得最新售價）
        $step1 = $this->setStep();
        foreach ($this->orderProducts as $index => $orderProduct) {
            $step1->addAction(
                alias: 'product_' . ($orderProduct->p_key),
                action: $this->productionService->productInfoAction($orderProduct->p_key)
            );
        }

        $this->transStart(transactionClass: CreateOrderSaga::class);

        //Step2 扣商品庫存
        $step2 = $this->setStep()->setCompensationMethod('rollbackInventory');
        foreach ($this->orderProducts as $index => $orderProduct) {
            $step2->addAction(
                alias: 'product_' . ($orderProduct->p_key) . '_reduceInventory',
                action: $this->productionService->reduceInventory($orderProduct->p_key,  $this->orderId, $orderProduct->amount)
            );
        }

        //Step3 建立訂單（將會取得訂單總價 total）
        $this->setStep()->setCompensationMethod('rollbackOrder')
            ->addAction(
                alias: 'createOrder',
                action: static function (CreateOrderOrchestrator $runtimeOrch) {
                    $userKey = $runtimeOrch->getStepAction('userInfo')->getMeaningData()['data']['u_key'];
                    //將最新商品售價更新至訂單資訊
                    foreach ($runtimeOrch->orderProducts as &$product) {
                        $product->price = (int)$runtimeOrch->getStepAction('product_' . $product->p_key)->getMeaningData()['data']['price'];
                    }
                    return $runtimeOrch->orderService->createOrderAction($userKey, $runtimeOrch->orderId, $runtimeOrch->orderProducts);
                }
            );

        //Step4 使用者錢包扣款
        $this->setStep()->setCompensationMethod('rollbackUserWalletCharge')
            ->addAction(
                alias: 'walletCharge',
                action: static function (CreateOrderOrchestrator $runtimeOrch) {
                    $userKey = $runtimeOrch->getStepAction('userInfo')->getMeaningData()['data']['u_key'];
                    $total = $runtimeOrch->getStepAction('createOrder')->getMeaningData()['total'];
                    return $runtimeOrch->userService->walletChargeAction($userKey, $runtimeOrch->orderId, $total);
                }
            );

        $this->transEnd();
    }

    protected function defineResult(): array
    {
        $orderInfo = $this->getStepAction('createOrder')->getMeaningData();

        $data = [
            "success" => true,
            "message" => "訂單建立成功",
            "data" => [
                "order_id" => $this->orderId,
                "total" => $orderInfo['total'],
            ]
        ];

        return $data;
    }

    protected function defineFailResult(): array
    {
        $failActions = $this->getFailActions();
        $failMessages = [];
        foreach ($failActions as $failAction) {
            $failMessages[] = $failAction->getMeaningData();
        }
        $data = [
            "success" => false,
            "message" => "訂單建立失敗",
            "data" => [
                "order_id" => $this->orderId,
                "fail_messages" => $failMessages,
            ]
        ];
        return $data;
    }

    protected function generateOrderId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}
