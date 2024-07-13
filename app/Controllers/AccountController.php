<?php

require_once __DIR__ . '/../init.php';
require_once __DIR__ .'/../Models/Account.php';

class AccountController {
    public function __construct() {
    }

    public function getBalance($params) {
        if (!isset($params['account_id'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing account_id parameter']);
        }

        $accountId = $params['account_id'];

        $found = false;
        foreach ($_SESSION['accounts'] as $auxAccount) {
            if ($auxAccount->getId() == $accountId) {
                header('Content-Type: application/json');
                return $auxAccount->getBalance();
            }
        }
        header('Content-Type: application/json');
        http_response_code(404);
        return 0;
    }

    public function postEvent() {
        $type = $_POST['type'] ?? null;
        $destination = $_POST['destination'] ?? null;
        $amount = $_POST['amount'] ?? null;

        if ($type === null || $type == '' || $destination === null || $destination == '' || $amount === null || $amount == '') {
            http_response_code(400);
            return json_encode(['error' => 'Missing parameters']);
        }

        $account = null;
        foreach ($_SESSION['accounts'] as $auxAccount) {
            if ($auxAccount->getId() == $destination) {
                $account = $auxAccount;
            }
        }

        // Se a conta não existir, instancia uma nova
        if ($account == null) {
            $account = new Account($destination, $amount);
            $_SESSION['accounts'][] = $account;
            http_response_code(201);
        }
        else {
            $balance = $account->getBalance();
            $balance += $amount;
            $account->setBalance($balance);   
            http_response_code(200);         
        }

        // Constrói um objeto vazio para retornar os atributos da conta
        $auxObj = new stdClass();
        $auxObj->id = $account->getId();
        $auxObj->balance = $account->getBalance();
        $response = [
            'msg' => 'Event processed successfully',
            'destination' => $auxObj,
        ];
        
        header('Content-Type: application/json');
        return json_encode($response);
    }
}
?>