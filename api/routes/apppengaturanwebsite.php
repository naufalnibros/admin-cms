<?php
/**
 * Validasi
 * @param  array $data
 * @param  array $custom
 * @return array
 */
function validasi($data, $custom = array())
{
    $validasi = array(
        'nama'       => 'required',
        'username'   => 'required',
        // 'm_roles_id' => 'required',
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

/**
 * get user detail for update profile
 */
$app->get('/appuser/view', function ($request, $response) {
    $db = $this->db;

    $data = $db->find('select id, nama, username, m_pengguna_akses_id from m_pengguna where id = "' . $_SESSION['user']['id'] . '"');

    return successResponse($response, $data);
});

/**
 * get user list
 */
$app->get('/appuser/index', function ($request, $response) {
    $params = $_REQUEST;

    $sort   = "id DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit  = isset($params['limit']) ? $params['limit'] : 10;

    $db = $this->db;

    $db->select("m_pengguna.*,m_pengguna_akses.nama as nama_akses")
        ->from('m_pengguna')
            ->leftJoin("m_pengguna_akses","m_pengguna_akses.id = m_pengguna.m_pengguna_akses_id");

    /** set parameter */
    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'nama') {
                $db->where('m_pengguna.nama', 'LIKE', $val);
            } else if ($key == 'is_deleted') {
                $db->where('m_pengguna.is_deleted', '=', $val);
            }
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

    $models    = $db->findAll();
    $totalItem = $db->count();

    /** set m_roles_id to string */
    foreach($models as $key => $val){
        $val->m_pengguna_akses_id = (string) $val->m_pengguna_akses_id;
    }

    return successResponse($response, ['list' => $models, 'totalItems' => $totalItem]);
});

/**
 * create user
 */
$app->post('/appuser/create', function ($request, $response) {
    $data = $request->getParams();

    $db = $this->db;

    $validasi = validasi($data, ['password' => 'required']);

    if ($validasi === true) {
        $data['password'] = sha1($data['password']);
        $data['is_deleted'] = 0;
        try {
            $model = $db->insert("m_pengguna", $data);
            return successResponse($response, $model);
        } catch (Exception $e) {
            return unprocessResponse($response, ['data gagal disimpan']);
        }
    }
    return unprocessResponse($response, $validasi);
});

/**
 * update user profile
 */
$app->post('/appuser/updateprofil', function ($request, $response) {
    $data = $request->getParams();
    $id   = $_SESSION['user']['id'];

    $db = $this->db;

    if (!empty($data['password'])) {
        $data['password'] = sha1($data['password']);
    } else {
        unset($data['password']);
    }

    $validasi = validasi($data);

    if ($validasi === true) {
        try {
            $model = $db->update("m_pengguna", $data, array('id' => $id));
            return successResponse($response, $model);
        } catch (Exception $e) {
            return unprocessResponse($response, ['data gagal disimpan']);
        }
    }
    return unprocessResponse($response, $validasi);
});

/**
 * update user
 */
$app->post('/appuser/update', function ($request, $response) {
    $data = $request->getParams();

    $db = $this->db;

    if (!empty($params['password'])) {
        $data['password'] = sha1($data['password']);
    } else {
        unset($data['password']);
    }

    $validasi = validasi($data);

    if ($validasi === true) {
        try {
            $model = $db->update("m_pengguna", $data, array('id' => $data['id']));
            return successResponse($response, $model);
        } catch (Exception $e) {
            return unprocessResponse($response, ['data gagal disimpan']);
        }
    }
    return unprocessResponse($response, $validasi);
});

/**
 * delete user
 */
$app->delete('/appuser/delete/{id}', function ($request, $response) {
    $db = $this->db;

    try {
        $delete = $db->delete('m_pengguna', array('id' => $request->getAttribute('id')));
        return successResponse($response, ['data berhasil dihapus']);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});
