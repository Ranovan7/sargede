<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Curah Hujan

$app->group('/curahhujan', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        return $this->view->render($response, 'curahhujan/index.html', [
            "sampling" => "19 Agustus 2019",
            "lokasi" => [
                [
                    'id' => 1,
                    'nama' => "Pos Air 1"
                ],
                [
                    'id' => 2,
                    'nama' => "Pos Air 2"
                ]
            ],
            "result" => [
                [
                    'durasi_07-13' => 15.0,
                    'durasi_13-19' => 10.0,
                    'durasi_19-01' => 5.0,
                    'durasi_01-07' => 20.0,
                    'durasi_all' => 50.0
                ],
                [
                    'durasi_07-13' => 15.0,
                    'durasi_13-19' => 10.0,
                    'durasi_19-01' => 5.0,
                    'durasi_01-07' => 20.0,
                    'durasi_all' => 50.0
                ]
            ]
        ]);
    })->setName('curahhujan');

    $this->group('/{id}', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/jamjaman.html', [
                'pos' => [
                    'id' => 1,
                    'nama' => "Pos Air 1"
                ]
            ]);
        })->setName('curahhujan.jamjaman');
    });

    $this->group('/{id}/harian', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/harian.html');
        })->setName('curahhujan.harian');
    });

    $this->group('/{id}/bulanan', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/bulanan.html');
        })->setName('curahhujan.bulanan');
    });

    $this->group('/{id}/maksimum', function() {

        $this->get('[/]', function(Request $request, Response $response, $args) {
            return $this->view->render($response, 'curahhujan/maksimum.html');
        })->setName('curahhujan.maksimum');
    });
});
