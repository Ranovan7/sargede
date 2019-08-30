<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/admin', function() use ($loggedinMiddleware) {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        // get user yg didapat dari middleware
        $user = $request->getAttribute('user');

        $hari = $request->getParam('sampling', "2019-06-26");//date('Y-m-d');
        $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
        $next_date = date('Y-m-d', strtotime($hari .' +1day'));
        $from = "{$hari} 00:00:00";
        $to = "{$hari} 23:55:00";

        // ADMIN
        if ($user['role'] == 1)
        {
            $tmas = $this->db->query("SELECT
                                tma.id,
                                tma.sampling,
                                tma.lokasi_id,
                                tma.received,
                                tma.petugas,
                                tma.telemetri,
                                lokasi.nama
                            FROM
                                tma LEFT JOIN lokasi ON (lokasi.id = tma.lokasi_id)
                            WHERE
                                tma.manual = 1
                                AND tma.sampling BETWEEN '{$from}' AND '{$to}'
                            ORDER BY sampling")->fetchAll();

            return $this->view->render($response, 'admin/index.html', [
                'tmas' => $tmas,
            ]);
        }
        else
        {
            // PENGAMAT
            $lokasi = $this->db->query("SELECT * FROM lokasi WHERE id={$user['lokasi_id']}")->fetch();
            if ($lokasi['jenis'] == 2) // tma
            {
                $tmas_temp = $this->db->query("SELECT * FROM tma WHERE lokasi_id={$user['lokasi_id']}")->fetchAll();

                $tmas = [];
                foreach ($tmas_temp as $tma) {
                    $date = date('Y-m-d', strtotime($tma['sampling']));
                    $time = date('H:i', strtotime($tma['sampling']));

                    if (!isset($tmas[$date])) {
                        $tmas[$date] = [
                            'sampling' => $date,
                            'jam6' => 0,
                            'jam12' => 0,
                            'jam18' => 0,
                        ];
                    }

                    switch ($time) {
                        case '06:00':
                            $tmas[$date]['jam6'] = $tma['telemetri'];
                            break;
                        case '12:00':
                            $tmas[$date]['jam12'] = $tma['telemetri'];
                            break;
                        case '18:00':
                            $tmas[$date]['jam18'] = $tma['telemetri'];
                            break;
                    }
                }

                return $this->view->render($response, 'admin/tma.html', [
                    'lokasi' => $lokasi,
                    'tmas' => $tmas,
                ]);
            }
            else // ch
            {
                return $this->view->render($response, 'admin/curahhujan.html', [
                    'key' => 'value',
                ]);
            }
        }
    })->setName('admin');

    /**
     * GROUP ADD
     */
    $this->group('/add', function() {

        /**
         * ADD TMA
         */
        $this->post('/tma', function(Request $request, Response $response) {
            $user = $request->getAttribute('user'); // didapat dari middleware
            $lokasi = $request->getAttribute('lokasi'); // didapat dari middleware
            $now = date('Y-m-d H:i:s');
            $jam = [ '06:00:00', '12:00:00', '18:00:00' ];

            $form = $request->getParams();
            foreach ($form['jam'] as $index => $telemetri) {
                if (empty($telemetri)) { continue; }

                $stmt = $this->db->prepare("INSERT INTO tma (
                                    sampling,
                                    manual,
                                    lokasi_id,
                                    received,
                                    petugas,
                                    telemetri
                                ) VALUES (
                                    :sampling,
                                    1,
                                    :lokasi_id,
                                    :received,
                                    :petugas,
                                    :telemetri
                                )");
                $stmt->execute([
                    ':sampling' => $form['sampling'] ." {$jam[$index]}",
                    ':lokasi_id' => $lokasi['id'],
                    ':received' => $now,
                    ':petugas' => $user['id'],
                    ':telemetri' => $telemetri,
                ]);
            }

            return $response->withRedirect('/admin');
        })->setName('admin.add.tma');

        $this->post('/curahhujan', function(Request $request, Response $response) {
            $user = $request->getAttribute('user'); // didapat dari middleware
            $lokasi = $request->getAttribute('lokasi'); // didapat dari middleware
            $now = date('Y-m-d H:i:s');
            $jam = [ '06:00:00', '12:00:00', '18:00:00' ];

            $form = $request->getParams();
            // foreach ($form['jam'] as $index => $telemetri) {
            //     if (empty($telemetri)) { continue; }
            //
            //     $stmt = $this->db->prepare("INSERT INTO tma (
            //                         sampling,
            //                         manual,
            //                         lokasi_id,
            //                         received,
            //                         petugas,
            //                         telemetri
            //                     ) VALUES (
            //                         :sampling,
            //                         1,
            //                         :lokasi_id,
            //                         :received,
            //                         :petugas,
            //                         :telemetri
            //                     )");
            //     $stmt->execute([
            //         ':sampling' => $form['sampling'] ." {$jam[$index]}",
            //         ':lokasi_id' => $lokasi['id'],
            //         ':received' => $now,
            //         ':petugas' => $user['id'],
            //         ':telemetri' => $telemetri,
            //     ]);
            // }

            return $response->withRedirect('/admin');
        })->setName('admin.add.curahhujan');

    })->add(function(Request $request, Response $response, $next) {

        // hanya user role pengamat yg dapat akses
        $user = $request->getAttribute('user');
        if ($user['role'] != 2) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        return $next($request, $response);
    });

})->add(function(Request $request, Response $response, $next) {

    $user = $request->getAttribute('user', null);
    if ($user && $user['role'] == 2) {
        $lokasi = $this->db->query("SELECT * FROM lokasi WHERE id={$user['lokasi_id']}")->fetch();
        $request = $request->withAttribute('lokasi', $lokasi);
    }

    return $next($request, $response);
})->add($loggedinMiddleware);
