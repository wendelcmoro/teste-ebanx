<?php

class Router {
    private $routes = [];

    public function add($method, $route, $callback) {
        $this->routes[] = [
            'method' => $method,
            'route' => $route,
            'callback' => $callback
        ];
    }

    public function run() {
        $url = strtok($_SERVER['REQUEST_URI'], '?'); // Captura a URL sem parâmetros de consulta
        $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            // Verifica se a rota coincide com o método e a URL base
            if ($url === $route['route'] && $requestMethod === $route['method']) {
                // Captura os parâmetros da consulta
                parse_str($queryString, $queryParams);
                // Chama a função de callback passando os parâmetros da consulta
                echo call_user_func($route['callback'], $queryParams);
                return;
            }
        }

        // Se a rota não for encontrada
        http_response_code(404);
        echo json_encode(['error' => '404 - Not Found']);
    }
}
