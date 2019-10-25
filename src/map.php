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
});
