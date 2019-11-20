<?php

use Slim\Http\Request;
use Slim\Http\Response;

// API periodik

$app->group('/pos', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {

        return $response->withJson(array([]));
    }); // get

    $this->post('[/]', function(Request $request, Response $response, $args) {
        $params = $request->getParams();
        $sn = $request->getParam('sn');

        // check periodik existance first
        $pos_device = $this->db->query("SELECT * FROM device
                                    WHERE sn='{$sn}'")->fetch();

        $device = [
            "temp_cor" => $request->getParam('temp_cor', ""),
            "humi_cor" => $request->getParam('humi_cor', ""),
            "batt_cor" => $request->getParam('batt_cor', ""),
            "tipp_fac" => $request->getParam('tipp_fac', ""),
            "ting_son" => $request->getParam('ting_son', ""),
            "tipe" => $request->getParam('tipe', "")
        ];
        $lokasi = [
            "nama" => $request->getParam('nama', "Belum ada"),
            "ll" => $request->getParam('ll', "Belum ada"),
            "jenis" => $request->getParam('jenis', "0")
        ];

        if ($check) {
            // if sn already exist (update)
            $lokasi_id = $pos_device['lokasi_id'];

            $stmt_lokasi = $this->db->prepare("UPDATE lokasi SET
                                                    nama=:nama,
                                                    ll=:ll,
                                                    jenis=:jenis
                                                WHERE id={$lokasi_id}");
            $stmt_lokasi->execute([
                ':nama' => $lokasi["nama"],
                ':ll' => $lokasi["ll"],
                ':jenis' => $lokasi["jenis"]
            ]);

            $stmt = $this->db->prepare("UPDATE device SET
                                            temp_cor=:temp_cor
                                            humi_cor=:humi_cor
                                            batt_cor=:batt_cor
                                            tipp_fac=:tipp_fac
                                            ting_son=:ting_son
                                            tipe=':tipe'
                                        WHERE sn='{$sn}'");
            $stmt->execute([
                ":temp_cor" => $device["temp_cor"],
                ":humi_cor" => $device["humi_cor"],
                ":batt_cor" => $device["batt_cor"],
                ":tipp_fac" => $device["tipp_fac"],
                ":ting_son" => $device["ting_son"],
                ":tipe" => $device["tipe"]
            ]);
        } else {
            // if sn not exist (insert)
            $lokasi_val = "";
            foreach ($lokasi as $i => $l) {
                if ($i == 0) {
                    $lokasi_val += "'{$l}'";
                } else {
                    $lokasi_val += ",'{$l}'";
                }
            }
            $stmt_lokasi = $this->db->prepare("INSERT INTO lokasi
                                                (nama,ll,jenis)
                                                VALUES
                                                (:nama,:ll,:jenis)");
            $stmt_lokasi->execute([
                ":nama" => $lokasi['nama'],
                ":ll" => $lokasi['ll'],
                ":jenis" => $lokasi['jenis']
            ]);
            $lokasi_id = $this->db->lastInsertId();

            $columns = "sn,lokasi_id";
            $values = "'{$sn}',{$lokasi_id}";
            $str_list = ['tipe'];
            // $raw_form = "";
            // generating dynamic columns and values for insert
            foreach ($device as $col => $val) {
                if ($val) {
                    $columns .= ",{$col}";
                    if (in_array($col, $str_list)) {
                        $values .= ",'{$val}'";
                    } else {
                        $values .= ",{$val}";
                    }
                }
            }
            $stmt = $this->db->prepare("INSERT INTO device ({$columns}) VALUES ($values)");
            $stmt->execute();
        }

        return $response->withJson([
            "status" => "200",
            "message" => "Success at Addition/Update Device and Location",
            "data" => [
                "sn" => $sn
            ]
        ], 200, JSON_PRETTY_PRINT);

    }); // post
});
