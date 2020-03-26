<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/carousel', function () {
    // URL: /carousel
    $this->get('[/]', function (Request $request, Response $response, $args) {
        $directory = "uploads/carousel";
        $carousels = $this->db->query("SELECT * FROM carousel ORDER BY id_order")->fetchAll();
        // dump($carousels);
        return $this->view->render($response, 'carousel/index.html', [
            'directory' => $directory,
            'carousels' => $carousels,
        ]);
    });

    $this->post('/add', function (Request $request, Response $response, $args) {
        $directory = $this->get('upload_directory') . "/carousel";
        if (!is_dir($directory)) {
            if (!is_dir($this->get('upload_directory'))) {
                mkdir($this->get('upload_directory'), 0775);
            }
            mkdir($directory, 0775);
        }

        $uploadedFiles = $request->getUploadedFiles();

        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['file'];
        $filename = '';
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $file_ok = true;

            // check ext
            $whitelist = ['jpg', 'jpeg', 'png', 'gif'];
            $extension = strtolower(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
            if (!in_array($extension, $whitelist)) {
                $file_ok = false;
                $this->flash->addMessage('errors', "Jenis file tidak dikenali, silahkan upload image dengan jenis: " . implode(", ", $whitelist));
            }

            // check size in KB, MAX 5*1024 KB
            $size = intval($uploadedFile->getSize() / 1024);
            if ($size > 5 * 1024) {
                $file_ok = false;
                $this->flash->addMessage('errors', "File terlalu besar, maksimal adalah 5 MB");
            }

            if (!$file_ok) {
                return $response->withRedirect('/carousel');
            }

            $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
            $filename = sprintf('%s.%0.8s', $basename, $extension);

            $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        }

        if (!file_exists($directory . DIRECTORY_SEPARATOR . $filename)) {
            $this->flash->addMessages('errors', "Gagal mengupload file, silahkan upload beberapa saat lagi");
            return $response->withRedirect('/carousel');
        }

        $name = $request->getParam('name', '');
        $id_order = $this->db->query("SELECT id_order FROM carousel ORDER BY id_order DESC")->fetch();
        if (!$id_order) {
            $id_order = 0;
        } else {
            $id_order = $id_order['id_order'];
        }
        $id_order++;

        $res = $this->db->query("INSERT INTO carousel (name, filename, id_order) VALUES ('{$name}', '{$filename}', {$id_order})");
        if ($res->rowCount() == 0) {
            $this->flash->addMessages('errors', "SQL ERROR");
        }

        return $response->withRedirect('/carousel');
    });

    $this->post('/reorder', function (Request $request, Response $response) {
        $ids = $request->getParam('ids', []);
        if (empty($ids)) {
            return $response->withJson([
                'status' => 'error',
                'message' => "Empty IDs",
                'data' => $ids
            ]);
        }

        foreach ($ids as $i => $id) {
            $id_order = $i + 1;
            $this->db->query("UPDATE carousel SET id_order={$id_order} WHERE id={$id}");
        }

        return $response->withJson([
            'status' => 'success',
            'message' => "Urutan berhasil disimpan"
        ]);
    });

    $this->get('/delete/{id:[0-9]+}', function (Request $request, Response $response, $args) {
        $id = intval($args['id']);
        $stmt = $this->db->prepare("SELECT * FROM carousel WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $carousel = $stmt->fetch();
        if (!$carousel) {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }

        $res = $this->db->query("DELETE FROM carousel WHERE id={$id}");
        if ($res && $res->rowCount() > 0) {
            $directory = $this->get('upload_directory') . "/carousel";
            unlink($directory . DIRECTORY_SEPARATOR . $carousel['filename']);
            
            $this->flash->addMessage('messages', "Gambar '{$carousel['name']}' telah dihapus");

            // update id_order
            $carousels = $this->db->query("SELECT * FROM carousel ORDER BY id_order")->fetchAll();
            foreach ($carousels as $i => $carousel) {
                $id_order = $i + 1;
                $this->db->query("UPDATE carousel SET id_order={$id_order} WHERE id={$carousel['id']}");
            }
        } else {
            $this->flash->addMessage('errors', "Gagal menghapus gambar '{$carousel['name']}'");
        }

        return $response->withRedirect('/carousel');
    });
})->add(function (Request $request, Response $response, $next) {
    $user = $request->getAttribute('user', null);
    if (!$user || $user['role'] != 1) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    return $next($request, $response);
})->add($loggedinMiddleware);
