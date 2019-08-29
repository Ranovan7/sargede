<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/admin', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        // get user yg didapat dari middleware
        $user = $request->getAttribute('user');
        // dump($user); // cek user pakai dump

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

        // inject user ke dalam request agar bisa diakses di route
        $request = $request->withAttribute('user', $user);

        return $next($request, $response);
    })->setName('admin');

});
