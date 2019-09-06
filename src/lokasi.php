<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Map

$app->group('/lokasi', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $lokasis = $this->db->query("SELECT * FROM lokasi")->fetchAll();
        return $this->view->render($response, 'lokasi/index.html', [
            'lokasis' => $lokasis
        ]);
    })->setName('lokasi');

    $this->group('/{id}', function() {

        // change password
        $this->get('/update', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');

            die("Mengakses Update Lokasi");
            return $this->view->render($response, 'user/password.html', [
                'user_id' => $id,
            ]);
        })->setName('lokasi.update');

        $this->post('/update', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');
            $credentials = $request->getParams();

            die("Mengakses Update Lokasi");
            return $this->response->withRedirect('/user');
        })->setName('lokasi.update');

    });

})->add(function(Request $request, Response $response, $next) {

    $user_refresh_time = $this->session->user_refresh_time;
    $now = time();

    // cek masa aktif login
    if (empty($user_refresh_time) || $user_refresh_time < $now) {
        $this->session->destroy();
        // die('Silahkan login untuk melanjutkan');
        return $this->response->withRedirect('/login');
    }

    // cek user exists, ada di index.php
    $user = $this->user;
    if (!$user) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }
    if ($user['role'] != "1") {
        $this->flash->addMessage('errors', 'Hanya admin yang diperbolehkan mengakses laman tersebut.');
        return $this->response->withRedirect('/login');
    }

    // inject user ke dalam request agar bisa diakses di route
    $request = $request->withAttribute('user', $user);

    return $next($request, $response);
});
