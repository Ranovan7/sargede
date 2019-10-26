<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/kualitas', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {

        return $this->view->render($response, 'kualitasair/index.html', [
             'key' => 'value'
        ]);
    })->setName('kualitasair');

});
