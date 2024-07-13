<?php

class Account {
    private $id;
    private $balance = 0;

    public function __construct($id, $balance = 0) {
        $this->id = $id;
        $this->balance = $balance;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getBalance() {
        return $this->balance;
    }

    public function setBalance($balance) {
        $this->balance = $balance;
    }
}
?>