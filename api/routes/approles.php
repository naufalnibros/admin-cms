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
$app->get('/approles/index', function ($request, $response) {
    $params = $request->getParams();

    $sort = "id DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db = $this->db;

    /** Select roles from database */
    $db->select("*")
            ->from("m_pengguna_akses");

    /** Add filter */
    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where('m_pengguna_akses.is_deleted', '=', $val);
            }

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

    $models = $db->findAll();
    $totalItem = $db->count();

    foreach ($models as $val) {
        $val->akses = json_decode($val->akses);
    }

    return successResponse($response, ['list' => $models, 'totalItems' => $totalItem]);
});

/**
 * Save roles
 */
$app->post('/approles/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $validasi = validasi($data);

    if ($validasi === true) {
        try {
            $data['akses'] = json_encode($data['akses']);
            if (isset($data['id'])) {
                $model = $db->update('m_pengguna_akses', $data, ['id' => $data['id']]); 
            } else {
                $data['is_deleted'] = 0;
                $model = $db->insert('m_pengguna_akses', $data);
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
$app->delete('/approles/delete/{id}', function ($request, $response) {
    $db = $this->db;

    try {
        $delete = $db->delete('m_pengguna_akses', array('id' => $request->getAttribute('id')));
        return successResponse($response, ['data berhasil dihapus']);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});

$app->post('/approles/update', function ($request, $response) {
    $data = $request->getParams();
    // echo json_encode($data);exit();
    $db = $this->db;

    $validasi = validasi($data);

    if ($validasi === true) {
        try {
            $data['akses'] = json_encode($data['akses']);
            $model = $db->update("m_pengguna_akses", $data, array('id' => $data['id']));



            return successResponse($response, $model);
        } catch (Exception $e) {
            return unprocessResponse($response, ['data gagal disimpan']);
        }
    }
    return unprocessResponse($response, $validasi);
});