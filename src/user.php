<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/user', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $lokasi = $this->db->query("SELECT * FROM lokasi")->fetchAll();
        $user = $this->db->query("SELECT public.user.*, lokasi.nama AS lokasi_nama, lokasi.jenis AS lokasi_jenis
                                    FROM public.user
                                    LEFT JOIN lokasi ON public.user.lokasi_id=lokasi.id")->fetchAll();

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

            $form = $request->getParams();

            echo $form['username'];
            echo $form['password'];
            echo $form['lokasi'];

            $stmt = $this->db->prepare("INSERT INTO public.user (username, password, role, lokasi_id) VALUES (:username, :password, 2, :lokasi_id)");
            $stmt->execute([
                ':username' => $form['username'],
                ':password' => password_hash($form['password'], PASSWORD_DEFAULT),
                ':lokasi_id' => $form['lokasi']
            ]);

            return $this->response->withRedirect('/user');
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
            $credentials = $request->getParams();

            $stmt = $this->db->prepare("UPDATE public.user SET password=:password WHERE id=:id");
            $stmt->execute([
                ':password' => password_hash($credentials['password'], PASSWORD_DEFAULT),
                ':id' => $id
            ]);
            // die("Password {$user['username']} diubah!"); // change to redirect next

            return $this->response->withRedirect('/user');
        })->setName('user.password');

        // delete
        $this->get('/del', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');
            $user = $user = $this->db->query("SELECT * FROM public.user WHERE id={$id}")->fetch();

            return $this->view->render($response, 'user/delete.html', [
                'user' => $user,
            ]);
        })->setName('user.delete');

        $this->post('/del', function(Request $request, Response $response, $args) {
            $id = $request->getAttribute('id');
            $user = $user = $this->db->query("SELECT * FROM public.user WHERE id={$id}")->fetch();

            $stmt = $this->db->prepare("DELETE FROM public.user WHERE id=:id");
            $stmt->execute([
                ':id' => $id
            ]);
            // die("User {$user['username']} dihapus!"); // change to redirect next

            return $this->response->withRedirect('/user');
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
        die('User selain admin tidak boleh mengakses URL ini');
    }

    // inject user ke dalam request agar bisa diakses di route
    $request = $request->withAttribute('user', $user);

    return $next($request, $response);
});
