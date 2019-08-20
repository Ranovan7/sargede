<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Main Route

// home

$app->get('[/]', function(Request $request, Response $response, $args) {
    return $this->view->render($response, 'main/index.html', [
        'hujan_sejak' => 90,
        'list_tma' => [
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
        ],
        'list_hujan' => [
            [
                'waktu' => "19 Agustus 2019",
                'daftar' => [
                    [
                        'pos' => "Pos Air 1",
                        'ch' => 18.0,
                        'durasi' => 55
                    ],
                ]
            ],
            [
                'waktu' => "18 Agustus 2019",
                'daftar' => [
                    [
                        'pos' => "Pos Air 1",
                        'ch' => 10.0,
                        'durasi' => 30
                    ],[
                        'pos' => "Pos Air 2",
                        'ch' => 8.0,
                        'durasi' => 25
                    ]
                ]
            ]
        ]
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
