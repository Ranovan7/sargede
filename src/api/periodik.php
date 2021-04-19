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
        try {
            $check = $this->db->query("SELECT * FROM periodik
                                        WHERE sampling='{$sampling}'
                                            AND lokasi_id={$lokasi_id}")->fetch();
            if ($check) {
                return $response->withJson([
                    "status" => "208",
                    "message" => "periodik already exist"
                ]);
            }
        } catch (Exception $e) {
            return $response->withJson([
                "status" => "500",
                "message" => "Data incomplete or formatted incorrectly"
            ]);
        }

        // insert if not exist
        $data = [
            "device_sn" => $request->getParam('device_sn', null),
            "mdpl" => $request->getParam('mdpl', null),
            "apre" => $request->getParam('apre', null),
            "sq" => $request->getParam('sq', null),
            "temp" => $request->getParam('temp', null),
            "humi" => $request->getParam('humi', null),
            "batt" => $request->getParam('batt', null),
            "rain" => $request->getParam('rain', null),
            "wlev" => $request->getParam('wlev', null),
            "wind_speed" => $request->getParam('wind_speed', null),
            "wind_dir" => $request->getParam('wind_dir', null),
            "sun_rad" => $request->getParam('sun_rad', null),
            "up_s" => $request->getParam('up_s', null),
            "ts_a" => $request->getParam('ts_a', null),
            "received" => $request->getParam('received', null)
        ];
        $columns = "sampling,lokasi_id";
        $values = "'{$sampling}',{$lokasi_id}";
        $str_list = ['device_sn', 'up_s', 'ts_a', 'received', 'wind_dir'];
        // generating dynamic columns and values for insert
        foreach ($data as $col => $val) {
            if ($val !== null) {
                $columns .= ",{$col}";
                if (in_array($col, $str_list)) {
                    $values .= ",'{$val}'";
                } else {
                    $values .= ",{$val}";
                }
            }
        }
        try {
            $stmt = $this->db->prepare("INSERT INTO periodik ({$columns}) VALUES ($values)");
            $stmt->execute();
            $message = "insertion succeded";
        } catch (Exception $e) {
            $message = "Error when trying to record : {$e}";
        }

        return $response->withJson([
            "status" => "200",
            "message" => $message,
            "data" => [
                "columns" => "({$columns})",
                "values" => "({$values})",
            ]
        ], 200, JSON_PRETTY_PRINT);
    }); // post

    $this->post('/bulk', function(Request $request, Response $response, $args) {
        $params = $request->getParams();
        $tenant = $params['tenant'];
        $periodics = $params['data'];
        $inserted = 0;
        $errors = 0;
        // dump($periodics);

        foreach ($periodics as $per) {
            $data = [
                "device_sn" => !empty($per['device_sn']) ? $per['device_sn'] : "",
                "mdpl" => !empty($per['mdpl']) ? $per['mdpl'] : "",
                "apre" => !empty($per['apre']) ? $per['apre'] : "",
                "sq" => !empty($per['sq']) ? $per['sq'] : "",
                "temp" => !empty($per['temp']) ? $per['temp'] : "",
                "humi" => !empty($per['humi']) ? $per['humi'] : "",
                "batt" => !empty($per['batt']) ? $per['batt'] : "",
                "rain" => !empty($per['rain']) ? $per['rain'] : "",
                "wlev" => !empty($per['wlev']) ? $per['wlev'] : "",
                "wind_speed" => !empty($per['wind_speed']) ? $per['wind_speed'] : "",
                "wind_dir" => !empty($per['wind_dir']) ? $per['wind_dir'] : "",
                "sun_rad" => !empty($per['sun_rad']) ? $per['sun_rad'] : "",
                "up_s" => !empty($per['up_s']) ? $per['up_s'] : "",
                "ts_a" => !empty($per['ts_a']) ? $per['ts_a'] : ""
            ];
            $columns = "sampling,lokasi_id";
            $values = "'{$per['sampling']}',{$per['lokasi_id']}";
            $str_list = ['device_sn', 'up_s', 'ts_a', 'received'];
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
            // dump($data);
            try {
                $stmt = $this->db->prepare("INSERT INTO periodik ({$columns}) VALUES ($values)");
                $stmt->execute();
                $message = "insertion succeded";
                $inserted += 1;
            } catch (Exception $e) {
                $message = "Error when trying to record : {$e}";
                $errors += 1;
            }
        }

        return $response->withJson([
            "status" => "200",
            "message" => $tenant,
            "data" => [
                "success" => "{$inserted}",
                "errors" => "{$errors}",
            ]
        ], 200, JSON_PRETTY_PRINT);
    }); // post bulk
});
