<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Map

$app->group('/map', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {

    });
});