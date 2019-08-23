<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Tinggi Muka Air

$app->group('/tma', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $hari = $request->getParam('sampling', "2019-06-26");//date('Y-m-d');
        $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
        $next_date = date('Y-m-d', strtotime($hari .' +1day'));
        $from = "{$hari} 00:00:00";
        $to = "{$hari} 23:55:00";
        // dump($to);
        $lokasi = $this->db->query("SELECT * FROM lokasi WHERE lokasi.jenis='2'")->fetchAll();
        $result = [];
        foreach ($lokasi as $l) {
            // LOGGER
            $wlev = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$l['id']} AND wlev IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();
            
            $jam6 = 0;
            $jam12 = 0;
            $jam18 = 0;
            $jam0 = 0;
            $latest_wlev = 0;
            $latest_time = "";

            foreach ($wlev as $w) {
                $time = date('H:i', strtotime($w['sampling']));
                switch ($time) {
                    case '06:00':
                        $jam6 = $w['wlev'];
                        break;
                    case '12:00':
                        $jam12 = $w['wlev'];
                        break;
                    case '18:00':
                        $jam18 = $w['wlev'];
                        break;
                    case '00:00':
                    case '24:00':
                        $jam0 = $w['wlev'];
                        break;
                }

                $latest_wlev = $w['wlev'];
                $latest_time = $w['sampling'];
            }

            $jam6 = $jam6 > 0 ? number_format($jam6,1) : '-';
            $jam12 = $jam12 > 0 ? number_format($jam12,1) : '-';
            $jam18 = $jam18 > 0 ? number_format($jam18,1) : '-';
            $jam0 = $jam0 > 0 ? number_format($jam0,1) : '-';
            $latest_wlev = $latest_wlev > 0 ? number_format($latest_wlev,1) : '-';
            if (!empty($latest_time)) {
                $latest_time = date('H:i', strtotime($latest_time));
            }


            // MANUAL
            $wlev_manual = $this->db->query("SELECT * FROM tma
                                    WHERE lokasi_id = {$l['id']} AND telemetri IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();

            $jam6_manual = 0;
            $jam12_manual = 0;
            $jam18_manual = 0;
            
            foreach ($wlev_manual as $w) {
                $time = date('H:i', strtotime($w['sampling']));
                switch ($time) {
                    case '06:00':
                        $jam6_manual = $w['telemetri'];
                        break;
                    case '12:00':
                        $jam12_manual = $w['telemetri'];
                        break;
                    case '18:00':
                        $jam18_manual = $w['telemetri'];
                        break;
                }
            }
            
            $jam6_manual = $jam6_manual > 0 ? number_format($jam6_manual,1) : '-';
            $jam12_manual = $jam12_manual > 0 ? number_format($jam12_manual,1) : '-';
            $jam18_manual = $jam18_manual > 0 ? number_format($jam18_manual,1) : '-';

            
            $result[] = [
                'lokasi' => $l,
                'jam6' => $jam6,
                'jam12' => $jam12,
                'jam18' => $jam18,
                'jam0' => $jam0,
                'latest_wlev' => $latest_wlev,
                'latest_time' => $latest_time,
                'jam6_manual' => $jam6_manual,
                'jam12_manual' => $jam12_manual,
                'jam18_manual' => $jam18_manual,
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
            $now = "2019-06-28";//date('Y-m-d');
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
                    $data[] = $prev_time == $current_time ? number_format($w['wlev'],2) : 0;
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
