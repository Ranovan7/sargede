<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/kualitas', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        dump("Page Kualitas Air");

        // return $this->view->render($response, 'lokasi/index.html', [
        //     'key' => 'value'
        // ]);
    })->setName('kualitas');

});
