<?php

use Slim\Http\Request;
use Slim\Http\Response;

// API periodik

$app->group('/periodik', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $lokasi_id = $request->getQueryParam('lokasi_id');
        if ($lokasi_id) { $lokasi_param = "AND lokasi_id=" . $lokasi_id . " "; }
        else { $lokasi_param = ""; }
        $sql = "SELECT * FROM periodik
            WHERE sampling <= NOW() " . $lokasi_param . 
            "ORDER BY sampling DESC LIMIT 1";
        $periodik = $this->db->query($sql)->fetchAll();
        return $response->withJson(array(periodik=>$periodik[0], lokasi_id=>$request->getQueryParam('lokasi_id')));
    }); // get

    $this->post('[/]', function(Request $req, Response $res, $args) {
        if (! $req->getParams()) { $ret = array(ok=>false); }
        else { $ret = array(ok=>true); }
        return $res->withJson($ret);
    
    }); // post
});
