<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Curah Hujan

$app->group('/curahhujan', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        return $this->view->render($response, 'curahhujan/index.html');
    });

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/jamjaman.html');
        });
    });

    $this->group('/{id}/harian', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/harian.html');
        });
    });

    $this->group('/{id}/bulanan', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/bulanan.html');
        });
    });

    $this->group('/{id}/maksimum', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/maksimum.html');
        });
    });
});
