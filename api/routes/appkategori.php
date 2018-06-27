<?php

/**
 * Validasi
 * @param  array $data
 * @param  array $custom
 * @return array
 */
function validasi($data, $custom = array()) {
    $validasi = array(
        'nama' => 'required',
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

/**
 * Get list user roles
 */
$app->get('/appkategori/index', function ($request, $response) {
    $params = $request->getParams();

    $sort = "id ASC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db = $this->db;

    /** Select roles from database */
    $db->select("*")
    ->from("kategori")
    ->orderBy('id ASC');

    /** Add filter */
    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            $db->where($key, 'LIKE', $val);
        }
    }

    /** Set limit */
    if (!empty($limit)) {
        $db->limit($limit);
    }

    /** Set offset */
    if (!empty($offset)) {
        $db->offset($offset);
    }

    /** Set sorting */
    if (!empty($params['sort'])) {
        $db->sort($sort);
    }

    $modelss = $db->findAll();


    $models = array();
    foreach ($modelss as $key => $value) {

        if ($value->path != null) {
             $models[$key]['path'] = $value->path;
        } else{
             $models[$key]['path'] ="";
        }

        $models[$key]['id'] = $value->id;
        $models[$key]['nama'] = $value->nama;
        $models[$key]['is_deleted'] = $value->is_deleted;
        $models[$key]['title'] = $value->title;
        $models[$key]['deskripsi'] = $value->deskripsi;
        $models[$key]['keywords'] = $value->keywords;
        $models[$key]['alias'] = $value->alias;

        // $models[$key][''] = $value->nama;

    }

    $totalItem = $db->count();


    return successResponse($response, ['list' => $models, 'totalItems' => $totalItem]);
});

/**
 * Save roles
 */
$app->post('/appkategori/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $validasi = validasi($data);

    if ($validasi === true) {
        if (isset($data['gambar']['base64'])) {
        $path = "../img/kategori/";
        $file = base64ToFile($data['gambar'], $path);
        $data['path'] = site_url(). $path. $file['fileName'];
        // $data['tanggal_upload'] = strtotime(date("Y-m-d H:i:s"));
        } else{
           unset($data['path']);
        }
        try {
            if (isset($data['id'])) {
                $model = $db->update('kategori', $data, ['id' => $data['id']]);
            } else {
                $data['is_deleted'] = 0;
                $model = $db->insert('kategori', $data);
            }
            return successResponse($response, $model);
        } catch (Exception $e) {
            return unprocessResponse($response, ['data gagal disimpan']);
        }
    }
    return unprocessResponse($response, $validasi);
});

/**
 * Delete roles
 */
$app->delete('/appkategori/delete/{id}', function ($request, $response) {
    $db = $this->db;
    $cek = $db->select('*')
    ->from('kategori')
    ->where('id','=',$request->getAttribute('id'))
    ->customWhere('id IN (select kategori_id from kategori_artikel)','AND')
    ->find();
    if (empty($cek)) {

        $delete = $db->delete('kategori', array('id' => $request->getAttribute('id')));

        if ($delete) {
            return successResponse($response, ['data berhasil dihapus']);
        }else{
            return unprocessResponse($response, ['data gagal dihapus']);
        }

    }
     return unprocessResponse($response, ['Kategori Terpakai, Tidak bisa di hapus']);
});
