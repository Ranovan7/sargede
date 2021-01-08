<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Map

$app->group('/map', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $lokasis_raw = $this->db->query("SELECT * FROM lokasi")->fetchAll();
	    $lokasis = [];
	    foreach ($lokasis_raw as $l) {
	        if (strpos($l['ll'], ",") !== false ) {
	            $lokasis[] = $l;
	        }
	    }
        return $this->view->render($response, 'main/map.html', [
            'lokasis' => $lokasis
        ]);
    });

    // Peta Pos CH
    $this->get('/ch', function(Request $req, Response $res, $args) {
        $lokasis_raw = $this->db->query("SELECT * FROM lokasi WHERE jenis='1'")->fetchAll();
	    $lokasis = [];
	    foreach ($lokasis_raw as $l) {
	        if (strpos($l['ll'], ",") !== false ) {
	            $lokasis[] = $l;
	        }
	    }
        return $this->view->render($res, 'map/ch.html', [
            'lokasis' => $lokasis
        ]);
    });

    // Peta Pos TMA
    $this->get('/tma', function(Request $req, Response $res, $args) {
        $lokasis_raw = $this->db->query("SELECT * FROM lokasi WHERE jenis='2'")->fetchAll();
	    $lokasis = [];
	    foreach ($lokasis_raw as $l) {
	        if (strpos($l['ll'], ",") !== false ) {
	            $lokasis[] = $l;
	        }
	    }
        return $this->view->render($res, 'map/tma.html', [
            'lokasis' => $lokasis
        ]);
    });

    // peta pos Klimatologi
    $this->get('/klimatologi', function(request $req, response $res, $args) {
        $lokasis_raw = $this->db->query("SELECT * FROM lokasi WHERE jenis='4'")->fetchAll();
	    $lokasis = [];
	    foreach ($lokasis_raw as $l) {
	        if (strpos($l['ll'], ",") !== false ) {
	            $lokasis[] = $l;
	        }
	    }
        return $this->view->render($res, 'map/klimatologi.html', [
            'lokasis' => $lokasis
        ]);
    });
    // peta pos Kualitas Air
    $this->get('/kualitasair', function(request $req, response $res, $args) {
        $lokasis_raw = $this->db->query("SELECT * FROM lokasi WHERE jenis='3'")->fetchAll();
	    $lokasis = [];
	    foreach ($lokasis_raw as $l) {
	        if (strpos($l['ll'], ",") !== false ) {
	            $lokasis[] = $l;
	        }
	    }
        return $this->view->render($res, 'map/kualitasair.html', [
            'lokasis' => $lokasis
        ]);
    });

    // peta pos Klimatologi
    $this->get('/test', function(request $req, response $res, $args) {
        $lokasis_raw = $this->db->query("SELECT * FROM lokasi")->fetchAll();
	    $lokasis = [];
	    foreach ($lokasis_raw as $l) {
	        if (strpos($l['ll'], ",") !== false ) {
	            $lokasis[] = $l;
	        }
	    }
        return $this->view->render($res, 'map/testmap.html', [
            'lokasis' => $lokasis
        ]);
    });
});
