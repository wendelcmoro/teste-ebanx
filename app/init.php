<?php

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Controllers/AccountController.php';
require_once __DIR__ . '/Models/Account.php';

class App {
    public function run() {
        $router = new Router();

        $router->add('GET', '/', function() {
            return 'Ok';
        });
        
        $router->add('POST', '/reset', function() {
            $_SESSION['accounts'] = [];
            
            // Inicia o vetor de contas com uma conta de ID 300
            // e balanço 0
            $account = new Account("300", 0);
            $auxObj = new stdClass();
            $auxObj->id = $account->getId();
            $auxObj->balance = $account->getBalance();

            $accountsData[] = $auxObj;
            $json = json_encode($accountsData, JSON_PRETTY_PRINT);
            $filename = 'accounts.json';
            file_put_contents($filename, $json);

            return 'OK';
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
