<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/klimatologi', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        return $this->view->render($response, 'klimatologi/index.html', [
        'key' => 'value'
        ]);
    })->setName('klimatologi');

});
