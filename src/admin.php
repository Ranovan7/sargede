<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/admin', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $mode = $request->getParam('mode', "admin");

        if ($mode == "tma") {
            return $this->view->render($response, 'admin/tma.html', [
                'key' => 'value',
            ]);
        } else if ($mode == "ch") {
            return $this->view->render($response, 'admin/curahhujan.html', [
                'key' => 'value',
            ]);
        } else {
            return $this->view->render($response, 'admin/index.html', [
                'key' => 'value',
            ]);
        }
    })->setName('admin');

});
