<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('/api/v1/createOrderByAction', 'CreateOrder::createOrderByAction');

$routes->get('/api/v1/createOrderByAnser', 'CreateOrder::createOrderByAnser');