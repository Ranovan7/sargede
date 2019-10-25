<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/klimatologi', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        dump("Page Klimatologi");

        // return $this->view->render($response, 'lokasi/index.html', [
        //     'key' => 'value'
        // ]);
    })->setName('klimatologi');

});
