<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Main Route

// home
$app->get('/', function(Request $request, Response $response, $args) {
    $sejak = intval($request->getParam('sejak', 90));
    $end = date("Y-m-d");
    $start = date('Y-m-d', strtotime($end ." -{$sejak}day"));
    $from = "{$start} 07:00:00";
    $to = "{$end} 06:55:00";

    // get curahhujan list for "sejak" day
    $ch = $this->db->query("SELECT periodik.*, lokasi.nama AS lokasi_nama FROM periodik
                            LEFT JOIN lokasi ON periodik.lokasi_id=lokasi.id
                            WHERE periodik.rain IS NOT NULL
                                AND periodik.sampling BETWEEN '{$from}' AND '{$to}'
                            ORDER BY periodik.sampling DESC")->fetchAll();

    $lokasi_tma = $this->db->query("SELECT * FROM lokasi
                            WHERE lokasi.jenis = '2'
                            ORDER BY lokasi.id")->fetchAll();

    $result = [
        'curahhujan' => []
    ];

    // generating curahhujan data
    $current_date = Null;
    foreach ($ch as $c) {
        // check sampling (date) change every iteration to append new array
        $date = date('Y-m-d', strtotime($c['sampling'].' -7hour'));
        if ($date != $current_date) {
            $result['curahhujan'][$date] = [
                'waktu' => tanggal_format(strtotime($date)),
                'date' => $date,
                'daftar' => []
            ];
            $current_date = $date;
        }

        // check lokasi id to add
        $lokasi = $c['lokasi_nama'];
        if (array_key_exists($c['lokasi_id'], $result['curahhujan'][$date]['daftar'])) {
            // update ch and durasi
            $result['curahhujan'][$date]['daftar'][$c['lokasi_id']]['ch'] = round($result['curahhujan'][$date]['daftar'][$c['lokasi_id']]['ch'] + $c['rain'], 2);
            $result['curahhujan'][$date]['daftar'][$c['lokasi_id']]['durasi'] += 5;
        } else {
            // append new array cause its not exist
            $result['curahhujan'][$date]['daftar'][$c['lokasi_id']] = [
                'id' => $c['lokasi_id'],
                'lokasi' => $lokasi,
                'ch' => $c['rain'],
                'durasi' => 5
            ];
        }
    }

    // generating tma data
    $tmalatest = $this->db->query("SELECT * FROM lokasi
                                    LEFT JOIN periodik ON periodik.id = (
                                        SELECT id from periodik
                                            WHERE periodik.lokasi_id = lokasi.id
                                                AND periodik.sampling <= '{$to}'
                                            ORDER BY sampling DESC
                                            LIMIT 1
                                    )
                                    WHERE lokasi.jenis = '2'
                                    ORDER BY lokasi.id")->fetchAll();
    foreach ($tmalatest as $tma) {
        $tma['wlev'] = max(round($tma['wlev'], 2), 0);
    }

    return $this->view->render($response, 'main/index.html', [
        'hujan_sejak' => $sejak,
        'result' => $result,
        'tmalatest' => $tmalatest
    ]);
});

// Auth User

$app->get('/login', function(Request $request, Response $response, $args) {
    return $this->view->render($response, 'main/login.html');
});
// dummy login flow, bisa di uncomment ke POST
// $app->get('/lg', function(Request $request, Response $response, $args) {
$app->post('/login', function(Request $request, Response $response, $args) {
    $credentials = $request->getParams();
    if (empty($credentials['username']) || empty($credentials['password'])) {
        die("Masukkan username dan password");
    }

    $stmt = $this->db->prepare("SELECT * FROM public.user WHERE username=:username");
    $stmt->execute([':username' => $credentials['username']]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($credentials['password'], $user['password'])) {
        $this->flash->addMessage('messages', "Username / password salah!");
        return $this->view->render($response, 'main/login.html');
    }

    $this->session->user_id = $user['id'];
    $this->session->user_refresh_time = strtotime("+1hour");

    return $this->response->withRedirect('/admin');
});

// generate admin, warning!
$app->get('/gen', function(Request $request, Response $response, $args) {
    $credentials = $request->getParams();
    if (empty($credentials['username']) || empty($credentials['password'])) {
        die("Masukkan username dan password");
    }

    $stmt = $this->db->prepare("SELECT * FROM public.user WHERE username=:username");
    $stmt->execute([':username' => $credentials['username']]);
    $user = $stmt->fetch();

    // jika belum ada di DB, tambahkan
    if (!$user) {
        $stmt = $this->db->prepare("INSERT INTO public.user (username, password, role) VALUES (:username, :password, 1)");
        $stmt->execute([
            ':username' => $credentials['username'],
            ':password' => password_hash($credentials['password'], PASSWORD_DEFAULT)
        ]);
        die("Username {$credentials['username']} ditambahkan!");
    } else { // else update password
        $stmt = $this->db->prepare("UPDATE public.user SET password=:password WHERE id=:id");
        $stmt->execute([
            ':password' => password_hash($credentials['password'], PASSWORD_DEFAULT),
            ':id' => $user['id']
        ]);
        die("Password {$user['username']} diubah!");
    }
});

$app->get('/logout', function(Request $request, Response $response, $args) {
    $this->session->destroy();
    return $this->response->withRedirect('/');
});
