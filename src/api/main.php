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
        $lokasi = $this->db->query("SELECT * FROM lokasi")->fetchAll();

        $data = [];
        foreach($lokasi as $l){
            $data[] = [
                'lokasi' => $l['nama'],
                'manual' => [
                    'ch' => 0,
                    'sampling' => '2020-01-01'
                ],
                'device' => [
                    'ch' => 0,
                    'sampling' => '2020-01-01',
                    'persen_data' => 90.5,
                    'lastest_sampling'
                ]
            ];
        }

        return $response->withJson([
          'balai' => "Balai Wilayah Sungai 2 Sulawesi",
          'timezone' => "Asia/Makassar",
          'deskripsi' => "Data Curah Hujan diambil untuk data hari ini dimulai dari jam 7 pagi",
          'satuan' => "mm",
          'data' => $data
        ]);
    });

    $this->get('tma', function(Request $request, Response $response, $args) {
        $lokasi = $this->db->query("SELECT * FROM lokasi")->fetchAll();

        $data = [];
        foreach($lokasi as $l){
            $latest_dev = $this->db->query("SELECT * FROM periodik WHERE lokasi_id={$l['id']} ORDER BY sampling DESC")->fetch();
            $latest_man = $this->db->query("SELECT * FROM tma WHERE lokasi_id={$l['id']} ORDER BY sampling DESC")->fetch();

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
        ]);
    });
});
