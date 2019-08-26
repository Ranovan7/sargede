<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Map

$app->group('/map', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $lokasis = $this->db->query("SELECT * FROM lokasi")->fetchAll();
        return $this->view->render($response, 'main/map.html', [
            'lokasis' => $lokasis
        ]);
    });
});
