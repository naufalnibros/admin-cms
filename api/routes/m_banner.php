<?php

/**
 * Get list user roles
 */
$app->get('/m_banner/index', function ($request, $response) {
    $params = $request->getParams();

    $sort = "m_banner.id DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db = $this->db;

    /** Select roles from database */
    $db->select("m_banner.*,m_pengguna.nama as user_nama")
            ->from("m_banner")
            ->leftJoin("m_pengguna","m_pengguna.id=m_banner.created_by")
            ->orderBy($sort);

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

    $models = $db->findAll();
    $totalItem = $db->count();
    $array = [];
    foreach ($models as $key => $val) {
        $array[$key] = (array) $val;
        $url = "../img/banner/" . $val->path;
        if (file_exists($url) && isset($val->path)) {
            $array[$key]['gambar']['path'] = getenv('SITE_URL') . $url;
        } else {
            $array[$key]['gambar'] = '';
        }
    }

    return successResponse($response, ['list' => $array, 'totalItems' => $totalItem]);
});

$app->post('/m_banner/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

//    echo json_encode($params);
//    exit();

    if (isset($data['gambar']['base64'])) {
        $file = base64ToFile($data['gambar'], "../img/banner");
        $data['path'] = imgUrl("banner").'/'.$file['fileName'];
        $data['tanggal_upload'] = strtotime(date("Y-m-d H:i:s"));
    }

    if (isset($data['id'])) {
        $model = $db->update('m_banner', $data, ['id' => $data['id']]);
    } else {
        $model = $db->insert('m_banner', $data);
    }

    return successResponse($response, $model);
});

/**
 * delete artikel
 */
$app->delete('/m_banner/delete/{id}', function ($request, $response) {
    $db = $this->db;

    try {
        $delete = $db->delete('m_banner', array('id' => $request->getAttribute('id')));
        return successResponse($response, ['data berhasil dihapus']);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});
