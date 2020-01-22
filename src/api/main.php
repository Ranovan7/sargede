<?php

use Slim\Http\Request;
use Slim\Http\Response;

// API main

$app->group('/', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        return $response->withJson([
          'endpoint' => "Main API"
        ]);
    });

    $this->get('curahhujan', function(Request $request, Response $response, $args) {
        $lokasi = $this->db->query("SELECT lokasi.*, curahujan.sampling AS sampling FROM lokasi
                                    LEFT JOIN curahujan ON curahujan.id = (
                                            SELECT id from curahujan
                                                WHERE lokasi_id = lokasi.id
                                                ORDER BY sampling DESC
                                                LIMIT 1
                                        )
                                    WHERE lokasi.jenis = '1'
                                        OR lokasi.jenis = '4'")->fetchAll();

        $today = date("Y-m-d");
        $now = date("{$today} H:i");
        $start_date = new DateTime("{$start} 07:00:00");
        $dt_now = new DateTime("{$now}:00");
        if ($dt_now->h < 7) {
            $start = date('Y-m-d', strtotime($today ." -1day"));
        } else {
            $start = $today;
        }
        $start_date = new DateTime("{$start} 07:00:00");
        $since_start = $start_date->diff(new DateTime("{$now}:00"));

        $minutes = 0;
        $minutes = $since_start->days * 24 * 60;
        $minutes += $since_start->h * 60;
        $minutes += $since_start->i;
        $total_rec = intval($minutes / 5);
        // dump($total_rec);

        $data = [];
        foreach($lokasi as $l){
            $ch_manual = $this->db->query("SELECT * FROM curahujan
                                            WHERE lokasi_id={$l['id']}
                                                AND sampling > '{$start} 07:00:00'
                                            ORDER BY sampling DESC")->fetch();
            $ch_device = $this->db->query("SELECT * FROM periodik
                                            WHERE lokasi_id={$l['id']}
                                                AND sampling >= '{$start} 07:00:00'
                                            ORDER BY sampling DESC")->fetchAll();

            $latest_man_samp = NULL;
            $ch_man = NULL;
            if ($ch_manual['manual']) {
                $ch_man = $ch_manual['manual'];
                // $latest_man_samp = $man['sampling'];
            }

            $latest_dev_samp = NULL;
            $ch_dev = 0;
            $available_rec = 0;
            foreach ($ch_device as $dev) {
                $ch_dev += $dev['rain'];
                $latest_dev_samp = $dev['sampling'];
                $available_rec += 1;
            }


            $data[] = [
                'lokasi' => $l['nama'],
                'manual' => [
                    'ch' => $ch_man,
                    'sampling' => $today
                ],
                'device' => [
                    'ch' => $ch_dev,
                    'sampling' => str_replace(' ', 'T', $latest_dev_samp),
                    'persen_data' => round($available_rec/$total_rec, 2)
                ]
            ];
        }

        return $response->withJson([
          'balai' => "Balai Wilayah Sungai Sulawesi II",
          'timezone' => "Asia/Makassar (GMT+8)",
          'deskripsi' => "Data Curah Hujan diambil untuk data hari ini dimulai dari jam 7 pagi",
          'satuan' => "mm",
          'data' => $data
      ], 200, JSON_PRETTY_PRINT);
    });

    $this->get('tma', function(Request $request, Response $response, $args) {
        $lokasi = $this->db->query("SELECT * FROM lokasi
                                    WHERE jenis = '2'")->fetchAll();

        $data = [];
        foreach($lokasi as $l){
            $latest_man = $this->db->query("SELECT * FROM tma
                                            WHERE lokasi_id={$l['id']}
                                            ORDER BY sampling DESC")->fetch();
            $latest_dev = $this->db->query("SELECT * FROM periodik
                                            WHERE lokasi_id={$l['id']}
                                            ORDER BY sampling DESC")->fetch();

            $data[] = [
                'lokasi' => $l['nama'],
                'manual' => [
                    'tma' => $latest_man['wlev'],
                    'sampling' => $latest_man['sampling']
                ],
                'device' => [
                    'tma' => round($latest_dev['wlev'], 2),
                    'sampling' => $latest_dev['sampling']
                ]
            ];
        }

        return $response->withJson([
          'balai' => "Balai Wilayah Sungai Sulawesi II",
          'timezone' => "Asia/Makassar (GMT+8)",
          'deskripsi' => "Data Tinggi Muka Air diambil dari data terbaru/terakhir",
          'satuan' => "meter",
          'data' => $data
        ], 200, JSON_PRETTY_PRINT);
    });
});
