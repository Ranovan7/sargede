<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Main Route

// home

$app->get('[/]', function(Request $request, Response $response, $args) {
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

    $tma = [
        [
            'waktu' => "19 Agustus 2019",
            'jam' => "16:00",
            'pos' => "Pos Air 21",
            'wlev' => 58
        ],
        [
            'waktu' => "19 Agustus 2019",
            'jam' => "17:00",
            'pos' => "Pos Air 22",
            'wlev' => 100
        ],
        [
            'waktu' => "19 Agustus 2019",
            'jam' => "17:00",
            'pos' => "Pos Air 23",
            'wlev' => 30
        ],
    ];

    $result = [
        'tma' => [],
        'curahhujan' => []
    ];
    $result['tma'] = $tma;

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

    return $this->view->render($response, 'main/index.html', [
        'hujan_sejak' => $sejak,
        'result' => $result,
    ]);
});

$app->get('/dashboard', function(Request $request, Response $response, $args) {

});


// Auth User

$app->get('/login', function(Request $request, Response $response, $args) {

});
$app->post('/login', function(Request $request, Response $response, $args) {

});

$app->post('/logout', function(Request $request, Response $response, $args) {

});
