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
            $wlev = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$l['id']} AND wlev IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();
            
            $jam6 = [0,0];
            $jam12 = [0,0];
            $jam18 = [0,0];
            $jam0 = [0,0];
            $latest_wlev = 0;
            $latest_time = "";

            foreach ($wlev as $w) {
                $time = date('H:i:s', strtotime($w['sampling']));
                if ($time < '06:05:00') {
                    $jam6[0] += $w['wlev'];
                    $jam6[1]++;
                } else if ($time < '12:05:00') {
                    $jam12[0] += $w['wlev'];
                    $jam12[1]++;
                } else if ($time < '18:05:00') {
                    $jam18[0] += $w['wlev'];
                    $jam18[1]++;
                } else {
                    $jam0[0] += $w['wlev'];
                    $jam0[1]++;
                }

                $latest_wlev = $w['wlev'];
                $latest_time = $w['sampling'];
            }

            $jam6 = $jam6[1] > 0 ? number_format($jam6[0]/$jam6[1],2) : '-';
            $jam12 = $jam12[1] > 0 ? number_format($jam12[0]/$jam12[1],2) : '-';
            $jam18 = $jam18[1] > 0 ? number_format($jam18[0]/$jam18[1],2) : '-';
            $jam0 = $jam0[1] > 0 ? number_format($jam0[0]/$jam0[1],2) : '-';
            $latest_wlev = $latest_wlev > 0 ? number_format($latest_wlev,2) : '-';
            if (!empty($latest_time)) {
                $latest_time = tanggal_format(strtotime($latest_time), true);
            }
            
            $result[] = [
                'lokasi' => $l,
                'jam6' => $jam6,
                'jam12' => $jam12,
                'jam18' => $jam18,
                'jam0' => $jam0,
                'latest_wlev' => $latest_wlev,
                'latest_time' => $latest_time,
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
            $now = "2019-06-26";//date('Y-m-d');
            $start_date = $request->getParam('start_date', $now);
            $end_date = $request->getParam('end_date', date('Y-m-d', strtotime('-2day')));

            $result = [];
            $datasets = [];
            $labels = [];

            return $this->view->render($response, 'tma/show.html', [
                'start_date' => tanggal_format(strtotime($start_date)),
                'end_date' => tanggal_format(strtotime($end_date)),
                'lokasi' => $lokasi,
                'result' => $result,
                'datasets' => $datasets,
                'labels' => $labels,
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
