<?php

use Slim\Http\Request;
use Slim\Http\Response;

// URL: /about/wilayah
$app->get('/about/wilayah', function(Request $request, Response $response, $args) {
    return $this->view->render($response, 'about/wilayah.html');
});

// URL: /about/organisasi
$app->get('/about/organisasi', function(Request $request, Response $response, $args) {
    return $this->view->render($response, 'about/organisasi.html');
});

// URL: /about/alamat
$app->get('/about/alamat', function(Request $request, Response $response, $args) {
    return $this->view->render($response, 'about/alamat.html');
});
