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
        // $this->resultData["step2Result"] = $this->step2Action->do()->getMeaningData();
        // $this->resultData["step3Result"] = $this->step3Action->do()->getMeaningData();
        // $this->resultData["step4Result"] = $this->step4Action->do()->getMeaningData();
        // $this->resultData["step5Result"] = $this->step5Action->do()->getMeaningData();

        return $this->respond([
            "status" => "ok",
            "data"   => $this->resultData
        ]);
    }

    private function defineEachStepAction()
    {
        $this->step1Action = (new Action(
            "http://localhost:8083",
            "GET",
            "/api/v1/user/login"
        ))->addOption("json", [
            "email"    => "user1@anser.io",
            "password" => "password"
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
