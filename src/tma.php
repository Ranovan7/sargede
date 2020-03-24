<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/tma', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $hari = $request->getParam('sampling', date('Y-m-d'));
        // $hari = "2019-06-26";
        $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
        $next_date = date('Y-m-d', strtotime($hari .' +1day'));
        $from = "{$hari} 00:00:00";
        $to = "{$hari} 23:55:00";
        $y_from = "{$prev_date} 00:00:00";
        $y_to = "{$prev_date} 23:55:00";
        // dump($to);

        $result = getTMAdetail($this, $from, $to);
        $y_result = getTMAdetail($this, $y_from, $y_to);

        return $this->view->render($response, 'tma/index.html', [
            'sampling' => $hari,
            'yesterday' => tanggal_format(strtotime($prev_date)),
            'today' => tanggal_format(strtotime($hari)),
            'result' => $result,
            'y_result' => $y_result
        ]);
    })->setName('tma');

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            $lokasi = $request->getAttribute('lokasi');
            $now = date('Y-m-d');
            // $now = "2019-06-28";
            $start_date = $request->getParam('start_date', date('Y-m-d', strtotime("$now -2day")));
            $end_date = $request->getParam('end_date', $now);

            // preparing initial datasets (0s) and labels (hour)
            $result = [
                'datasets' => [],
                'labels' => []
            ];

            $from = "{$start_date} 00:00:00";
            $to = "{$end_date} 23:55:00";
            $wlev = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$lokasi['id']} AND wlev IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();
            $manual = $this->db->query("SELECT * FROM tma
                                    WHERE lokasi_id = {$lokasi['id']}
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();
            $wlev_samp = [];
            $manual_samp = [];
            foreach ($wlev as $w) {
                $wlev_samp[strtotime($w['sampling'])] = $w;
            }
            foreach($manual as $m) {
                $manual_samp[strtotime($m['sampling'])] = $m;
            }

            $prev_time = strtotime($from);
            $data = [];
            $data_man = [];
            while ($prev_time <= strtotime($to)) {
                $prev_tanggal = tanggal_format($prev_time, true);
                $result['labels'][] = $prev_tanggal;

                $tele = floatval($wlev_samp[$prev_time]['wlev'])/100;
                $man = floatval($manual_samp[$prev_time]['manual'])/100;
                $data[] = $tele > 0 ? number_format($tele,2) : 0;
                $data_man[] = $man > 0 ? number_format($man,2) : 0;
                $prev_time += 300;
            }

            // $result['datasets'][] = [
            //     'label' => "TMA Manual (M)",
            //     'data' => $data_man,
            //     'backgroundColor' => "rgba(255, 0, 0, 0.5)",
            //     'borderColor' => "rgba(255, 0, 0, 1)",
            //     'fill' => false
            // ];
            $result['datasets'][] = [
                'label' => "TMA Telemetri (M)",
                'data' => $data,
                'backgroundColor' => "rgba(0, 0, 255, 0.5)",
                'borderColor' => "rgba(0, 0, 255, 1)",
                'fill' => false
            ];

            // separate manual dataset
            $result_man = [
                'dataset' => [],
                'labels' => []
            ];
            $prev_time = strtotime($from);
            $data_man = [];
            while ($prev_time <= strtotime($to)) {
                $prev_tanggal = date('Y-m-d', $prev_time);
                $hour7 = strtotime("{$prev_tanggal} 07:00");
                $hour12 = strtotime("{$prev_tanggal} 12:00");
                $hour17 = strtotime("{$prev_tanggal} 17:00");

                $man7 = floatval($manual_samp[$hour7]['manual'])/100;
                $man12 = floatval($manual_samp[$hour12]['manual'])/100;
                $man17 = floatval($manual_samp[$hour17]['manual'])/100;
                $data_man[] = $man7 > 0 ? number_format($man7,2) : 0;
                $data_man[] = $man12 > 0 ? number_format($man12,2) : 0;
                $data_man[] = $man17 > 0 ? number_format($man17,2) : 0;

                $result_man['labels'][] = tanggal_format($hour7, true);
                $result_man['labels'][] = tanggal_format($hour12, true);
                $result_man['labels'][] = tanggal_format($hour17, true);
                $prev_time += 86400;
            }
            $result_man['dataset'] = [
                'label' => "TMA Manual (M)",
                'data' => $data_man,
                'backgroundColor' => "rgba(255, 0, 0, 0.5)",
                'borderColor' => "rgba(255, 0, 0, 1)",
                'fill' => false
            ];
            // dump($result);

            return $this->view->render($response, 'tma/show.html', [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'lokasi' => $lokasi,
                'result' => $result,
                'result_man' => $result_man
            ]);
        })->setName('tma.show');

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

function getTMAdetail($app, $from, $to) {
    $depth = 12;  // nearby TMA depth search, in 5 minutes intervals
    $lokasi = $app->db->query("SELECT * FROM lokasi WHERE lokasi.jenis='2'")->fetchAll();
    foreach ($lokasi as $l) {
        // LOGGER
        $wlev = $app->db->query("SELECT * FROM periodik
                                WHERE lokasi_id = {$l['id']} AND wlev IS NOT NULL
                                    AND sampling BETWEEN '{$from}' AND '{$to}'
                                ORDER BY sampling, id")->fetchAll();

        $jam = [
            7 => 0,
            12 => 0,
            17 => 0
        ];
        $hour_minutes_wlev = [];
        $latest_wlev = 0;
        $latest_time = "";

        foreach ($wlev as $w) {
            $hour = (int) date('H', strtotime($w['sampling']));
            $minute = (int) date('i', strtotime($w['sampling']));
            $timestamp = "{$hour}:{$minute}";
            $hour_minutes_wlev[$timestamp] = $w['wlev'];

            $latest_wlev = $w['wlev'];
            $latest_time = $w['sampling'];
        }
        // dump($hour_minutes_wlev);

        // Set TMA value on timestamp, if null check nearby timestamp
        foreach ([7,12,17] as $t) {
            // today
            if (is_null($hour_minutes_wlev["{$t}:0"])) {
                for ($m = 1; $m <= $depth; $m++) {
                    $front = ($m * 5) % 60;
                    $back = 60 - $front;
                    $next = $t + (int) (($m * 5) / 60);
                    $prev = $t - 1 - (int) (($m * 5) / 60);
                    if (in_array("{$next}:{$front}", $hour_minutes_wlev)) {
                        if (!is_null($hour_minutes_wlev["{$next}:{$front}"])){
                            $jam[$t] = $hour_minutes_wlev["{$next}:{$front}"];
                            break;
                        }
                        if (!is_null($hour_minutes_wlev["{$prev}:{$back}"])){
                            $jam[$t] = $hour_minutes_wlev["{$prev}:{$back}"];
                            break;
                        }
                    }
                }
            } else {
                $jam[$t] = $hour_minutes_wlev["{$t}:0"];
            }
        }

        $jam7 = $jam[7] > 0 ? number_format(floatval($jam[7])/100,1) : '-';
        $jam12 = $jam[12] > 0 ? number_format(floatval($jam[12])/100,1) : '-';
        $jam17 = $jam[17] > 0 ? number_format(floatval($jam[17])/100,1) : '-';
        // $jam0 = $jam0 > 0 ? number_format($jam0,1) : '-';
        $latest_wlev = $latest_wlev > 0 ? number_format($latest_wlev,1) : '-';
        if (!empty($latest_time)) {
            $latest_time = date('H:i', strtotime($latest_time));
        }


        // MANUAL
        $wlev_manual = $app->db->query("SELECT * FROM tma
                                WHERE lokasi_id = {$l['id']} AND manual IS NOT NULL
                                    AND sampling BETWEEN '{$from}' AND '{$to}'
                                ORDER BY sampling")->fetchAll();

        $jam7_manual = 0;
        $jam12_manual = 0;
        $jam17_manual = 0;

        foreach ($wlev_manual as $w) {
            $time = date('H:i', strtotime($w['sampling']));
            $wlev = floatval($w['manual'])/100;
            switch ($time) {
                case '07:00':
                    $jam7_manual = $wlev;
                    break;
                case '12:00':
                    $jam12_manual = $wlev;
                    break;
                case '17:00':
                    $jam17_manual = $wlev;
                    break;
            }
        }

        $jam7_manual = $jam7_manual > 0 ? number_format($jam7_manual,2) : '-';
        $jam12_manual = $jam12_manual > 0 ? number_format($jam12_manual,2) : '-';
        $jam17_manual = $jam17_manual > 0 ? number_format($jam17_manual,2) : '-';


        $result[] = [
            'lokasi' => $l,
            'jam7' => $jam7,
            'jam12' => $jam12,
            'jam17' => $jam17,
            'jam0' => $jam0,
            'latest_wlev' => $latest_wlev,
            'latest_time' => $latest_time,
            'jam7_manual' => $jam7_manual,
            'jam12_manual' => $jam12_manual,
            'jam17_manual' => $jam17_manual,
        ];
    }

    return $result;
}
