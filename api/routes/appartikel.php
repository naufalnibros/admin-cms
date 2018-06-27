<?php
define('UPLOAD_DIR', '../img/artikel/');
/**
 * Validasi
 * @param  array $data
 * @param  array $custom
 * @return array
 */
function validasi($data, $custom = array())
{
    $validasi = array(
        'judul'       => 'required',
        'content'     => 'required',
        'alias'       => 'required',
        // 'kategori'    => 'required',
        'description' => 'required',
        // 'keyword'     => 'required',
        'jam'         => 'required',
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

/**
 * get list artikel
 */
$app->get('/appartikel/index', function ($request, $response) {
    $params = $_REQUEST;

    // $sort   = "artikel.id DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit  = isset($params['limit']) ? $params['limit'] : 10;
    $db     = $this->db;
    $db->select("DISTINCT('artikel.judul') judul, artikel.*")
        ->from('artikel')
        ->join('left join', 'm_pengguna as p', 'artikel.created_by = p.id')
        ->join('left join', 'kategori_artikel as kakel', 'artikel.id = kakel.artikel_id')
        ->join('left join', 'kategori as k', 'kakel.kategori_id = k.id');

    /** set parameter */
    if (isset($params['filter'])) {
        // var_dump($params['filter']);exit()
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where('artikel.is_deleted', '=', $val);
            } elseif ($key == 'publish') {
                // var_dump($key);exit();
                $db->where('artikel.publish', '=', $val);
            } elseif ($key == 'rekom') {
                $db->where('artikel.rekomendasi', '=', $val);
            } elseif ($key == 'created_by') {
                $db->where('p.nama', 'LIKE', $val);
            } elseif ($key == 'kategori') {
                $db->where('k.nama', 'LIKE', $val);
            } elseif ($key == 'jam') {

                $db->where("FROM_UNIXTIME(artikel.jam,'%Y-%m-%d')", '=', $val);

            }
             else {
                $db->where($key, 'LIKE', $val);
            }
        }
    }
    $db->orderBy('artikel.created_at DESC');

    if ($_SESSION['user']['m_pengguna_akses_id'] != 1) {
        $db->andwhere('artikel.created_by', '=', $_SESSION['user']['id']);
    }

    $totalItem = count($db->findAll());

    /** Set limit */
    if (!empty($limit)) {
        $db->limit($limit);
    }

    /** Set offset */
    if (!empty($offset)) {
        $db->offset($offset);
    }

    /** Set sorting */

    // $models = $db->log();
    $models    = $db->findAll();

    $return = [];
    if (!empty($models)) {
        foreach ($models as $key => $val) {
            $return[$key] = (array) $val;
            $pengguna     = $db->select("nama")
                ->from('m_pengguna')
                ->where('id', '=', $val->created_by)
                ->find();

            $tgl                     = date('Y-m-d H:i:s', $val->jam);
            $now                     = date('Y-m-d H:i:s');
            $return[$key]['content'] = contentParsing($val->content);
            $return[$key]['jam']     = $val->jam;
            $return[$key]['img']     = $val->gambar_thumb ? $val->gambar_thumb : SITE_URL() . '../img/artikel/default-thumb.png';
            $return[$key]['creator'] = isset($pengguna->nama) ? $pengguna->nama : '';
            // $return[$key]['id'] = $val->id;
            $return[$key]['pub']     = $val->publish == '1' && $tgl >= $now || $val->publish == '0' ? '0' : '1';
            $return[$key]['preview'] = 'preview/-/' . $val->alias . '.html';
            $return[$key]['date'] = date('Y/m/', $val->jam) . $val->alias . '.html';

            $kategori = $db->select("kategori.*")
                ->from("kategori_artikel")
                ->join('left join', 'kategori', 'kategori.id = kategori_artikel.kategori_id')
                ->where("artikel_id", "=", $val->id)
                ->findAll();

            // $array = []; $no = 0;
            // foreach($kategori as $key => $vl){
            //    $array[$no] = $vl->nama;
            //    $no++;
            // }

            // $ini = 'kosong';
            // if($kategori){
            //     $ini = implode(',', $array);
            // }
            $return[$key]['kategori'] = $kategori ? $kategori : 'kosong';
            // $return[$key]['kategori_list'] = $ini;
        }
    } else {
        $return = '';
    }


    // print_r($return);
    // exit();
    // array_multisort($return, SORT_DESC);

    return successResponse($response, ['list' => $return, 'totalItems' => $totalItem]);
});

/**
 * save artikel
 */
$app->post('/appartikel/save', function ($request, $response) {
    $data = $request->getParams();

    $db              = $this->db;
    $data['judul']   = isset($data['judul']) ? $data['judul'] : '';
    $data['content'] = isset($data['content']) ? $data['content'] : '';
    $validasi        = validasi($data);
    //
    if ($validasi === true) {
        $img = get_images($data['content']);

        $url = [];
        if ($img) {
            for ($i = 0; $i < count($img); $i++) {
                $path = '../img/artikel/' . date("m-Y") . '/';
                if (!file_exists($path)) {
                    mkdir($path, 0777);
                }
                if (strpos($img[$i], ';base64') !== false) {

                    $uploadFileName = base64toImg($img[$i], $path, rand() . '-' . $data['alias']);

                    $crtImg = createImags($path, $uploadFileName['data'], '', true, $i);

                    // $file = $path . $crtImg['big'];
                    $url = getenv("SITE_URL") . $path . $crtImg['big'];

                    if ($i == 0) {
                        $data['gambar_thumb']   = getenv("SITE_URL") . $path . $crtImg['small'];
                        $data['gambar_primary'] = getenv("SITE_URL") . $path . $crtImg['big'];
                    }
                    $data['content'] = str_replace($img[$i], $url, $data['content']);
                } else {
                    if (isset($data['id'])) {
                        $query = $this->db->select('content')
                            ->from('artikel')
                            ->where('id', '=', $data['id'])
                            ->find();

                        if (strpos($query->content, $img[$i]) !== false) {
                            $image = $img[$i];
                        } else {
                            if ($i == 0) {
                                $image = get_img($img[$i], $path, rand() . '-' . $data['alias']);
                                // $crtImg          = createImageThumb($path , $image, '', false);
                                $crtImg                 = createImageCopy($path, $image, '', true);
                                $data['gambar_thumb']   = getenv("SITE_URL") . $path . $crtImg['small'];
                                $data['gambar_primary'] = getenv("SITE_URL") . $path . $crtImg['big'];
                                $url                    = getenv("SITE_URL") . $path . $crtImg['big'];
                                $data['content']        = str_replace($img[$i], $url, $data['content']);
                            }
                        }
                    } else {
                        if ($i == 0) {
                            $image = get_img($img[$i], $path, rand() . '-' . $data['alias']);
                            // var_dump($image);exit();
                            // $crtImg          = createImageThumb($path , $image, '', false);
                            $crtImg                 = createImageCopy($path, $image, '', true);
                            $data['gambar_thumb']   = getenv("SITE_URL") . $path . $crtImg['small'];
                            $data['gambar_primary'] = getenv("SITE_URL") . $path . $crtImg['big'];
                            $url                    = getenv("SITE_URL") . $path . $crtImg['big'];
                            $data['content']        = str_replace($img[$i], $url, $data['content']);

                        }
                    }
                }
            } // end
        } else {
            $url_primary            = getenv("SITE_URL") . '../img/default.png';
            $url_thumb              = getenv("SITE_URL") . '../img/default-thumb.png';
            $data['gambar_thumb']   = $url_thumb;
            $data['gambar_primary'] = $url_primary;

            // $data['content'] = str_replace($img[$i], $url_primary, $data['content']);
        }
        // print_r($data['content']);exit();
        //cek apakah alias unique
        $db->select("alias")
            ->from("artikel")
            ->where('alias', '=', $data['alias'])
            ->andwhere('is_deleted', '=', 0);
        if (isset($data['id'])) {
            $db->andwhere('id', '<>', $data['id']);
        }
        $periksa_alias = $db->find();

        if (!$periksa_alias) {
            if (isset($data['id'])) {
                $harijam     = date('Y-m-d', strtotime($data['tanggal'])) . ' ' . date('H:i:s', strtotime($data['jam']));
                $harijam     = strtotime($harijam);
                $data['jam'] = $harijam;
                // var_dump($data);exit();
                $model = $db->update("artikel", $data, ['id' => $data['id']]);
                if (!empty($data['kategori'])) {
                    $db->delete('kategori_artikel', array('artikel_id' => $data['id']));
                    foreach ($data['kategori'] as $key => $val) {
                        $dt                = [];
                        $dt['artikel_id']  = $model->id;
                        $dt['kategori_id'] = $val['id'];
                        $sv                = $db->insert('kategori_artikel', $dt);
                    }
                }
            } else {
                $data['creator'] = $_SESSION['user']['id'];
                $harijam         = date('Y-m-d', strtotime($data['tanggal'])) . ' ' . date('H:i:s', strtotime($data['jam']));
                $harijam         = strtotime($harijam);
                $data['jam']     = $harijam;
                // var_dump($data['jam']);exit();
                $model = $db->insert("artikel", $data);
                if (!empty($data['kategori'])) {
                    foreach ($data['kategori'] as $val) {
                        $dt['artikel_id']  = $model->id;
                        $dt['kategori_id'] = $val['id'];
                        $db->insert('kategori_artikel', $dt);
                    }
                }
            }
            return successResponse($response, $model);
        } else {
            //respon jika alias sama
            return unprocessResponse($response, ['Alias harus unik, buatlah alias yang baru!']);
        }
    }
    return unprocessResponse($response, $validasi);
});

$app->post('/appartikel/update', function ($request, $response) {
    $data = $request->getParams();
    $db   = $this->db;
    try {
        $harijam     = date('Y-m-d', strtotime($data['tanggal'])) . ' ' . date('H:i:s', strtotime($data['jam']));
        $harijam     = strtotime($harijam);
        $data['jam'] = $harijam;
        $model       = $db->update("artikel", $data, ['id' => $data['id']]);
        if (!empty($data['kategori'])) {
            $db->delete('kategori_artikel', array('artikel_id' => $data['id']));
            foreach ($data['kategori'] as $key => $val) {
                $dt                = [];
                $dt['artikel_id']  = $model->id;
                $dt['kategori_id'] = $val['id'];
                $sv                = $db->insert('kategori_artikel', $dt);
            }
        }

        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal disimpan']);
    }
});

$app->post('/appartikel/trash', function ($request, $response) {
    $data = $request->getParams();
    $db   = $this->db;
    try {
        // var_dump($data);exit();
        $model = $db->update("artikel", $data, ['id' => $data['id']]);
        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal disimpan']);
    }
//    }
});
$app->post('/appartikel/rekom', function ($request, $response) {
    $data = $request->getParams();

    $db = $this->db;
    try {
        $model = $db->update("artikel", $data, ['id' => $data['id']]);
        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal disimpan']);
    }
//    }
});
/**
 * delete artikel
 */
$app->delete('/appartikel/delete/{id}', function ($request, $response) {
    $db = $this->db;

    try {
        $delete = $db->delete('artikel', array('id' => $request->getAttribute('id')));
        $delete = $db->delete('kategori_artikel', array('artikel_id' => $request->getAttribute('id')));
        return successResponse($response, ['data berhasil dihapus']);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});

/** UPLOAD GAMBAR CKEDITOR */
$app->post('/appartikel/upload', function ($request, $response) {
    $files   = $request->getUploadedFiles();
    $newfile = $files['upload'];

    if (file_exists("file/" . $newfile->getClientFilename())) {
        echo $newfile->getClientFilename() . " already exists please choose another image.";
    } else {

        $path = '../img/artikel/' . date("m-Y") . '/';
        if (!file_exists($path)) {
            mkdir($path, 0777);
        }

//        $uploadFileName = urlParsing($newfile->getClientFilename());
        $uploadFileName = $newfile->getClientFilename();
        $upload         = $newfile->moveTo($path . $uploadFileName);

        $crtImg = createImg($path . '/', $uploadFileName, date("dYh"), true); //koyok e iki function resize and create

        $url = getenv("SITE_URL") . $path . $crtImg['big'];

        // Required: anonymous function reference number as explained above.
        $funcNum = $_GET['CKEditorFuncNum'];
        // Optional: instance name (might be used to load a specific configuration file or anything else).
        $CKEditor = $_GET['CKEditor'];
        // Optional: might be used to provide localized messages.
        $langCode = $_GET['langCode'];

        echo "<script type='text/javascript'> window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', '');</script>";
    }
});

$app->get('/appartikel/kategori', function ($request, $response) {
    $params = $request->getParams();
    $db     = $this->db;

    /** Select roles from database */
    $modelscekuserkategori = $db->select("kategori_id")
        ->from("m_roles_kategori")
        ->where("user_id", "=", $_SESSION['user']['id'])
        ->findAll();

    $kat = [];
    foreach ($modelscekuserkategori as $key => $val) {
        $kat[] = $val->kategori_id;
    }
    $kat = implode(',', $kat);

    if ($modelscekuserkategori) {

        $models = $db->select("*")
            ->from("kategori")
            ->customWhere("id IN(" . $kat . ")", "AND")
            ->findAll();

    } else {
        $db->select("*")
            ->from("kategori")
            ->orderBy('kode ASC');
        $models = $db->findAll();
    }

    return successResponse($response, ['list' => $models]);
});
$app->get('/appartikel/domain', function ($request, $response) {
    $url = implode('.', array_slice(explode('.', $_SERVER['HTTP_HOST']), -2));
    return successResponse($response, ['url' => $url]);
});
/** END UPLOAD */
