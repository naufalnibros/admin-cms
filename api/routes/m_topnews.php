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
        'artikel'       => 'required',
        'nomer'     => 'required',
        'date_start'       => 'required',
        'date_end'    => 'required',
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

/**
 * get list artikel
 */
$app->get('/m_topnews/index', function ($request, $response) {
    $params = $_REQUEST;

    // $sort   = "artikel.id DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit  = isset($params['limit']) ? $params['limit'] : 10;
    $db     = $this->db;
    $db->select("m_topnews.* , artikel.judul as artikel")
        ->from('m_topnews')
        ->join('left join', 'artikel', 'artikel.id = m_topnews.artikel_id');
    /** set parameter */
    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {

            if ($key == 'is_deleted') {
                $db->where('m_topnews.is_deleted', '=', $val);
            } elseif ($key == 'nama') {
                $db->where('artikel.judul', 'LIKE', $val);
            } elseif ($key == 'rekom') {
                $db->where('artikel.rekomendasi', '=', $val);
            } elseif ($key == 'created_by') {
                $db->where('p.nama', 'LIKE', $val);
            } elseif ($key == 'kategori') {
                $db->where('k.nama', 'LIKE', $val);
            } else {
                $db->where($key, 'LIKE', $val);
            }
        }

    }
    $db->orderBy('id ASC');

    // if ($_SESSION['user']['m_pengguna_akses_id'] != 1) {
    //     $db->andwhere('artikel.created_by', '=', $_SESSION['user']['id']);
    // }

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
    $totalItem = $db->count();
    // var_dump($models);exit();
    $return = [];
    if (!empty($models)) {
        foreach ($models as $key => $val) {

            $return[$key] = (array) $val;
            // $pengguna     = $db->select("nama")
            //     ->from('m_pengguna')
            //     ->where('id', '=', $val->created_by)
            //     ->find();
            $return[$key]['date_start'] = date('d/M/Y H:i', $val->date_start);
            $return[$key]['date_end']   = date('d/M/Y H:i', $val->date_end);
            $now                        = date('d/M/Y H:i');

            if ($now < $return[$key]['date_start']) {
                $return[$key]['publish'] = 0;
            } elseif (($now >= $return[$key]['date_start']) && ($now <= $return[$key]['date_end'])) {
                $return[$key]['publish'] = 1;
            } elseif ($now > $return[$key]['date_end']) {
                $return[$key]['publish'] = 2;
            }
            // $return[$key]['content'] = contentParsing($val->content);
            // $return[$key]['jam']     = $tgl;
            // $return[$key]['img']     = $val->gambar_thumb ? $val->gambar_thumb : SITE_URL(). '../img/artikel/default.jpg';
            // $return[$key]['creator'] = isset($pengguna->nama) ? $pengguna->nama : '';
            // $return[$key]['id'] = $val->id;
            // $return[$key]['pub'] = $val->publish == '1' && $tgl >= $now || $val->publish == '0' ? '0' : '1';

            // $kategori = $db->select("kategori.*")
            //     ->from("kategori_artikel")
            //     ->join('left join', 'kategori', 'kategori.id = kategori_artikel.kategori_id')
            //     ->where("artikel_id", "=", $val->id)
            //     ->findAll();

            // $array = []; $no = 0;
            // foreach($kategori as $key => $vl){
            //    $array[$no] = $vl->nama;
            //    $no++;
            // }

            // $ini = 'kosong';
            // if($kategori){
            //     $ini = implode(',', $array);
            // }
            // $return[$key]['kategori'] = $kategori ? $kategori : 'kosong';
            // $return[$key]['kategori_list'] = $ini;
        }
    } else {
        $return = '';
    }
    // var_dump($return);
    // exit();

    // print_r($return);
    // exit();
    // array_multisort($return, SORT_DESC);

    return successResponse($response, ['list' => $return, 'totalItems' => $totalItem]);
});
/**
 * save artikel
 */
$app->post('/m_topnews/save', function ($request, $response) {
    $data = $request->getParams();
    $boleh = false;
    // print_r($data);exit();
    $db = $this->db;

    $validasi        = validasi($data);

    if ($validasi === true) {
        if ($data['date_end'] < $data['date_start']) {
            $err = "Tanggal Berakhir harus setelah Tanggal Mulai";
            return unprocessResponse($response,['errors' => $err] );
        }
        $harijam            = date('Y-m-d', strtotime($data['date_start'])) . ' ' . date('H:i:s', strtotime($data['date_start']));
        $data['date_start'] = strtotime($harijam);
        $harijam1           = date('Y-m-d', strtotime($data['date_end'])) . ' ' . date('H:i:s', strtotime($data['date_end']));
        $data['date_end']   = strtotime($harijam1);
        $data['artikel_id'] = $data['artikel']['id'];

        $cekdb = $db->select('*')
                ->from('m_topnews')
                ->where('nomer', '=' , $data['nomer']);
        
        if (!empty($data['id'])) {
            $cekdb = $cekdb->customWhere('id = '. $data['id'], 'AND NOT');
        }

        $cekdb = $cekdb->findAll();

        if (!empty($cekdb)) {
            foreach ($cekdb as $key => $value) {
                if ((($data['date_start'] < $value->date_start) && ($data['date_end'] < $value->date_start)) || (($data['date_start'] > $value->date_end) && ($data['date_end'] > $value->date_end))) {
                    $boleh = true;
                } else {
                    $boleh = false;
                    break;
                }
            }

            if ($boleh) {
                if (!empty($data['id'])) {
                    $model = $db->update("m_topnews", $data, ['id' => $data['id']]);
                    return successResponse($response, $model);
                }
                $model = $db->insert("m_topnews", $data);
                return successResponse($response, $model);
            } else {
                $err = "Data dengan nomer dan tanggal tersebut sudah ada";
                return unprocessResponse($response,['errors' => $err] );
            }
        } else {
            if (!empty($data['id'])) {
                    $model = $db->update("m_topnews", $data, ['id' => $data['id']]);
                    return successResponse($response, $model);
                } else {
                    $model = $db->insert("m_topnews", $data);
                return successResponse($response, $model);
                }
            
        }
    }
    return unprocessResponse($response, $validasi);
    // if (!empty($data['id'])) {

    //     $data['artikel_id'] = $data['artikel']['id'];
    //     $model = $db->update("m_topnews", $data, ['id' => $data['id']]);
    //     return successResponse($response, $model);
    // } else {
    //     $cekdb = $db->select('*')
    //         ->from('m_topnews')
    //         ->where('nomer', '=' , $data['nomer'])
    //         ->findAll();
    //     // print_r(empty($cekdb));exit();
    //     if (!empty($cekdb)) {
    //         foreach ($cekdb as $key => $value) {
    //             if ((($data['date_start'] < $value->date_start) && ($data['date_end'] < $value->date_start)) || (($data['date_start'] > $value->date_end) && ($data['date_end'] > $value->date_end))) {
    //                 $boleh = true;
    //             } else {
    //                 $boleh = false;
    //                 break;
    //             }
    //         }

    //         if ($boleh) {
    //             $data['artikel_id'] = $data['artikel']['id'];
    //             $model = $db->insert("m_topnews", $data);
    //             return successResponse($response, $model);
    //         } else {
    //             $err = "Data dengan nomer dan tanggal tersebut sudah ada";
    //             return unprocessResponse($response,['errors' => $err] );
    //         }
    //     } else {
    //         $data['artikel_id'] = $data['artikel']['id'];
    //         $model = $db->insert("m_topnews", $data);
    //             return successResponse($response, $model);
    //     }  
    // }

    // return unprocessResponse($response, $validasi);
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
    //    }
});

$app->post('/m_topnews/trash', function ($request, $response) {
    $data = $request->getParams();
    $db   = $this->db;
    try {
        $harijam            = date('Y-m-d', strtotime($data['date_start'])) . ' ' . date('H:i:s', strtotime($data['date_start']));
        $data['date_start'] = strtotime($harijam);
        $harijam1           = date('Y-m-d', strtotime($data['date_end'])) . ' ' . date('H:i:s', strtotime($data['date_end']));
        $data['date_end']   = strtotime($harijam1);
        // $harijam     = date('Y-m-d', strtotime($data['tanggal'])) . ' ' . date('H:i:s', strtotime($data['jam']));
        // $harijam     = strtotime($harijam);
        // $data['jam'] = $harijam;
        $model       = $db->update("m_topnews", $data, ['id' => $data['id']]);
        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal disimpan']);
    }
    //    }
});

$app->post('/appartikel/rekom', function ($request, $response) {
    $data = $request->getParams();
    // var_dump($data);exit();
    $db = $this->db;
    try {
        $harijam     = date('Y-m-d', strtotime($data['tanggal'])) . ' ' . date('H:i:s', strtotime($data['jam']));
        $harijam     = strtotime($harijam);
        $data['jam'] = $harijam;
        $model       = $db->update("artikel", $data, ['id' => $data['id']]);
        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal disimpan']);
    }
    //    }
});
/**
 * delete artikel
 */
$app->delete('/m_topnews/delete/{id}', function ($request, $response) {
    $db = $this->db;

    try {
        $delete = $db->delete('m_topnews', array('id' => $request->getAttribute('id')));
        // $delete = $db->delete('kategori_artikel', array('artikel_id' => $request->getAttribute('id')));
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

$app->get('/m_topnews/getArtikel/{val}', function ($request, $response) {

    $db    = $this->db;
    $model = $db->select("id, judul")
        ->from("artikel")
        ->where("judul", "like", $request->getAttribute('val'))
        ->findAll();

    //    print_r($model);
    //    exit();
    echo json_encode(array('status' => 1, 'data' => (array) $model), JSON_PRETTY_PRINT);
});

$app->get('/m_topnews/getArtikelById/{val}', function ($request, $response) {

    $db    = $this->db;
    $model = $db->select("*")
        ->from("artikel")
        ->where("id", "=", $request->getAttribute('val'))
        ->find();

    //    print_r($model);
    //    exit();
    echo json_encode(array('status' => 1, 'data' => (array) $model), JSON_PRETTY_PRINT);
});

$app->get('/m_topnews/getNumber', function ($request, $response) {

    $data = getenv('number');
    $data = explode(",",$data);
    //    print_r($model);
    //    exit();
    // echo json_encode(array('status' => 1, 'data' => $data), JSON_PRETTY_PRINT);
    return successResponse($response, $data);
});

$app->get('/m_topnews/insertAllData', function ($request, $response) {
    set_time_limit(0);
    $a = $this->db;
    $penulis = $a->select("*")->from("m_pengguna")->findAll();

    for ($i=21; $i <= 30; $i++) { 
        $string = file_get_contents("file/data". $i .".json");
        $json_a = json_decode($string, true);
        $db   = $this->db;
        $kat = $db->select("id, nama")
        ->from("kategori")
        ->findAll();
        $array = [];
        foreach ($json_a['feed']['entry'] as $key => $value) {
            $date = strtotime(date('Y-m-d H:i:s',strtotime($value['published']['$t'])));
            $alias = explode(" ",$value['title']['$t']);
            if (isset($value['link'][2]['href'])) {
                $url_full = explode('/',$value['link'][2]['href']);
                $alias = str_replace(".html", "", $url_full[5]);
            } else{
                if (count($alias) < 6) {
                    $alias = str_replace("?","-",str_replace(" ", "-",  strtolower($value['title']['$t'])));
                } else {
                    $alias = str_replace("?","-",str_replace(" ", "-",  strtolower($alias[0].$alias[1].$alias[2].$alias[3].$alias[4])));
                }
            }
            
            foreach ($penulis as $kk => $vv) {
                if ($value['author'][0]['name']['$t'] == $vv->nama) {
                    $created_by = $vv->id;
                }
            } 

            $array[$key] = [
                "judul" => $value['title']['$t'],
                "jam" => strtotime($value['published']['$t']),
                "created_at" => $date,
                "modified_at" => strtotime($value['updated']['$t']),
                "content" => $value['content']['$t'],
                "gambar_thumb" => !empty($value['media$thumbnail']['url']) ? str_replace("s72-c/","",$value['media$thumbnail']['url']) : null,
                "alias" => $alias,
                "publish" => 1,
                "created_by" => $created_by,
                "modified_by" => 0,
                "author" => $value['author'][0]['name']['$t'],
            ];

            $model = $db->insert("artikel", $array[$key]);

            if (!empty($value['category'])) {
                foreach ($kat as $keys => $values) {
                    foreach ($value['category'] as $keyx => $valuex) {
                        if($values->nama == $valuex['term']) {
                            $arrayx = [
                                "artikel_id" => $model->id,
                                "kategori_id" => $values->id
                            ];
                            $kategori = $db->insert('kategori_artikel', $arrayx);

                        }
                    }
                    
                }
            } else {
                $arrayx = [
                    "artikel_id" => $model->id,
                    "kategori_id" => null
                ];
                $kategori = $db->insert('kategori_artikel', $arrayx);
            }      
        }
    }
    return successResponse($response, "berhasil"); 
});

$app->get('/m_topnews/insertOneData', function ($request, $response) {
        set_time_limit(0);
        $string = file_get_contents("file/data1.json");
        $json_a = json_decode($string, true);

        $db   = $this->db;
        $kat = $db->select("id, nama")
        ->from("kategori")
        ->findAll();

        // echo json_encode($kat);exit;
        $array = [];
        foreach ($json_a['feed']['entry'] as $key => $value) {

            $date = strtotime(date('Y-m-d',strtotime($value['published']['$t'])));
            $time = strtotime(date('H:i:s',strtotime($value['published']['$t'])));
            $array[$key] = [
                "judul" => $value['title']['$t'],
                "tanggal" => $date,
                "jam" => $time,
                "created_at" => strtotime($value['published']['$t']),
                "modified_at" => strtotime($value['updated']['$t']),
                "content" => $value['content']['$t'],
                "gambar_thumb" => !empty($value['media$thumbnail']['url']) ? str_replace("s72-c/","",$value['media$thumbnail']['url']) : null,
                "alias" => str_replace(" ", "-",  strtolower($value['title']['$t'])),
                "publish" => 1,
                "created_by" => 1,
                "modified_by" => 0,
                "author" => $value['author'][0]['name']['$t'],
            ];

            $model = $db->insert("artikel", $array[$key]);

            if (!empty($value['category'])) {
                foreach ($kat as $keys => $values) {
                    foreach ($value['category'] as $keyx => $valuex) {
                        if($values->nama == $valuex['term']) {
                            $arrayx = [
                                "artikel_id" => $model->id,
                                "kategori_id" => $values->id
                            ];
                            $kategori = $db->insert('kategori_artikel', $arrayx);

                        }
                    }
                    
                }
            } else {
                $arrayx = [
                    "artikel_id" => $model->id,
                    "kategori_id" => null
                ];
                $kategori = $db->insert('kategori_artikel', $arrayx);
            }    
        }
        
        return successResponse($response, "berhasil");  
});

$app->get('/m_topnews/insertAllKategori', function ($request, $response) {
        set_time_limit(0);
        $string = file_get_contents("file/data1.json");
        $json_a = json_decode($string, true);

        $db   = $this->db;
        $array = [];
        foreach ($json_a['feed']['category'] as $key => $value) {
            $array[$key] = [
                "nama" => $value['term'],
                "alias" => $value['term'],
            ];

            $model = $db->insert("kategori", $array[$key]); 
        }
        return successResponse($response, "berhasil");
});

$app->get('/m_topnews/getPenulis', function ($request, $response) {
        // set_time_limit(0);
        $db   = $this->db;
        $author = $db->select("DISTINCT author")
                ->from('artikel')
                ->findAll();
        // echo json_encode($author);exit;

        $array = [];
        foreach ($author as $key => $value) {
            $username = str_replace(" ","",$value->author);
            $username = strtolower($username);
            $array[$key] = [
                "nama" => $value->author,
                "username" => $username,
                "password" => sha1($username),
                "is_deleted" => 0,
                "m_pengguna_akses_id" => 3,
            ];
            // echo json_encode($array[$key]);exit;
            $pengguna = $db->insert('m_pengguna',$array[$key]);
        }
        // echo json_encode($array);exit;
        return successResponse($response, "berhasil");    
});

$app->get('/m_topnews/changePenulis', function ($request, $response) {
        set_time_limit(0);
        ini_set('memory_limit', '200M');
        $db   = $this->db;
        $penulis = $db->select("id,nama")
                ->from('m_pengguna')
                ->findAll();
        // echo json_encode($author);exit;
        $artikel = $db->select('*')
                    ->from('artikel')
                    ->findAll();

        $array = [];
        foreach ($artikel as $key => $value) {
            foreach ($penulis as $keys => $values) {
                if ($value->author == $values->nama) {
                    $array[$key] = [
                        "created_by" => $values->id,
                    ];
                    $pengguna = $db->update('artikel',$array[$key],['id' => $value->id]);
                }
            }
            
            // echo json_encode($array[$key]);exit;
        }
        // echo json_encode($array);exit;
        return successResponse($response, "berhasil");    
});
/** END UPLOAD */
