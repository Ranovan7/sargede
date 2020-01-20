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
        $lokasi = $this->db->query("SELECT * FROM lokasi
                                    WHERE jenis = '1'
                                        OR jenis = '4'")->fetchAll();

        $data = [];
        foreach($lokasi as $l){
            $today = date("Y-m-d");
            $now = date("Y-m-d H:i");

            $start_date = new DateTime("{$today} 04:10:58");
            $since_start = $start_date->diff(new DateTime("{$now}:00"));
            $minutes = 0;
            $minutes = $since_start->days * 24 * 60;
            $minutes += $since_start->h * 60;
            $minutes += $since_start->i;
            $total_rec = intval($minutes / 5);
            // dump($total_rec);

            $ch_manual = $this->db->query("SELECT * FROM curahujan
                                            WHERE lokasi_id={$l['id']}
                                                AND sampling > '{$today} 07:00:00'
                                            ORDER BY sampling DESC")->fetchAll();
            $ch_device = $this->db->query("SELECT * FROM periodik
                                            WHERE lokasi_id={$l['id']}
                                                AND sampling >= '{$today} 07:00:00'
                                            ORDER BY sampling DESC")->fetchAll();

            $latest_man_samp = NULL;
            $ch_man = 0;
            foreach ($ch_manual as $man) {
                $ch_man += $man['manual'];
                $latest_man_samp = $man['sampling'];
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
                    'sampling' => "{$today} 07:00 - {$now}",
                    'lastest_sampling' => $latest_man_samp
                ],
                'device' => [
                    'ch' => $ch_dev,
                    'sampling' => "{$today} 07:00 - {$now}",
                    'persen_data' => round($available_rec/$total_rec, 2),
                    'lastest_sampling' => $latest_dev_samp
                ]
            ];
        }

        return $response->withJson([
          'balai' => "Balai Wilayah Sungai 2 Sulawesi",
          'timezone' => "Asia/Makassar",
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
          'balai' => "Balai Wilayah Sungai 2 Sulawesi",
          'timezone' => "Asia/Makassar",
          'deskripsi' => "Data Tinggi Muka Air diambil dari data terbaru/terakhir",
          'satuan' => "meter",
          'data' => $data
        ], 200, JSON_PRETTY_PRINT);
    });
});
