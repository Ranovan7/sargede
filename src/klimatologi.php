<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/klimatologi', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $hari = $request->getParam('sampling', date('Y-m-d'));//"2019-06-26");
        $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
        $next_date = date('Y-m-d', strtotime($hari .' +1day'));

        $end = date('Y-m-d', strtotime($hari .' +1day'));
        $from = "{$hari} 07:00:00";
        $to = "{$end} 06:55:00";

        $lokasi = $this->db->query("SELECT * FROM lokasi WHERE lokasi.jenis='4'")->fetchAll();

        $result = [];
        foreach ($lokasi as $l) {
            $klimat = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$l['id']}
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();

            $res = [
                'ch' => 0,
                'temp_min' => NULL,
                'temp_max' => NULL,
                'humi_min' => NULL,
                'humi_max' => NULL,
                'wind' => NULL,
                'rad' => NULL,
                'rad_rec' => NULL,
                'pressure' => NULL,
                'evaporation' => NULL
            ];
            foreach ($klimat as $k) {
                if ($k['rain']){
                    $res['ch'] += $k['rain'];
                }

                if ($res['temp_min']) {
                    $res['temp_min'] = min($res['temp_min'], $k['temp']);
                } else {
                    $res['temp_min'] = $k['temp'];
                }
                if ($res['temp_max']) {
                    $res['temp_max'] = max($res['temp_max'], $k['temp']);
                } else {
                    $res['temp_max'] = $k['temp'];
                }

                if ($res['humi_min']) {
                    $res['humi_min'] = min($res['humi_min'], $k['humi']);
                } else {
                    $res['humi_min'] = $k['humi'];
                }
                if ($res['humi_max']) {
                    $res['humi_max'] = max($res['humi_max'], $k['humi']);
                } else {
                    $res['humi_max'] = $k['humi'];
                }
            }

            $result[] = [
                'lokasi' => $l,
                'durasi_07_13' => $durasi_07_13,
                'durasi_13_19' => $durasi_13_19,
                'durasi_19_01' => $durasi_19_01,
                'durasi_01_07' => $durasi_01_07,
                'durasi_all' => $durasi_all,
                'durasi_manual' => $ch_manual ? $ch_manual['manual'] : null,
            ];
        }

        return $this->view->render($response, 'klimatologi/index.html', [
            'key' => 'value'
        ]);
    })->setName('klimatologi');

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'klimatologi/jamjaman.html', [
                'key' => 'value'
            ]);
        })->setName('klimatologi.jamjaman');

        $this->get('/harian', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'klimatologi/harian.html', [
                'key' => 'value'
            ]);
        })->setName('klimatologi.harian');

        $this->get('/bulanan', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'klimatologi/bulanan.html', [
                'key' => 'value'
            ]);
        })->setName('klimatologi.bulanan');

    });

});
