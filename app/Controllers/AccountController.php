<?php

require_once __DIR__ . '/../init.php';
require_once __DIR__ .'/../Models/Account.php';

class AccountController {
    public function __construct() {
        $this->account = new Account();
    }

    public function getBalance($params) {
        if (!isset($params['account_id'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing account_id parameter']);
        }

        $accountId = $params['account_id'];

        if (isset($GLOBALS['accounts'][$accountId])) {
            $account = $GLOBALS['accounts'][$accountId];
            $data = [
                'balance' => isset($account['balance']) ? $account['balance'] : null
            ];
        } else {
            $data = [
                'error' => 'Account not found'
            ];
            http_response_code(404);
        }

        header('Content-Type: application/json');
        return json_encode($data);
    }
}
?>