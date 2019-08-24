<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/user', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {

        return $this->view->render($response, 'user/index.html', [
            'key' => 'value',
        ]);
    })->setName('user');

    $this->group('/{id}/password', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {

            return $this->view->render($response, 'user/password.html', [
                'key' => 'value',
            ]);
        })->setName('user.password');

    });

    $this->group('/add', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {

            return $this->view->render($response, 'user/add.html', [
                'key' => 'value',
            ]);
        })->setName('user.add');

    });

    $this->group('/{id}/del', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {

            return $this->view->render($response, 'user/delete.html', [
                'key' => 'value',
            ]);
        })->setName('user.delete');

    })->add(function(Request $request, Response $response, $next) { // middleware untuk mendapatkan lokasi
        $args = $request->getAttribute('routeInfo')[2];
        $lokasi_id = intval($args['id']);
        $stmt = $this->db->prepare("SELECT * FROM lokasi WHERE id=:id");
        $stmt->execute([':id' => $lokasi_id]);
        $lokasi = $stmt->fetch();

        if (!$lokasi) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $request = $request->withAttribute('lokasi', $lokasi);

        return $next($request, $response);
    });
});
