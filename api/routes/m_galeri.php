<?php
/**
 * Validasi
 * @param  array $data
 * @param  array $custom
 * @return array
 */

/*validasi*/
/*Step 5.I*/

function validasi($data, $custom = array())
{
    $validasi = array(
        'nama' => 'required',
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}
/*Step 5.I*/

/*Index*/
/*Step 5.A*/

$app->get('/m_galeri/index', function ($request, $response) {
    $params = $request->getParams();

    $sort   = "id DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit  = isset($params['limit']) ? $params['limit'] : 10;

    $db = $this->db;

    $db->select("*")
        ->from('m_galeri');

    /** set parameter */
    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {

            $db->where($key, 'LIKE', $val);

        }
    }

    /** Set limit */
    if (isset($params['limit']) && !empty($params['limit'])) {
        $db->limit($limit);
    }

    /** Set offset */
    if (isset($params['offset']) && !empty($params['offset'])) {
        $db->offset($offset);
    }

    /** Set sorting */
    if (isset($params['sort']) && !empty($params['sort'])) {
        $db->sort($sort);
    }

    $models    = $db->findAll();

    // echo getenv('ARTIKEL'); exit;
    // print_r($models);exit;

    foreach ($models as $key => $value) {
        $value->path_gambar = (string)getenv('ARTIKEL') . $value->path_gambar;
        // echo $value->path_gambar;
        // print_r($value);exit;
    }

    // print_r($models);exit;
 
    $totalItem = $db->count();

    return successResponse($response, ['list' => $models, 'totalItems' => $totalItem]);
});
/*Step 5.A*/

/*Create*/
/*step 5.H*/
$app->post('/m_galeri/create', function ($request, $response) {
    $data = $request->getParams();
    $db   = $this->db;

    // print_r($data);

    // exit();

    // $validasi = validasi($data['form']);
    // if ($validasi === true) {

    //     try {

    //         if (isset($data['form']['foto']['base64'])) {
    //             $file                 = base64ToFile($data['form']['foto'], "file", 'foto_siswa_' . $data['form']['nama']);
    //             $data['form']['foto'] = '/api/file/' . $file['fileName'];
    //         }
    //         $model = $db->insert("m_galeri", $data['form']);

    //         foreach ($data['detail'] as $value) {

    //             $value['siswa_id'] = $model->id;

    //             $modeldetail = $db->insert("m_galeri_detail", $value);

    //         }
    //         return successResponse($response, $model);
    //     } catch (Exception $e) {
    //         return unprocessResponse($response, ['data gagal disimpan']);
    //     }
    // }
    // return unprocessResponse($response, $validasi);

// hak akses dan menu
    // $validasi = validasi($data);
    // if ($validasi === true) {

    //     try {
    //         $model = $db->insert("m_galeri", $data);
    //         return successResponse($response, $model);
    //     } catch (Exception $e) {
    //         return unprocessResponse($response, ['data gagal disimpan']);
    //     }
    // }
    // return unprocessResponse($response, $validasi);

    // create detail
    $validasi = validasi($data['form']);
    if ($validasi === true) {

        try {
            if (isset($data['form']['path_gambar']['base64'])) {
                $file                 = base64ToFile($data['form']['path_gambar'], "file", 'gambar_' .$data['form']['nama']);
                $data['form']['path_gambar'] = '/api/file/' . $file['fileName'];
            }
            // print_r($data);exit;
            $model = $db->insert("m_galeri", $data['form']);

            // foreach ($data['detail'] as $value) {

            //     $value['siswa_id'] = $model->id;

            //     $modeldetail = $db->insert("m_galeri_detail", $value);

            // }
            return successResponse($response, $model);
        } catch (Exception $e) {
            return unprocessResponse($response, ['data gagal disimpan']);
        }
    }
    return unprocessResponse($response, $validasi);
});
/*step 5.H*/

/*Update*/
/*Step 6.B*/

$app->post('/m_galeri/update', function ($request, $response) {
    $data = $request->getParams();
    $db   = $this->db;

    // $validasi = validasi($data['form']);
    // if ($validasi === true) {
    //     try {

    //         if (isset($data['form']['foto']['base64'])) {
    //             $file                 = base64ToFile($data['form']['foto'], "file", 'foto_siswa_' . $data['form']['nama']);
    //             $data['form']['foto'] = '/api/file/' . $file['fileName'];
    //         }

    //         $model = $db->update("m_galeri", $data['form'], array('id' => $data['form']['id']));
    //         foreach ($data['detail'] as $vals) {
    //             $vals['siswa_id'] = $model->id;

    //             /* UPDATE ATAU INSERT DATA */
    //             if (isset($vals['id'])) {
    //                 $modelss = $db->update("m_galeri_detail", $vals, array("id" => $vals['id']));
    //             } else {
    //                 $modelss = $db->insert('m_galeri_detail', $vals);
    //             }
    //         }
    //         return successResponse($response, $model);
    //     } catch (Exception $e) {
    //         return unprocessResponse($response, ['data gagal disimpan']);
    //     }
    // }
    // return unprocessResponse($response, $validasi);

    // $validasi = validasi($data);
    // if ($validasi === true) {
    //     try {
    //         $model = $db->update("m_galeri", $data, array('id' => $data['id']));
    //         return successResponse($response, $model);
    //     } catch (Exception $e) {
    //         return unprocessResponse($response, ['data gagal disimpan']);
    //     }
    // }
    // return unprocessResponse($response, $validasi);

    $validasi = validasi($data['form']);
    if ($validasi === true) {
        try {
            if (isset($data['form']['path_gambar']['base64'])) {
                $file                 = base64ToFile($data['form']['path_gambar'], "file", 'gambar_' .$data['form']['nama']);
                $data['form']['path_gambar'] = '/api/file/' . $file['fileName'];
            }
            // $data['form']['tgl_lahir'] = new Datetime($data['form']['tgl_lahir']);
            // $data['form']['tgl_lahir'] -> setTimezone(new DateTimeZone('Asia/Jakarta'));
            // $data['form']['tgl_lahir'] = $data['form']['tgl_lahir']->format("Y-m-d");
            // print_r($data);exit;
            $model = $db->update("m_galeri", $data['form'], array('id' => $data['form']['id']));
            // foreach ($data['detail'] as $vals) {
            //     $vals['siswa_id'] = $model->id;

            //     /* UPDATE ATAU INSERT DATA */
            //     if (isset($vals['id'])) {
            //         $modelss = $db->update("m_galeri_detail", $vals, array("id" => $vals['id']));
            //     } else {
            //         $modelss = $db->insert('m_galeri_detail', $vals);
            //     }
            // }
            return successResponse($response, $model);
        } catch (Exception $e) {
            return unprocessResponse($response, ['data gagal disimpan']);
        }
    }
    return unprocessResponse($response, $validasi);
});
/*Step 6.B*/

/*Hapus*/
/*Step 7.B*/

$app->delete('/m_galeri/delete/{id}', function ($request, $response) {
    $db = $this->db;
    try {
        $delete = $db->delete('m_galeri', array('id' => $request->getAttribute('id')));
        return successResponse($response, ['data berhasil dihapus']);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});
/*Step 7.B*/

/*Step 8.7*/

$app->get('/m_galeri/view/{id}', function ($request, $response) {
    $db = $this->db;
    $id = $request->getAttribute('id');
    try {

        $model = $db->select("*")
            ->from('m_galeri')
            ->where('id', '=', $id)
            ->find();

        // $modeldetail = $db->select("*")
        //     ->from('m_galeri_detail')
        //     ->where('siswa_id', '=', $id)
        //     ->findAll();

        return successResponse($response, ['form' => $model]);
    } catch (Exception $e) {
        return unprocessResponse($response, ['Terjadi Kesalahan']);
    }
});
/*Step 8.7*/

/*Hapus Detail*/
/*Step 8.9*/

$app->delete('/m_galeri/deleteDetail/{id}', function ($request, $response) {
    $db = $this->db;
    try {
        $delete = $db->delete('m_galeri_detail', array('id' => $request->getAttribute('id')));
        return successResponse($response, ['data berhasil dihapus']);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});
/*Step 8.9.B*/

/*step 10.B*/

$app->get('/m_galeri/viewPrint/', function ($request, $response) {
    $db = $this->db;
    $id = $request->getAttribute('id');
    try {

        $model = $db->select("*")
            ->from('m_galeri')
            ->findAll();

        return successResponse($response, ['form' => $model]);
    } catch (Exception $e) {
        return unprocessResponse($response, ['Terjadi Kesalahan']);
    }
});
/*step 10.B*/

/* Step 11.A*/

$app->get('/m_galeri/export', function ($request, $response) {

    $sql   = $this->db;
    $siswa = $sql->select("*")
        ->from('m_galeri')
        ->findAll();

    $path        = 'file/siswa.xls';
    $objReader   = PHPExcel_IOFactory::createReader('Excel5');
    $objPHPExcel = $objReader->load($path);

    $row = 2;
    $no  = 1;
    foreach ($siswa as $key => $val) {

        $objPHPExcel->getActiveSheet()
            ->setCellValue('A' . $row, $no)
            ->setCellValue('B' . $row, $val->nama)
            ->getRowDimension($row)
            ->setRowHeight(20);

        $row++;
        $no++;
    }

    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment;Filename=Data_siswa.xls");

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
});

/* Step 11.A*/
