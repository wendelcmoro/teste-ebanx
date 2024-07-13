<?php

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Controllers/AccountController.php';
require_once __DIR__ . '/Models/Account.php';

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
            
            // Inicia o vetor de contas com uma conta de ID 300
            // e balanço 0
            $account = new Account(300, 0);
            $_SESSION['accounts'][] = $account;
            
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
