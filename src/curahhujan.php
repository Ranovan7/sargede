<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Curah Hujan

$app->group('/curahhujan', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $hari = $request->getParam('sampling', date('Y-m-d'));//"2019-06-26");
        $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
        $next_date = date('Y-m-d', strtotime($hari .' +1day'));

        $end = date('Y-m-d', strtotime($hari .' +1day'));
        $from = "{$hari} 07:00:00";
        $to = "{$end} 06:55:00";
        $y_from = "{$prev_date} 07:00:00";
        $y_to = "{$hari} 06:55:00";

        $device = $this->db->query("SELECT * FROM device")->fetchAll();
        $logger_ids = [];
        foreach ($device as $d) {
            $logger_ids[] = $d['lokasi_id'];
        }

        $result = getCHdetail($this, $from, $to, $logger_ids);
        $y_result = getCHdetail($this, $y_from, $y_to, $logger_ids);

        return $this->view->render($response, 'curahhujan/index.html', [
            'sampling' => $hari,
            'yesterday' => tanggal_format(strtotime($prev_date)),
            'today' => tanggal_format(strtotime($hari)),
            'result' => $result,
            'y_result' => $y_result
        ]);
    })->setName('curahhujan');

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            $hari = $request->getParam('sampling', date('Y-m-d'));//"2019-06-01");
            $prev_date = date('Y-m-d', strtotime($hari .' -1day'));
            $next_date = date('Y-m-d', strtotime($hari .' +1day'));

            // preparing initial datasets (0s) and labels (hour)
            $result = [
                'datasets' => [],
                'labels' => []
            ];
            for($i = 0; $i < 24; ++$i) {
                $hour = ($i + 7) % 24;
                $result['labels'][] = "{$hour}:00";
                $result['datasets'][] = 0;
            }

            $end = date('Y-m-d', strtotime($hari .' +1day'));
            $from = "{$hari} 07:00:00";
            $to = "{$end} 06:55:00";

            $lokasi_id = $request->getAttribute('id');
            $lokasi = $this->db->query("SELECT * FROM lokasi WHERE id={$lokasi_id}")->fetch();

            $ch = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$lokasi_id} AND rain IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();

            $curr = 0;
            foreach ($ch as $c) {
                $current = date('H', strtotime($c['sampling']));
                $j = (intval($current) - 7 + 24) % 24;
                $result['datasets'][$j] = round($result['datasets'][$j] + $c['rain'], 2);
            }

            return $this->view->render($response, 'curahhujan/jamjaman.html', [
                'sampling' => tanggal_format(strtotime($hari)),
                'lokasi' => $lokasi,
                'prev_date' => $prev_date,
                'next_date' => $next_date,
                'result' => $result
            ]);
        })->setName('curahhujan.jamjaman');

        $this->get('/harian', function(Request $request, Response $response, $args) {
            $sampling = $request->getParam('sampling', date('Y-m-d'));//"2019-06-01");
            $month = date('m', strtotime($sampling));
            $year = date('Y', strtotime($sampling));
            $hari = date('Y-m-d', strtotime("{$year}-{$month}-1"));

            $prev_date = date('Y-m-d', strtotime($hari .' -1month'));
            $next_date = date('Y-m-d', strtotime($hari .' +1month'));

            // preparing initial datasets (0s) and labels (day)
            $result = [
                'datasets' => [],
                'datasets_man' => [],
                'labels' => []
            ];
            $i = 1;
            $current = date('Y-m-d', strtotime($hari));
            while (true) {
                if ($i == intval(date('d', strtotime($current)))) {
                    $result['datasets'][] = 0;
                    $result['datasets_man'][] = 0;
                    $result['labels'][] = tanggal_format(strtotime($current));
                    $i += 1;
                } else {
                    break;
                }
                $current = date('Y-m-d', strtotime($current .' +1day'));

            }

            $i -= 1;
            $end = date('Y-m-d', strtotime($hari ." +{$i}day"));
            $from = "{$hari} 07:00:00";
            $to = "{$end} 06:55:00";

            $lokasi_id = $request->getAttribute('id');
            $lokasi = $this->db->query("SELECT * FROM lokasi WHERE id={$lokasi_id}")->fetch();

            $ch = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$lokasi_id} AND rain IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();

            $ch_man = $this->db->query("SELECT * FROM curahujan
                                    WHERE lokasi_id = {$lokasi_id}
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();

            foreach ($ch as $c) {
                $current = date('d', strtotime($c['sampling'] ." -7hour"));
                $j = intval($current) - 1;
                $result['datasets'][$j] = round($result['datasets'][$j] + $c['rain'], 2);
            }
            foreach ($ch_man as $c) {
                echo "{$c['manual']}, ";
                $current = date('d', strtotime($c['sampling'] ." -7hour"));
                $j = intval($current) - 1;
                $result['datasets_man'][$j] = round($result['datasets_man'][$j] + $c['manual'], 2);
            }

            return $this->view->render($response, 'curahhujan/harian.html', [
                'sampling' => date('Y-m', strtotime($hari)),
                'lokasi' => $lokasi,
                'prev_date' => $prev_date,
                'next_date' => $next_date,
                'result' => $result
            ]);
        })->setName('curahhujan.harian');

        $this->get('/bulanan', function(Request $request, Response $response, $args) {
            $lokasi_id = $request->getAttribute('id');
            $lokasi = $this->db->query("SELECT * FROM lokasi WHERE id={$lokasi_id}")->fetch();

            // fetch all curahhujan (rain) data
            $ch = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$lokasi_id} AND rain IS NOT NULL
                                    ORDER BY sampling")->fetchAll();
            $ch_man = $this->db->query("SELECT * FROM curahujan
                                    WHERE lokasi_id = {$lokasi_id}
                                    ORDER BY sampling")->fetchAll();

            $result = getCHbulanan($ch, 'rain');
            $result['colors'] = [
                "0,0,255", "0,128,255", "0,255,255",
                "0,255,85", "0,255,170"
            ];
            $result_man = getCHbulanan($ch_man, 'manual');
            $result_man['colors'] = [
                "255,0,0", "255,128,0", "255,255,0",
                "85,255,0", "170,255,0"
            ];

            return $this->view->render($response, 'curahhujan/bulanan.html', [
                'sampling' => "Curah Hujan Bulanan",
                'lokasi' => $lokasi,
                'result' => $result,
                'result_man' => $result_man
            ]);
        })->setName('curahhujan.bulanan');

        $this->get('/maksimum', function(Request $request, Response $response, $args) {
            $lokasi_id = $request->getAttribute('id');
            $lokasi = $this->db->query("SELECT * FROM lokasi WHERE id={$lokasi_id}")->fetch();

            // fetch all curahhujan (rain) data
            $ch = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$lokasi_id} AND rain IS NOT NULL
                                    ORDER BY sampling")->fetchAll();
            $ch_man = $this->db->query("SELECT * FROM curahujan
                                    WHERE lokasi_id = {$lokasi_id}
                                    ORDER BY sampling")->fetchAll();

            $result = getCHmaximum($ch, 'rain');
            $result['colors'] = [
                "0,0,255", "0,128,255", "0,255,255",
                "0,255,85", "0,255,170"
            ];

            $result_man = getCHmaximum($ch_man, 'manual');
            $result_man['colors'] = [
                "255,0,0", "255,128,0", "255,255,0",
                "85,255,0", "170,255,0"
            ];

            return $this->view->render($response, 'curahhujan/maksimum.html', [
                'sampling' => "Curah Hujan Maksimum",
                'lokasi' => $lokasi,
                'result' => $result,
                'result_man' => $result_man
            ]);
        })->setName('curahhujan.maksimum');

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

            $chs = $this->db->query("SELECT * FROM periodik
                                    WHERE lokasi_id = {$lokasi_id} AND rain IS NOT NULL
                                        AND sampling BETWEEN '{$from}' AND '{$to}'
                                    ORDER BY sampling")->fetchAll();

            $result = [];
            for($i = 1; $i <= intval(date('d', strtotime($end_date))); $i++) {
                $hari_manual = date('Y-m-d', strtotime($hari .' -1day'));
                $ch_manual = $this->db->query("SELECT * FROM manual_daily
                                    WHERE lokasi_id = {$lokasi_id} AND rain IS NOT NULL
                                        AND sampling = '{$from}'")->fetch();
                $result[date("Y-m-d", strtotime("{$year}-{$month}-{$i}"))] = [
                    'durasi_07_13' => 0,
                    'durasi_13_19' => 0,
                    'durasi_19_01' => 0,
                    'durasi_01_07' => 0,
                    'durasi_all' => 0,
                    'durasi_manual' => $ch_manual ? $ch_manual['rain'] : null,
                ];
            }
            forEach($chs as $c) {
                $date = date("Y-m-d", strtotime($c['sampling'] .' -7hour'));
                $time = date('H:i:s', strtotime(date('H:i:s', strtotime($c['sampling'])) .' -7hour'));
                if ($time < '07:00:00') {
                    $result[$date]['durasi_07_13'] += $c['rain'];
                } else if ($time < '13:00:00') {
                    $result[$date]['durasi_13_19'] += $c['rain'];
                } else if ($time < '19:00:00') {
                    $result[$date]['durasi_19_01'] += $c['rain'];
                } else {
                    $result[$date]['durasi_01_07'] += $c['rain'];
                }
                $result[$date]['durasi_all'] += $c['rain'];
            }
            $result = array_reverse($result);

            return $this->view->render($response, 'curahhujan/periodik.html', [
                'sampling' => date('Y-m', strtotime($hari)),
                'lokasi' => $lokasi,
                'prev_date' => $prev_date,
                'next_date' => $next_date,
                'result' => $result
            ]);
        })->setName('curahhujan.periodik');

    });
});

function getCHbulanan($ch, $col_str) {
    $result = [
        'datasets' => [],
        'labels' => [],
        'colors' => [],
        'title' => []
    ];
    $result['labels'] = ['Januari', 'Februari', 'Maret',
        'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September',
        'Oktober', 'November', 'Desember',
    ];

    foreach ($ch as $c) {
        $year = date('Y', strtotime($c['sampling']));

        if (!array_key_exists($year, $result['datasets'])) {
            $result['datasets'][$year] = [];
            for ($i = 0; $i < 12; $i++) {
                $result['datasets'][$year][] = 0.0;
            }
            $result['title'][] = $year;
        }

        $month = date('m', strtotime($c['sampling'] .' -7hour'));
        $i = intval($month) -1;
        $result['datasets'][$year][$i] = round($result['datasets'][$year][$i] + $c[$col_str], 2);
    }
    return $result;
}

function getCHmaximum($ch, $col_str) {
    $result = [
        'datasets' => [],
        'labels' => [],
        'colors' => [],
        'title' => []
    ];
    $result['labels'] = ['Januari', 'Februari', 'Maret',
        'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September',
        'Oktober', 'November', 'Desember',
    ];

    foreach ($ch as $c) {
        $year = date('Y', strtotime($c['sampling']));
        if (!array_key_exists($year, $result['datasets'])) {
            // initialize array for the year of current periodik data
            $result['datasets'][$year] = [];
            for ($i = 0; $i < 12; $i++) {
                $result['datasets'][$year][] = [];
                for ($j = 0; $j < cal_days_in_month(CAL_GREGORIAN, $i, $year); $j++) {
                    $result['datasets'][$year][$i][] = 0;
                }
            }
            $result['title'][] = $year;
        }
        $month = date('m', strtotime($c['sampling'] .' -7hour'));
        $day = date('d', strtotime($c['sampling'] .' -7hour'));
        $i = intval($month) -1;
        $d = intval($day);
        $result['datasets'][$year][$i][$d] = round($result['datasets'][$year][$i][$d] + $c[$col_str], 2);
    }
    return $result;
}

function getmax($a, $b) {
    if ($a >= $b) {
        return $a;
    } else {
        return $b;
    }
}

function getCHdetail($app, $from, $to, $logger_ids) {
    $lokasi = $app->db->query("SELECT * FROM lokasi WHERE lokasi.jenis='1' OR lokasi.jenis='4'")->fetchAll();

    $result = [];
    foreach ($lokasi as $l) {
        $ch = $app->db->query("SELECT * FROM periodik
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

        if (!in_array($l['id'], $logger_ids)) {
            $durasi_07_13 = "-";
            $durasi_13_19 = "-";
            $durasi_19_01 = "-";
            $durasi_01_07 = "-";
            $durasi_all = "-";
        } else {
            $durasi_07_13 = "{$durasi_07_13} mm";
            $durasi_13_19 = "{$durasi_13_19} mm";
            $durasi_19_01 = "{$durasi_19_01} mm";
            $durasi_01_07 = "{$durasi_01_07} mm";
            $durasi_all = "{$durasi_all} mm";
        }

        $hari_manual = date('Y-m-d', strtotime($hari .' -1day'));
        $ch_manual = $app->db->query("SELECT * FROM manual_daily
                            WHERE lokasi_id = {$l['id']} AND rain IS NOT NULL
                                AND sampling = '{$from}'")->fetch();

        $result[] = [
            'lokasi' => $l,
            'durasi_07_13' => $durasi_07_13,
            'durasi_13_19' => $durasi_13_19,
            'durasi_19_01' => $durasi_19_01,
            'durasi_01_07' => $durasi_01_07,
            'durasi_all' => $durasi_all,
            'durasi_manual' => $ch_manual ? $ch_manual['rain'] : null,
        ];
    }

    return $result;
}
