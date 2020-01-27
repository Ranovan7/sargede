<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/tma', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $hari = $request->getParam('sampling', date('Y-m-d'));//"2019-06-26");
        $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
        $next_date = date('Y-m-d', strtotime($hari .' +1day'));
        $from = "{$hari} 00:00:00";
        $to = "{$hari} 23:55:00";
        // dump($to);

        $depth = 12;  // nearby TMA depth search, in 5 minutes intervals
        $lokasi = $this->db->query("SELECT * FROM lokasi WHERE lokasi.jenis='2'")->fetchAll();
        $result = [];
        foreach ($lokasi as $l) {
            // LOGGER
            $wlev = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$l['id']} AND wlev IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling, id")->fetchAll();

            $jam = [];
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
                if (is_null($hour_minutes_wlev["{$t}:0"])) {
                    for ($m = 1; $m <= $depth; $m++) {
                        $front = ($m * 5) % 60;
                        $back = 60 - $front;
                        $next = $t + (int) (($m * 5) / 60);
                        $prev = $t - 1 - (int) (($m * 5) / 60);
                        if (!is_null($hour_minutes_wlev["{$next}:{$front}"])){
                            $jam[$t] = $hour_minutes_wlev["{$next}:{$front}"];
                            break;
                        }
                        if (!is_null($hour_minutes_wlev["{$prev}:{$back}"])){
                            $jam[$t] = $hour_minutes_wlev["{$prev}:{$back}"];
                            break;
                        }
                    }
                } else {
                    $jam[$t] = $hour_minutes_wlev["{$t}:0"];
                }
            }

            $jam7 = $jam[7] > 0 ? number_format($jam[7],1) : '-';
            $jam12 = $jam[12] > 0 ? number_format($jam[12],1) : '-';
            $jam17 = $jam[17] > 0 ? number_format($jam[17],1) : '-';
            // $jam0 = $jam0 > 0 ? number_format($jam0,1) : '-';
            $latest_wlev = $latest_wlev > 0 ? number_format($latest_wlev,1) : '-';
            if (!empty($latest_time)) {
                $latest_time = date('H:i', strtotime($latest_time));
            }


            // MANUAL
            $wlev_manual = $this->db->query("SELECT * FROM tma
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

        return $this->view->render($response, 'tma/index.html', [
            'sampling' => tanggal_format(strtotime($hari)),
            'prev_date' => $prev_date,
            'next_date' => $next_date,
            'result' => $result,
        ]);
    })->setName('tma');

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            $lokasi = $request->getAttribute('lokasi');
            $now = date('Y-m-d');//"2019-06-28";
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

            $prev_time = strtotime($from);
            $data = [];
            foreach ($wlev as $w) {
                $current_time = strtotime($w['sampling']);
                while ($prev_time <= $current_time) {
                    $prev_tanggal = tanggal_format($prev_time, true);
                    $result['labels'][] = $prev_tanggal;
                    $data[] = $prev_time == $current_time && $w['wlev'] > 0 ? number_format($w['wlev'],2) : 0;
                    $prev_time += 300;
                }
            }

            while ($prev_time <= strtotime($to)) {
                $prev_tanggal = tanggal_format($prev_time, true);
                $result['labels'][] = $prev_tanggal;
                $data[] = 0;
                $prev_time += 300;
            }

            $result['datasets'][] = [
                'label' => "Tinggi Mata Air",
                'data' => $data,
                'backgroundColor' => "rgba(255, 255, 255, 0.5)",
                'borderColor' => "rgba(205, 50, 0, 0.5)",
                'fill' => false
            ];

            // dump($result);

            return $this->view->render($response, 'tma/show.html', [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'lokasi' => $lokasi,
                'result' => $result,
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
