<?php
namespace App\Anser\Services;

use SDPMlab\Anser\Service\SimpleService;
use SDPMlab\Anser\Service\ActionInterface;
use App\Anser\Filters\FailHandlerFilter;
use App\Anser\Filters\JsonDoneHandlerFilter;

class UserService extends SimpleService
{
    protected $serviceName = "UserService";
    protected $filters = [
        "before" => [
            JsonDoneHandlerFilter::class,
            FailHandlerFilter::class
        ],
        "after" => [],
    ];
    protected $retry = 1;
    protected $retryDelay = 0.2;
    protected $timeout = 3.0;
    protected $options = [];

    /**
     * 使用者登入
     *
     * @param string $email 使用者信箱
     * @param string $password 使用者密碼
     * @return ActionInterface
     */
    public function userLoginAction(string $email, string $password): ActionInterface
    {
        return $this->getAction(
            method: "POST",
            path: "/api/v1/user/login"
        )->setOptions([
            "headers" => [
                "Content-Type" => "application/json"
            ],
            "body" => json_encode([
                "email" => $email,
                "password" => $password
            ])
        ]);
    }

    /**
     * 取得使用者資訊
     *
     * @param string $apiKey
     * @return ActionInterface
     */
    public function userInfoAction(string $apiKey): ActionInterface
    {
        return $this->getAction(
            method: "GET",
            path: "/api/v1/user"
        )->setOptions([
            "headers" => [
                "Authorization" => $apiKey
            ]
        ]);
    }

    /**
     * 取得使用者錢包資訊
     *
     * @param integer $userId
     * @return ActionInterface
     */
    public function walletAction(int $userId): ActionInterface
    {
        return $this->getAction(
            method: "GET",
            path: "/api/v1/wallet"
        )->setOptions([
            "headers" => [
                "X-User-Key" => $userId
            ]
        ]);
    }

    /**
     * 使用者錢包儲值
     *
     * @param integer $userId 使用者ID
     * @param integer $amount 儲值金額
     * @return ActionInterface
     */
    public function walletDepositAction(int $userId, int $amount): ActionInterface
    {
        return $this->getAction(
            method: "POST",
            path: "/api/v1/wallet"
        )->setOptions([
            "headers" => [
                "X-User-Key" => $userId,
            ],
            "form_params" => [
                "addAmount" => $amount
            ]
        ]);
    }

    /**
     * 使用者錢包扣款
     *
     * @param integer $userId  使用者ID
     * @param string  $orderId 訂單ID
     * @param integer $total   扣款金額
     * @return ActionInterface
     */
    public function walletChargeAction(int $userId, string $orderId, int $total): ActionInterface
    {
        return $this->getAction(
            method: "POST",
            path: "/api/v1/wallet/charge"
        )->setOptions([
            "headers" => [
                "X-User-Key" => $userId,
            ],
            "form_params" => [
                "o_key" => $orderId,
                "total" => $total
            ]
        ]);
    }

    /**
     * 使用者錢包補償
     *
     * @param integer $userId  使用者ID
     * @param string  $orderId 訂單ID
     * @param integer $amount  補償金額
     * @return ActionInterface
     */
    public function walletCompensateAction(int $userId, string $orderId, int $amount): ActionInterface
    {
        return $this->getAction(
            method: "POST",
            path: "/api/v1/wallet/compensate"
        )->setOptions([
            "headers" => [
                "X-User-Key" => $userId,
            ],
            "form_params" => [
                "o_key" => $orderId,
                "addAmount" => $amount
            ]
        ]);
    }

}
