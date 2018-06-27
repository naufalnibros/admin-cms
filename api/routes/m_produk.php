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
$app->get('/appuser/kategori', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    /** Select roles from database */
    $db->select("*")
        ->from("kategori")
        ->orderBy('kode ASC');
      $models = $db->findAll();


    return successResponse($response, ['list' => $models]);
});
/**
 * get user list
 */
$app->get('/m_produk/index', function ($request, $response) {
    $params = $_REQUEST;

    $sort   = "id DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit  = isset($params['limit']) ? $params['limit'] : 10;

    $db = $this->db;

    $db->select("*")
        ->from('m_produk');

    /** set parameter */
    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'nama') {
                $db->where('m_produk.nama', 'LIKE', $val);
            } else if ($key == 'is_deleted') {
                $db->where('m_produk.is_deleted', '=', $val);
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

    $model = array();
    foreach ($models as $key => $value) {
      $pembuat = $db->find("SELECT * FROM m_pengguna WHERE id = {$value->created_by} ");
      $model[$key]['id']     = $value->id;
      $model[$key]['nama']   = $value->nama;
      $model[$key]['gambar'] = $value->gambar;
      $model[$key]['is_deleted'] = $value->is_deleted;
      $model[$key]['pembuat']= $pembuat->nama;
      $model[$key]['kode']= $value->kode;
    }

    $totalItem = $db->count();
    return successResponse($response, ['list' => $model, 'totalItems' => $totalItem]);
});

$app->post('/m_produk/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    // return successResponse($response, $data);

    if (isset($data['gambar']['base64'])) {
        $file = base64ToFile($data['gambar'], "../img/produk");
        $data['gambar'] = imgUrl("produk").'/'.$file['fileName'];
    }

    if (isset($data['id'])) {
        $model = $db->update('m_produk', $data, ['id' => $data['id']]);
    } else {
        $model = $db->insert('m_produk', $data);
    }

    return successResponse($response, $model);
});

/**
 * delete user
 */
$app->delete('/m_produk/delete/{id}', function ($request, $response) {
    $db = $this->db;

    try {
        $delete = $db->delete('m_produk', array('id' => $request->getAttribute('id')));
        return successResponse($response, ['data berhasil dihapus']);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});
