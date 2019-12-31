<?php

use Slim\Http\Request;
use Slim\Http\Response;

// API periodik

$app->group('/device', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {

        return $response->withJson(["api" => "device"]);
    }); // get

    $this->post('[/]', function(Request $request, Response $response, $args) {
        // insert or update new lokasi
        // constraint : id should be the exact number as inputed id
        $sn = $request->getParam('sn');
        $device_id = $request->getParam('id');

        // check periodik existance first
        try {
            $device = $this->db->query("SELECT * FROM device
                                        WHERE sn='{$sn}'")->fetch();
        } catch (Exception $e) {
            return $response->withJson([
                "status" => "400",
                "message" => "Data not complete",
                "data" => [
                    "sn" => $sn
                ]
            ], 200, JSON_PRETTY_PRINT);
        }

        $params = [
            "temp_cor" => $request->getParam('temp_cor', ""),
            "humi_cor" => $request->getParam('humi_cor', ""),
            "batt_cor" => $request->getParam('batt_cor', ""),
            "tipp_fac" => $request->getParam('tipp_fac', ""),
            "ting_son" => $request->getParam('ting_son', ""),
            "lokasi_id" => $request->getParam('lokasi_id', ""),
            "tipe" => $request->getParam('tipe', ""),
            "latest_sampling" => $request->getParam('latest_sampling', ""),
            "latest_up" => $request->getParam('latest_up', ""),
            "latest_id" => $request->getParam('latest_id', "")
        ];
        $str_list = ["latest_sampling", "latest_up", "tipe"];

        if ($device) {
            // if sn already exist (update)
            $values = "";
            foreach ($params as $c => $p) {
                if (!empty($p)) {
                    if (in_array($c, $str_list)){
                        $values .= "{$c}='{$p}',";
                    } else {
                        $values .= "{$c}={$p},";
                    }
                }
            }
            $values = substr($values, 0, -1);
            $stmt = $this->db->prepare("UPDATE device SET
                                                    {$values}
                                                WHERE sn='{$sn}'");
            $stmt->execute();
        } else {
            // insert
            $columns = "sn, id";
            $values = "'{$sn}', {$device_id}";
            foreach ($params as $c => $p) {
                if (!empty($p)) {
                    $columns .= ",{$c}";
                    if (in_array($c, $str_list)){
                        $values .= ",'{$p}'";
                    } else {
                        $values .= ",{$p}";
                    }
                }
            }
            $stmt = $this->db->prepare("INSERT INTO device
                                                ({$columns})
                                                VALUES
                                                ({$values})");
            $stmt->execute();
        }

        return $response->withJson([
            "status" => "200",
            "message" => "Device berhasil ditambahkan/diupdate",
            "data" => [
                "sn" => $sn
            ]
        ], 200, JSON_PRETTY_PRINT);

    }); // post
});
