<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/user', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $lokasi = $this->db->query("SELECT * FROM lokasi")->fetchAll();
        $user = $this->db->query('SELECT * FROM "user"')->fetchAll();

        return $this->view->render($response, 'user/index.html', [
            'lokasi' => $lokasi,
            'users' => $user
        ]);
    })->setName('user');

    $this->group('/add', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {

            return $this->view->render($response, 'user/add.html');
        })->setName('user.add');

        $this->post('[/]', function(Request $request, Response $response, $args) {

            $username = $request->getParam('username');
            $password = $request->getParam('password');

            echo $username;
            echo $password;

            return $this->view->render($response, 'user/add.html', [
                'key' => 'value',
            ]);
        })->setName('user.add');

    });

    $this->group('/{id}', function() {

        // change password
        $this->get('/password', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');

            return $this->view->render($response, 'user/password.html', [
                'user_id' => $id,
            ]);
        })->setName('user.password');

        $this->post('/password', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');
            $password = $request->getParam('password');

            echo $password;

            return $this->view->render($response, 'user/password.html', [
                'user_id' => $id,
            ]);
        })->setName('user.password');

        // delete
        $this->get('/del', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');

            return $this->view->render($response, 'user/delete.html', [
                'user_id' => $id,
            ]);
        })->setName('user.delete');

        $this->post('/del', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');

            return $this->view->render($response, 'user/delete.html', [
                'user_id' => $id,
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
