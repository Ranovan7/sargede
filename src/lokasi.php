<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Map

$app->group('/lokasi', function() {

    $this->get('[/]', function(Request $request, Response $response, $args) {
        $lokasis = $this->db->query("SELECT * FROM lokasi ORDER BY nama")->fetchAll();
        return $this->view->render($response, 'lokasi/index.html', [
            'lokasis' => $lokasis
        ]);
    })->setName('lokasi');

    $this->group('/{id:[0-9]+}', function() {

        $this->post('/update', function(Request $request, Response $response, $args) {
            return $this->response->withRedirect('/lokasi');

            // no more support
            $id = intval($args['id']);
            $stmt = $this->db->prepare("SELECT * FROM lokasi WHERE id=:id");
            $stmt->execute([':id' => $id]);
            $lokasi = $stmt->fetch();
            if (!$lokasi) {
                throw new \Slim\Exception\NotFoundException($request, $response);
            }

            $form = $request->getParams();
            // dump($form);
            $stmt = $this->db->prepare("UPDATE lokasi SET nama=:nama, jenis=:jenis, ll=:ll, siaga1=:siaga1, siaga2=:siaga2, siaga3=:siaga3 WHERE id=:id");
            $res = $stmt->execute([
                ':nama' => $form['nama'],
                ':jenis' => $form['jenis'],
                ':ll' => isset($form['ll']) ? $form['ll'] : $lokasi['ll'],
                ':siaga1' => $form['siaga1'] != '' ? $form['siaga1'] : $lokasi['siaga1'],
                ':siaga2' => $form['siaga2'] != '' ? $form['siaga2'] : $lokasi['siaga2'],
                ':siaga3' => $form['siaga3'] != '' ? $form['siaga3'] : $lokasi['siaga3'],
                ':id' => $lokasi['id'],
            ]);
            
            return $this->response->withRedirect('/lokasi');
        })->setName('lokasi.update');

    });

})->add($adminRoleMiddleware)->add($loggedinMiddleware);
