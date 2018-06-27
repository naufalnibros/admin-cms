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
// $app->get('/appuser/getKategori', function ($request, $response) {
//     $db = $this->db;

//     $data = $db->select('*')
//                 ->from('kategori')
//                 ->findAll();

//     return successResponse($response, ['list' => $data]);
// });
// $app->post('/appuser/tambahberkas', function ($request, $response) {
//     $params = $request->getParams();

//     $db   = $this->db;
//     $data = $db->select("*")
//         ->from("m_roles_kategori")
//         ->where("m_berkas_diklat.is_deleted", "=", '0')
//         ->AndWhere("m_berkas_diklat.id", "=", $params['id'])
//         ->findAll();

//     return successResponse($response, ['data' => $data, 'status' => 1]);
// });
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

    foreach ($models as $key => $value) {
         $value->m_pengguna_akses_id = (string) $value->m_pengguna_akses_id;
            $models[$key] = (array) $value;
            $kategori = $db->select("kategori.*")
                ->from("kategori")
                ->join('left join', 'm_roles_kategori', 'kategori.id = m_roles_kategori.kategori_id')
                ->where("user_id", "=", $value->id)
                ->findAll();
                $models[$key]['kategori'] = $kategori;         
    }   
    /** set m_roles_id to string */
    // foreach($models as $key => $val){
    //     $val->m_pengguna_akses_id = (string) $val->m_pengguna_akses_id;
    // }

    return successResponse($response, ['list' => $models, 'totalItems' => $totalItem]);
});

/**
 * create user
 */
$app->post('/appuser/create', function ($request, $response) {
    $data = $request->getParams();
    // var_dump($data['kategori']);
    // exit();
    $db = $this->db;

    $validasi = validasi($data, ['password' => 'required']);

    if ($validasi === true) {
        $data['password'] = sha1($data['password']);
        $data['is_deleted'] = 0;
        try {
            $model = $db->insert("m_pengguna", $data);
            if (!empty($dat['kategori'])) {
                foreach ($data['kategori'] as $key => $value) {
                        $dt                = [];
                        $dt['user_id']  = $model->id;
                        $dt['kategori_id'] = $value['id'];
                        
                $modelkategori = $db->insert("m_roles_kategori", $dt);
            }
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

    if (!empty($data['password'])) {
        $data['password'] = sha1($data['password']);
    } else {
        unset($data['password']);
    }

    $validasi = validasi($data);

    if ($validasi === true) {
        try {
            $model = $db->update("m_pengguna", $data, array('id' => $data['id']));

                if (!empty($data['kategori'])) {
                    $db->delete('m_roles_kategori', array('user_id' => $data['id']));
                    foreach ($data['kategori'] as $key => $val) {
                        $dt                = [];
                        $dt['user_id']  = $model->id;
                        $dt['kategori_id'] = $val['id'];
                        $sv                = $db->insert('m_roles_kategori', $dt);
                    }
                }



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
