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
        $origin = $_POST['origin'] ?? null;
        $amount = $_POST['amount'] ?? null;

        // Valida parâmetros comuns
        if ($type === null || $type == '') { 
            http_response_code(400);
            return json_encode(['error' => 'Missing parameter: type']);
        }
        if ($amount === null || $amount == '') {
            http_response_code(400);
            return json_encode(['error' => 'Missing parameter: amount']);
        }

        // Valida parâmetros condicionais
        if ($type == 'deposit') {
            if ($destination === null || $destination == '') {
                http_response_code(400);
                return json_encode(['error' => 'Missing parameter: destination']);
            }
        } else if ($type == 'withdraw') {
            if ($origin === null || $origin == '') {
                http_response_code(400);
                return json_encode(['error' => 'Missing parameter: origin']);
            }
        } else if ($type == 'transfer') {
            if ($destination === null || $destination == '') {
                http_response_code(400);
                return json_encode(['error' => 'Missing parameter: destination']);
            }

            if ($origin === null || $origin == '') {
                http_response_code(400);
                return json_encode(['error' => 'Missing parameter: origin']);
            }
        }

        $accountId = 0;
        if ($type == 'deposit') {
            $accountId = $destination;
        } else if ($type == 'withdraw' || $type == 'transfer') {
            $accountId = $origin;
        }

        // Procura no vetor global de contas, se conta existe
        // o Seguinte trecho funciona apenas para RETIRADA ou DEPÓSITO
        $account = null;
        foreach ($_SESSION['accounts'] as $auxAccount) {
            if ($auxAccount->getId() == $accountId) {
                $account = $auxAccount;
            }
        }

        // Procura no vetor global de contas, se conta de destino existe
        // o Seguinte trecho funciona apenas para TRANFERÊNCIA
        $destAccount = null;
        if ($type == 'transfer') {
            foreach ($_SESSION['accounts'] as $auxAccount) {
                if ($auxAccount->getId() == $destination) {
                    $destAccount = $auxAccount;
                }
            }
        }

        // Se a conta não existir, não deve permitir retirar saldo ou transferência
        if (!$account && ($type == 'withdraw' || $type == 'transfer')) {
            http_response_code(404);
            return 0;
        }

        // Se a conta não existir, instancia uma nova
        if (!$account) {
            $account = new Account($destination, $amount);
            $_SESSION['accounts'][] = $account;
            http_response_code(201);
        } else {
            $balance = $account->getBalance();
            if ($type == 'deposit') {
                $balance += $amount;
                http_response_code(200);
            } else if ($type == 'withdraw') {
                $balance -= $amount;
                http_response_code(201);
            } else if ($type == 'transfer') {
                // Soma o balanço da conta de de destino com a conta de origem                
                $auxBalance = $destAccount->getBalance();
                $auxBalance += $balance;
                $destAccount->setBalance($auxBalance);

                // e então desconta o valor da conta de origem
                $balance -= $amount;
                http_response_code(201);
            }
            $account->setBalance($balance);                        
        }

        // Constrói um objeto vazio para retornar os atributos da conta
        $auxObj = new stdClass();
        $auxObj->id = $account->getId();
        $auxObj->balance = $account->getBalance();
        $response = [];
        if ($type == 'deposit') {
            $response = [
                'msg' => 'Event processed successfully',
                'destination' => $auxObj,
            ];
        } else if ($type == 'withdraw') {
            $response = [
                'msg' => 'Event processed successfully',
                'origin' => $auxObj,
            ];
        } else if ($type == 'transfer') {
            $destObj = new stdClass();
            $destObj->id = $destAccount->getId();
            $destObj->balance = $destAccount->getBalance();
            
            $response = [
                'msg' => 'Event processed successfully',
                'origin' => $auxObj,
                'destination' => $destObj,
            ];
        }
        
        header('Content-Type: application/json');
        return json_encode($response);
    }
}
?>