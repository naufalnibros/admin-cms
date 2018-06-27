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
        'tipe' => 'required',

        // 'm_roles_id' => 'required',
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

/**
 * get user detail for update profile
 */

/**
 * get user list
 */
$app->get('/m_sosmed/index', function ($request, $response) {
    $params = $_REQUEST;

    $sort   = "id DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit  = isset($params['limit']) ? $params['limit'] : 10;

    $db = $this->db;

    $db->select("*")
        ->from('m_sosialmedia');

    /** set parameter */
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

    $models    = $db->findAll();
    $totalItem = $db->count();

    /** set m_roles_id to string */

    return successResponse($response, ['list' => $models, 'totalItems' => $totalItem]);
});

/**
 * create user
 */
$app->post('/m_sosmed/create', function ($request, $response) {
    $data     = $request->getParams();
    $validasi = validasi($data);

    $db = $this->db;
    if ($validasi === true) {
        try {

            $cek = $db->select("*")
                ->from("m_sosialmedia")
                ->where("tipe", "=", $data['tipe'])
                ->where("is_deleted", "=", 0)
                ->find();

            if (!empty($cek)) {
                return unprocessResponse($response, ['Data Sudah Ada']);
            } else {
                $model = $db->insert("m_sosialmedia", $data);
            }

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

/**
 * update user
 */
$app->post('/m_sosmed/update', function ($request, $response) {
    $data = $request->getParams();

    $db = $this->db;

    $validasi = validasi($data);

    if ($validasi === true) {
        try {
            $model = $db->update("m_sosialmedia", $data, array('id' => $data['id']));
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
$app->delete('/m_sosmed/delete/{id}', function ($request, $response) {
    $db = $this->db;

    try {
        $delete = $db->delete('m_sosialmedia', array('id' => $request->getAttribute('id')));
        return successResponse($response, ['data berhasil dihapus']);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});

$app->get('/m_sosmed/view/{id}', function ($request, $response) {
    $params = $request->getParams();
    $id     = $request->getAttribute('id');

    $db = $this->db;

    /** Select roles from database */
    $models = $db->select("*")
        ->from("m_sosialmedia")
        ->where("id", "=", $id)
        ->find();

    return successResponse($response, ['list' => $models]);
});
