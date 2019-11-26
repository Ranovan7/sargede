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

    $this->post('[/]', function(Request $request, Response $response, $args) {
        $params = $request->getParams();
        $sampling = $request->getParam('sampling');
        $lokasi_id = $request->getParam('lokasi_id');

        // check if periodik exist first
        $check = $this->db->query("SELECT * FROM periodik
                                    WHERE sampling='{$sampling}'
                                        AND lokasi_id={$lokasi_id}")->fetch();
        if ($check) {
            return $response->withJson([
                "status" => "208",
                "message" => "periodik already exist"
            ]);
        }

        // insert if not exist
        $data = [
            "device_sn" => $request->getParam('device_sn', ""),
            "mdpl" => $request->getParam('mdpl', ""),
            "apre" => $request->getParam('apre', ""),
            "sq" => $request->getParam('sq', ""),
            "temp" => $request->getParam('temp', ""),
            "humi" => $request->getParam('humi', ""),
            "batt" => $request->getParam('batt', ""),
            "rain" => $request->getParam('rain', ""),
            "wlev" => $request->getParam('wlev', ""),
            "up_s" => $request->getParam('up_s', ""),
            "ts_a" => $request->getParam('ts_a', ""),
            "recieved" => $request->getParam('recieved', "")
        ];
        $columns = "sampling,lokasi_id";
        $values = "'{$sampling}',{$lokasi_id}";
        $str_list = ['device_sn', 'up_s', 'ts_a', 'recieved'];
        // generating dynamic columns and values for insert
        foreach ($data as $col => $val) {
            if ($val) {
                $columns .= ",{$col}";
                if (in_array($col, $str_list)) {
                    $values .= ",'{$val}'";
                } else {
                    $values .= ",{$val}";
                }
            }
        }
        $stmt = $this->db->prepare("INSERT INTO periodik ({$columns}) VALUES ($values)");
        $stmt->execute();

        return $response->withJson([
            "status" => "200",
            "message" => "insertion succeded",
            "data" => [
                "columns" => "({$columns})",
                "values" => "({$values})",
            ]
        ], 200, JSON_PRETTY_PRINT);

    }); // post
});
