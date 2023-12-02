<?php
namespace App\Anser\Orchestrators;

use SDPMlab\Anser\Orchestration\Orchestrator;
use App\Anser\Services\UserService;

class UserLoginOrchestrator extends Orchestrator
{
    protected $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    protected function definition(string $email = "", string $password = "")
    {
        $this->setStep()
            ->addAction('login', $this->userService->userLoginAction($email, $password));
        $this->setStep()
            ->addAction('info', static function(UserLoginOrchestrator $runtimeOrch){
                $data = $runtimeOrch->getStepAction('login')->getMeaningData();
                return $runtimeOrch->userService->userInfoAction($data['token']);
            });
        $this->setStep()
            ->addAction('wallet', static function(UserLoginOrchestrator $runtimeOrch){
                $data = $runtimeOrch->getStepAction('info')->getMeaningData();
                return $runtimeOrch->userService->walletAction($data['data']['u_key']);
            });
    }

    protected function defineResult(): array
    {        
        $loginAction = $this->getStepAction('login');
        $infoAction = $this->getStepAction('info');
        $walletAction = $this->getStepAction('wallet');
        
        $data = [
            "success" => true,
            "message" => "協作器執行成功"
        ];
        $data['token'] =  $loginAction->getMeaningData()['token'];
        $data['userData'] = $infoAction->getMeaningData()['data'];
        $data['walletInfo'] = $walletAction->getMeaningData()['data'];
        return $data;
    }

    protected function defineFailResult(): array
    {
        $loginAction = $this->getStepAction('login');
        $infoAction = $this->getStepAction('info');
        $walletAction = $this->getStepAction('wallet');

        $data = [
            "success" => false,
            "message" => "協作器執行失敗"
        ];

        $data['token'] = $loginAction->isSuccess() ? $loginAction->getMeaningData()['token'] : $loginAction->getMeaningData();
        if($loginAction->isSuccess() == false){
            $data['userData'] = "資料無法取得";
            $data['walletInfo'] = "資料無法取得";
            return $data;
        }

        if($infoAction != null){
            $data['userData'] = $infoAction->isSuccess() ? $infoAction->getMeaningData()['data'] : "部分資料無法取得";
        }

        if($walletAction != null){
            $data['walletInfo'] = $walletAction->isSuccess() ? $walletAction->getMeaningData()['data'] : "部分資料無法取得";
        }

        return $data;
    }

}
