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
                    "sn" => $sn
                ]
            ], 200, JSON_PRETTY_PRINT);
        }

        $params = [
            "nama" => $request->getParam('nama', "Belum ada"),
            "ll" => $request->getParam('ll', "Belum ada"),
            "jenis" => $request->getParam('jenis', "0")
        ];

        if ($lokasi) {
            // if sn already exist (update)
            $stmt = $this->db->prepare("UPDATE lokasi SET
                                                    nama=:nama,
                                                    ll=:ll,
                                                    jenis=:jenis
                                                WHERE id={$lokasi_id}");
            $stmt->execute([
                ':nama' => $params["nama"],
                ':ll' => $params["ll"],
                ':jenis' => $params["jenis"]
            ]);
        } else {
            // insert
            $stmt = $this->db->prepare("INSERT INTO lokasi
                                                (id,nama,ll,jenis)
                                                VALUES
                                                (:id,:nama,:ll,:jenis)");
            $stmt->execute([
                ":id" => $lokasi_id,
                ":nama" => $params['nama'],
                ":ll" => $params['ll'],
                ":jenis" => $params['jenis']
            ]);
        }

        return $response->withJson([
            "status" => "200",
            "message" => "Lokasi berhasil ditambahkan/diupdate",
            "data" => [
                "sn" => $sn
            ]
        ], 200, JSON_PRETTY_PRINT);

    }); // post
});
