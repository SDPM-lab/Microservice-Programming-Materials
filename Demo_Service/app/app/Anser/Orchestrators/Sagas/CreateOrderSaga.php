<?php

namespace App\Anser\Orchestrators\Sagas;

use SDPMlab\Anser\Orchestration\Saga\SimpleSaga;
use SDPMlab\Anser\Service\ConcurrentAction;
use App\Anser\Orchestrators\CreateOrderOrchestrator;

class CreateOrderSaga extends SimpleSaga
{
    public function rollbackInventory()
    {
        /** @var CreateOrderOrchestrator  */
        $runTimeOrchestrator = $this->getOrchestrator();
        $orderProducts = $runTimeOrchestrator->orderProducts;
        $concurrentAction = new ConcurrentAction();
        foreach ($orderProducts as $product) {
            $concurrentAction->addAction(
                'rollbackInventory_' . $product->p_key,
                $runTimeOrchestrator->productionService->addInventoryCompensateAction($product->p_key, $runTimeOrchestrator->orderId, $product->amount)
            );
        }
        $concurrentAction->send();
    }

    public function rollbackOrder()
    {
        /** @var CreateOrderOrchestrator  */
        $runTimeOrchestrator = $this->getOrchestrator();
        $userKey = $runTimeOrchestrator->getStepAction('userInfo')->getMeaningData()['data']['u_key'];
        $runTimeOrchestrator->orderService->compensateOrderAction($userKey, $runTimeOrchestrator->orderId)->do();
    }

    public function rollbackUserWalletCharge()
    {
        /** @var CreateOrderOrchestrator  */
        $runTimeOrchestrator = $this->getOrchestrator();
        $userKey = $runTimeOrchestrator->getStepAction('userInfo')->getMeaningData()['data']['u_key'];
        $total = $runTimeOrchestrator->getStepAction('createOrder')->getMeaningData()['total'];
        $runTimeOrchestrator->userService->walletCompensateAction($userKey, $runTimeOrchestrator->orderId, $total)->do();
    }
}
