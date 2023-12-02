<?php

namespace App\Anser\Config;

use SDPMlab\Anser\Service\ServiceList;

/**
 * Ref
 * https://ithelp.ithome.com.tw/articles/10317445
 * https://ithelp.ithome.com.tw/articles/10319014
 */

ServiceList::addLocalService(
    name: "ProductionService",
    address: "production-service",
    port: 8080,
    isHttps: false
);

ServiceList::addLocalService(
    name: "UserService",
    address: "user-service",
    port: 8080,
    isHttps: false
);

ServiceList::addLocalService(
    name: "OrderService",
    address: "order-service",
    port: 8080,
    isHttps: false
);

//定義常數 Log 位置
define("LOG_PATH", APPPATH . DIRECTORY_SEPARATOR . "Anser" . DIRECTORY_SEPARATOR . "Logs" . DIRECTORY_SEPARATOR);