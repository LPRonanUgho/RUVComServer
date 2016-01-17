<?php
//Override the default Not Found Handler
$container['notFoundHandler'] = function ($c) {
    return function () use ($c) {
        // array for final json response
        $response = array();
        $response['error'] = true;
        $response['message'] = "Not Found";

        return $c['response']
            ->withStatus(404)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($response));
    };
};

//Override the default Not Allowed Handler
$container['notAllowedHandler'] = function ($c) {
    return function ($request, $response, $methods) use ($c) {
        // array for final json response
        $response = array();
        $response['error'] = true;
        $response['message'] = "Method Not Allowed";

        return $c['response']
            ->withStatus(405)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($response));
    };
};
