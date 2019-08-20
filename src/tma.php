<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/tma', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        return $this->view->render($response, 'tma/index.html');
    });

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'tma/show.html');
        });
    });
});
