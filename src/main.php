<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Main Route

// home

$app->get('[/]', function(Request $request, Response $response, $args) {
    return $this->view->render($response, 'main/index.html');
});

$app->get('/dashboard', function(Request $request, Response $response, $args) {
    
});


// Auth User

$app->get('/login', function(Request $request, Response $response, $args) {
    
});
$app->post('/login', function(Request $request, Response $response, $args) {
    
});

$app->post('/logout', function(Request $request, Response $response, $args) {
    
});