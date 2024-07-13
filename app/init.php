<?php

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Controllers/AccountController.php';

session_start();

if (!isset($_SESSION['accounts'])) {
    $_SESSION['accounts'] = [];
}

class App {
    public function run() {
        $router = new Router();

        $router->add('GET', '/', function() {
            $data = [
                'msg' => 'API is running'
            ];

            header('Content-Type: application/json');
            echo json_encode($data);

            return;
        });
        
        $router->add('POST', '/reset', function() {
            $_SESSION['accounts'] = [];

            $data = [
                'msg' => 'API reseted'
            ];

            header('Content-Type: application/json');
            echo json_encode($data);

            return;
        });

        $router->add('GET', '/balance', function($params) {
            return (new AccountController())->getBalance($params);
        });

        $router->add('POST', '/event', function() {
            return (new AccountController())->postEvent();
        });

        $router->run();
    }
}
