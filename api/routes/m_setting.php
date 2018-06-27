<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function validasi($data, $custom = array()) {
    $validasi = array(
        'nama_website' => 'required',
        'email' => 'required',
        'no_telpon' => 'required',
        'alamat' => 'required',
        'fax' => 'required',
        'title' => 'required',
        'company_description' => 'required',
        'description' => 'required',
        'keywords' => 'required',
        'author' => 'required'
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->get('/m_setting/view', function ($request, $response) {
    $db = new Cahkampung\Landadb(Db());

    $data = $db->select("*")->from("m_setting")
            ->where("id", "=", 1)
            ->find();
    $data->password = '';
    // print_r($data);exit();
        $url = "../img/main/" . $data->path;
        if (file_exists($url) && isset($data->path)) {
            $data->gambar = getenv('SITE_URL') . $url;
        } else {
            $data->gambar = '';
        }

    return successResponse($response, $data);
});

$app->post('/m_setting/update', function ($request, $response) {
    $data = $request->getParams();

    $db = $this->db;
    $validasi = validasi($data);
    if ($validasi === true) {
        if (isset($data['gambar']['base64'])) {
        $file = base64ToFile($data['gambar'], "../img/main");
        $data['path'] = $file['fileName'];
        // $data['tanggal_upload'] = strtotime(date("Y-m-d H:i:s"));
        } else{
           unset($data['path']);
        }

        try {
            $model = $db->update("m_setting", $data, array('id' => $data['id']));
            return successResponse($response, $model);
        } catch (Exception $e) {
            return unprocessResponse($response, ['data gagal disimpan']);
        }

    }
    return unprocessResponse($response, $validasi);
});
