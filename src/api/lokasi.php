<?php

use Slim\Http\Request;
use Slim\Http\Response;

// API periodik

$app->group('/lokasi', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {

        return $response->withJson(["api" => "lokasi"]);
    }); // get

    $this->post('[/]', function(Request $request, Response $response, $args) {
        // insert or update new lokasi
        // constraint : id should be the exact number as inputed id
        $lokasi_id = $request->getParam('id');

        // check periodik existance first
        try {
            $lokasi = $this->db->query("SELECT * FROM lokasi
                                        WHERE id='{$lokasi_id}'")->fetch();
        } catch (Exception $e) {
            return $response->withJson([
                "status" => "400",
                "message" => "Data not complete",
                "data" => [
                    "lokasi_id" => $lokasi_id
                ]
            ], 200, JSON_PRETTY_PRINT);
        }

        $params = [
            "nama" => $request->getParam('nama', "Belum ada"),
            "ll" => $request->getParam('ll', "Belum ada"),
            "jenis" => $request->getParam('jenis', "0"),
            "siaga1" => $request->getParam('siaga1', null),
            "siaga2" => $request->getParam('siaga2', null),
            "siaga3" => $request->getParam('siaga3', null)
        ];

        if ($lokasi) {
            // if lokasi already exist (update)
            $stmt = $this->db->prepare("UPDATE lokasi SET
                                                    nama=:nama,
                                                    ll=:ll,
                                                    jenis=:jenis,
                                                    siaga1=:siaga1,
                                                    siaga2=:siaga2,
                                                    siaga3=:siaga3
                                                WHERE id={$lokasi_id}");
            $stmt->execute([
                ':nama' => $params["nama"],
                ':ll' => $params["ll"],
                ':jenis' => $params["jenis"],
                ':siaga1' => $params["siaga1"],
                ':siaga2' => $params["siaga2"],
                ':siaga3' => $params["siaga3"]
            ]);
        } else {
            // insert
            $stmt = $this->db->prepare("INSERT INTO lokasi
                                                (id,nama,ll,jenis,siaga1,siaga2,siaga3)
                                                VALUES
                                                (:id,:nama,:ll,:jenis,:siaga1,:siaga2,:siaga3)");
            $stmt->execute([
                ":id" => $lokasi_id,
                ":nama" => $params['nama'],
                ":ll" => $params['ll'],
                ":jenis" => $params['jenis'],
                ':siaga1' => $params["siaga1"],
                ':siaga2' => $params["siaga2"],
                ':siaga3' => $params["siaga3"]
            ]);
        }

        return $response->withJson([
            "status" => "200",
            "message" => "Lokasi berhasil ditambahkan/diupdate",
            "data" => [
                "lokasi_id" => $lokasi_id
            ]
        ], 200, JSON_PRETTY_PRINT);

    }); // post
});
