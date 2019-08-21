<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Curah Hujan

$app->group('/curahhujan', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $hari = $request->getParam('sampling', "2019-06-26");//date('Y-m-d');
        $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
        $next_date = date('Y-m-d', strtotime($hari .' +1day'));

        $end = date('Y-m-d', strtotime($hari .' +1day'));
        $from = "{$hari} 07:00:00";
        $to = "{$end} 06:55:00";

        $lokasi = $this->db->query("SELECT * FROM lokasi WHERE lokasi.jenis='1' OR lokasi.jenis='4'")->fetchAll();

        $result = [];
        foreach ($lokasi as $l) {
            $ch = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$l['id']} AND rain IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();

            $durasi_07_13 = 0;
            $durasi_13_19 = 0;
            $durasi_19_01 = 0;
            $durasi_01_07 = 0;
            $durasi_all = 0;

            foreach ($ch as $c) {
                $time = date('H:i:s', strtotime(date('H:i:s', strtotime($c['sampling'])) .' -7hour'));
                if ($time < '07:00:00') {
                    $durasi_07_13 += $c['rain'];
                } else if ($time < '13:00:00') {
                    $durasi_13_19 += $c['rain'];
                } else if ($time < '19:00:00') {
                    $durasi_19_01 += $c['rain'];
                } else {
                    $durasi_01_07 += $c['rain'];
                }
                $durasi_all += $c['rain'];
            };

            $result[] = [
                'lokasi' => $l,
                'durasi_07_13' => $durasi_07_13,
                'durasi_13_19' => $durasi_13_19,
                'durasi_19_01' => $durasi_19_01,
                'durasi_01_07' => $durasi_01_07,
                'durasi_all' => $durasi_all
            ];
        }

        return $this->view->render($response, 'curahhujan/index.html', [
            'sampling' => tanggal_format(strtotime($hari)),
            'prev_date' => $prev_date,
            'next_date' => $next_date,
            'result' => $result
        ]);
    })->setName('curahhujan');

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            $hari = $request->getParam('sampling', "2019-06-26");//date('Y-m-d');
            $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
            $next_date = date('Y-m-d', strtotime($hari .' +1day'));

            $end = date('Y-m-d', strtotime($hari .' +1day'));
            $from = "{$hari} 07:00:00";
            $to = "{$end} 06:55:00";

            $lokasi_id = $request->getAttribute('id');
            $lokasi = $this->db->query("SELECT * FROM lokasi WHERE id={$lokasi_id}")->fetch();

            $ch = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$lokasi_id} AND rain IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();
            $result = [
                'datasets' => [],
                'labels' => []
            ];
            $check = 6;
            $i = 0;
            foreach ($ch as $c) {
                $current = date('H', $c['sampling']);
                if ($current != $check) {
                    $check = $current;
                    $i += 1;
                    $result['datasets'][] = 0;
                    $result['labels'][] = date('Y-m-d H', strtotime($c['sampling']));;
                };

                $result['datasets'][$i] += $c['rain'];
            };
            // echo implode(",", $result['datasets']);

            return $this->view->render($response, 'curahhujan/jamjaman.html', [
                'sampling' => tanggal_format(strtotime($hari)),
                'lokasi' => $lokasi,
                'prev_date' => $prev_date,
                'next_date' => $next_date,
                'result' => $result
            ]);
        })->setName('curahhujan.jamjaman');
    });

    $this->group('/{id}/harian', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/harian.html');
        })->setName('curahhujan.harian');
    });

    $this->group('/{id}/bulanan', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/bulanan.html');
        })->setName('curahhujan.bulanan');
    });

    $this->group('/{id}/maksimum', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/maksimum.html');
        })->setName('curahhujan.maksimum');
    });
});
