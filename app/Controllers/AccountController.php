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

        // Lê do arquivo estático e verifica se conta existe
        // e retorna o balanço
        $json = file_get_contents('accounts.json');
        $accountsData = json_decode($json, true);        
        $found = false;
        foreach ($accountsData as $auxAccount) {
            if ($auxAccount['id'] == $accountId) {
                header('Content-Type: application/json');
                return $auxAccount['balance'];
            }
        }
        header('Content-Type: application/json');
        http_response_code(404);
        return 0;
    }

    public function postEvent() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if ($data === null) {
            http_response_code(400);
            return json_encode(['error' => 'Invalid JSON']);
        }
        $type = $data['type'] ?? null;
        $destination = $data['destination'] ?? null;
        $origin = $data['origin'] ?? null;
        $amount = $data['amount'] ?? null;

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
        $json = file_get_contents('accounts.json');
        $accountsData = json_decode($json, true);  
        $account = null;
        $accountIndex = -1;
        foreach ($accountsData as $index => $auxAccount) {
            if ($auxAccount['id'] == $accountId) {
                $account = $auxAccount;
                $accountIndex = $index;
            }
        }

        // Procura no vetor global de contas, se conta de destino existe
        // o Seguinte trecho funciona apenas para TRANFERÊNCIA
        $destAccount = null;
        $destAccountIndex = -1;
        if ($type == 'transfer') {
            foreach ($accountsData as $index => $auxAccount) {
                if ($auxAccount['id'] == $destination) {
                    $destAccount = $auxAccount;
                    $destAccountIndex = $index;
                }
            }
        }

        // Se a conta não existir, não deve permitir retirar saldo ou transferência
        if (!$account && ($type == 'withdraw' || $type == 'transfer')) {
            http_response_code(404);
            return 0;
        }

        // Se a conta não existir, instancia uma nova
        $found = false;
        if (!$account) {
            $account = new Account($destination, $amount);
            http_response_code(201);
        } else {
            $found = true;
            $balance = $account['balance'];
            if ($type == 'deposit') {
                $balance += $amount;
                http_response_code(201);
            } else if ($type == 'withdraw') {
                $balance -= $amount;
                http_response_code(201);
            } else if ($type == 'transfer') {
                // Soma o balanço da conta de de destino com a conta de origem                
                $auxBalance = $destAccount['balance'];
                $auxBalance += $balance;
                $destAccount['balance'] = $auxBalance;

                // e então desconta o valor da conta de origem
                $balance -= $amount;
                http_response_code(201);
            }
            $account['balance'] = $balance;                        
        }

        // Constrói um objeto vazio para retornar os atributos da conta
        $auxObj = new stdClass();
        if (!$found) {            
            $auxObj->id = $account->getId();
            $auxObj->balance = $account->getBalance();
        } else {
            $auxObj->id = $account['id'];
            $auxObj->balance = $account['balance'];
        }
        $response = [];
        // DEPÓSITO
        if ($type == 'deposit') {
            if (!$found) {
                $accountsData[] = $auxObj;
            } else {
                $accountsData[$accountIndex]['balance'] = $auxObj->balance;
            }
            
            $json = json_encode($accountsData, JSON_PRETTY_PRINT);
            $filename = 'accounts.json';
            file_put_contents($filename, $json);

            $response = [
                'destination' =>  $auxObj,
            ];
        } 
        // RETIRADA
        else if ($type == 'withdraw') {
            if (!$found) {
                $accountsData[] = $auxObj;
            } else {
                $accountsData[$accountIndex]['balance'] = $auxObj->balance;
            }

            $json = json_encode($accountsData, JSON_PRETTY_PRINT);
            $filename = 'accounts.json';
            file_put_contents($filename, $json);

            $response = [
                'origin' => $auxObj,
            ];
        } 
        // TRANSFERÊNCIA
        else if ($type == 'transfer') {
            $destObj = new stdClass();
            $destObj->id = $destAccount['id'];
            $destObj->balance = $destAccount['balance'];
            $accountsData[$destAccountIndex]['balance'] = $destAccount['balance'];
            $accountsData[$accountIndex]['balance'] = $auxObj->balance;
            
            $json = json_encode($accountsData, JSON_PRETTY_PRINT);
            $filename = 'accounts.json';
            file_put_contents($filename, $json);
            
            $response = [
                'origin' => $auxObj,
                'destination' => $destObj,
            ];
        }
        
        header('Content-Type: application/json');
        return json_encode($response);
    }
}
?>