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

            $from = "{$start_date} 07:00:00";
            $to = "{$end_date} 07:00:00";
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
                $prev_tanggal = date("Y-m-d H:i", $prev_time);
                $result['labels'][] = $prev_tanggal;

                $tele = floatval($wlev_samp[$prev_time]['wlev'])/100;
                $man = floatval($manual_samp[$prev_time]['manual'])/100;
                $val = $tele > 0 ? number_format($tele,2) : 0;
                $data[] = $val;
                $prev_time += 300;
            }
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
            while ($prev_time + 86400 <= strtotime($to)) {
                $prev_tanggal = date('Y-m-d', $prev_time);
                $hour7 = strtotime("{$prev_tanggal} 07:00");
                $hour12 = strtotime("{$prev_tanggal} 12:00");
                $hour17 = strtotime("{$prev_tanggal} 17:00");

                $man7 = round(floatval($manual_samp[$hour7]['manual'])/100, 2);
                $man12 = round(floatval($manual_samp[$hour12]['manual'])/100, 2);
                $man17 = round(floatval($manual_samp[$hour17]['manual'])/100, 2);
                $tglhour7 = date("Y-m-d H:i", $hour7);
                $tglhour12 = date("Y-m-d H:i", $hour12);
                $tglhour17 = date("Y-m-d H:i", $hour17);

                $data_man[] = array(x => $tglhour7, y => $man7);
                $data_man[] = array(x => $tglhour12, y => $man12);
                $data_man[] = array(x => $tglhour17, y => $man17);

                $prev_time += 86400;
            }
            // foreach ($manual_samp as $sam => $val) {
            //     $time = tanggal_format($sam, true);
            //
            //     $man_val = round(floatval($val['manual'])/100, 2);
            //     $data_man[] = array(x => $time, y => $man_val);
            // }
            $result['datasets'][] = [
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

        $this->get('/periodik', function(Request $request, Response $response, $args) {
            $sampling = $request->getParam('sampling', date('Y-m-d'));//"2019-06-01");
            $lokasi_id = $request->getAttribute('id');
            $lokasi = $this->db->query("SELECT * FROM lokasi WHERE id={$lokasi_id}")->fetch();

            $month = date('m', strtotime($sampling));
            $year = date('Y', strtotime($sampling));
            $hari = date('Y-m-d', strtotime("{$year}-{$month}-1"));

            $prev_date = date('Y-m-d', strtotime($hari .' -1month'));
            $next_date = date('Y-m-d', strtotime($hari .' +1month'));
            $end_date = (date('m') == $month) ? date('Y-m-d') : date("Y-m-t", strtotime("{$year}-{$month}"));

            $end = date('Y-m-d', strtotime($end_date .' +1day'));
            $from = "{$hari} 07:00:00";
            $to = "{$end} 06:55:00";
            $y_from = "{$prev_date} 07:00:00";
            $y_to = "{$hari} 06:55:00";

            $wlev = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$lokasi_id} AND wlev IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling, id")->fetchAll();

            $result = [];
            $hour_minutes_wlev = [];
            $latest_wlev = 0;
            $latest_time = "";
            $depth = 12;
            for($i = 1; $i <= intval(date('d', strtotime($end_date))); $i++) {
                $date = date("Y-m-d", strtotime("{$year}-{$month}-{$i}"));
                $hour_minutes_wlev[$date] = [];
                $result[$date] = [
                    'jam7' => 0,
                    'jam12' => 0,
                    'jam17' => 0,
                    'latest_wlev' => 0,
                    'latest_time' => 0,
                    'jam7_manual' => 0,
                    'jam12_manual' => 0,
                    'jam17_manual' => 0,
                ];
                $wlev_manual = $this->db->query("SELECT * FROM tma
                                        WHERE lokasi_id = {$lokasi_id} AND manual IS NOT NULL
                                            AND EXTRACT(day FROM sampling) = {$i}
                                            AND EXTRACT(month FROM sampling) = {$month}
                                            AND EXTRACT(year FROM sampling) = {$year}
                                        ORDER BY sampling")->fetchAll();

                foreach ($wlev_manual as $w) {
                    $time = date('H:i', strtotime($w['sampling']));
                    $wlev = floatval($w['manual'])/100;
                    switch ($time) {
                        case '07:00':
                            $result[$date]['jam7_manual'] = $wlev;
                            break;
                        case '12:00':
                            $result[$date]['jam12_manual'] = $wlev;
                            break;
                        case '17:00':
                            $result[$date]['jam17_manual'] = $wlev;
                            break;
                    }
                }
            }
            forEach($wlev as $w) {
                $date = date("Y-m-d", strtotime("{$w['sampling']}"));
                $hour = intval(date('H', strtotime($w['sampling'])));
                $minute = intval(date('i', strtotime($w['sampling'])));
                $timestamp = "{$hour}:{$minute}";
                $hour_minutes_wlev[$date][$timestamp] = $w['wlev'];

                $latest_wlev = $w['wlev'];
                $latest_time = $w['sampling'];
            }

            // Set TMA value on timestamp, if null check nearby timestamp
            for($i = 1; $i <= intval(date('d', strtotime($end_date))); $i++) {
                $date = date("Y-m-d", strtotime("{$year}-{$month}-{$i}"));
                $jam = [
                    7 => 0,
                    12 => 0,
                    17 => 0
                ];
                foreach ([7,12,17] as $t) {
                    // today
                    if (is_null($hour_minutes_wlev[$date]["{$t}:0"])) {
                        for ($m = 1; $m <= $depth; $m++) {
                            $front = ($m * 5) % 60;
                            $back = 60 - $front;
                            $next = $t + (int) (($m * 5) / 60);
                            $prev = $t - 1 - (int) (($m * 5) / 60);
                            if (in_array("{$next}:{$front}", $hour_minutes_wlev[$date])) {
                                if (!is_null($hour_minutes_wlev[$date]["{$next}:{$front}"])){
                                    $jam[$t] = $hour_minutes_wlev[$date]["{$next}:{$front}"];
                                    break;
                                }
                                if (!is_null($hour_minutes_wlev[$date]["{$prev}:{$back}"])){
                                    $jam[$t] = $hour_minutes_wlev[$date]["{$prev}:{$back}"];
                                    break;
                                }
                            }
                        }
                    } else {
                        $jam[$t] = $hour_minutes_wlev[$date]["{$t}:0"];
                    }
                }
                $result[$date]['jam7'] = ($jam[7]) ? number_format(floatval($jam[7])/100,2) : null;
                $result[$date]['jam12'] = ($jam[12]) ? number_format(floatval($jam[12])/100,2) : null;
                $result[$date]['jam17'] = ($jam[17]) ? number_format(floatval($jam[17])/100,2) : null;
            }
            $result = array_reverse($result);
            // dump($hour_minutes_wlev);

            return $this->view->render($response, 'tma/periodik.html', [
                'sampling' => date('Y-m', strtotime($hari)),
                'lokasi' => $lokasi,
                'prev_date' => $prev_date,
                'next_date' => $next_date,
                'result' => $result
            ]);
        })->setName('tma.periodik');

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

        $jam7 = $jam[7] > 0 ? number_format(floatval($jam[7])/100,2) : '-';
        $jam12 = $jam[12] > 0 ? number_format(floatval($jam[12])/100,2) : '-';
        $jam17 = $jam[17] > 0 ? number_format(floatval($jam[17])/100,2) : '-';
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
