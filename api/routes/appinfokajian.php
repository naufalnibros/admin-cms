<?php

/**
 * Validasi
 * @param  array $data
 * @param  array $custom
 * @return array
 */
function validasi($data, $custom = array()) {
    $validasi = array(
        'judul' => 'required',
        'content' => 'required',
        'tanggal' => 'required',
        'pembicara' => 'required',
        'lokasi' => 'required',
        'alias' => 'required',
        'keyword' => 'required',
        'description' => 'required',
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

/**
 * get list m_infokajian
 */
$app->get('/appinfokajian/index', function ($request, $response) {
    $params = $request->getParams();

    $sort = "m_infokajian.id DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;
    $db = $this->db;
    $db->select("m_infokajian.*, p.nama")
    ->from('m_infokajian')
    ->join('left join', 'm_pengguna as p', 'm_infokajian.created_by = p.id');
    /** set parameter */
    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
           
            if ($key == 'is_deleted') {
                $db->where('m_infokajian.is_deleted', '=', $val);
            }
            elseif ($key == 'created_by') {
                $db->where('p.nama', 'LIKE', $val);
            }
            elseif ($key == 'pembicara') {
                $db->where('m_infokajian.pembicara', 'LIKE', $val);
            }
            else{
                $db->where($key, 'LIKE', $val);
            }
        }
       
    }


    if ($_SESSION['user']['m_pengguna_akses_id'] != 1) {
       $db->andwhere('m_infokajian.created_by', '=', $_SESSION['user']['id']);
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
    $db->orderBy($sort);


    // $models = $db->log();
    $models = $db->findAll();
    // var_dump($models);
    // exit();
    $totalItem = $db->count();
    $return = [];
    foreach ($models as $key => $val) {

        $return[$key] = (array)$val;
        $pengguna = $db->select("nama")
        ->from('m_pengguna')
        ->where('id', '=', $val->created_by)
        ->find();
        $return[$key]['content'] = contentParsing($val->content);
        $return[$key]['tanggal'] = date('Y-m-d', $val->tanggal);
        $return[$key]['jam'] = date('Y-m-d H:i' ,$val->jam);
        $return[$key]['creator'] = $pengguna->nama;
        $return[$key]['preview'] = 'preview/info/kajian/'.$val->alias;        
        $return[$key]['img']     = $val->gambar_thumb ? $val->gambar_thumb : SITE_URL(). '../img/artikel/default-thumb.png';
    }
    // array_multisort($return, SORT_DESC);

    return successResponse($response, ['list' => $return, 'totalItems' => $totalItem]);
});


/**
 * save m_infokajian
 */
$app->post('/appinfokajian/save', function ($request, $response) {
    
    $data = $request->getParams();

    $db = $this->db;  
    $data['judul']   = isset($data['judul']) ? $data['judul'] : '';
    $data['content'] = isset($data['content']) ? $data['content'] : '';
    $validasi        = validasi($data);
    //
    if ($validasi === true) {
        $img = get_images($data['content']);
       

        $url = []; 
        if ($img) {
        for ($i = 0; $i < count($img); $i++) {  
            $path = '../img/infokajian/' . date("m-Y") . '/';
            if (!file_exists($path)) {
                    mkdir($path, 0777);
                }
           
            if (strpos($img[$i], ';base64') !== false) {

                $uploadFileName = base64toImg($img[$i], $path, 'img-'.$i.'-');
               
                $crtImg = createImags_kajian($path , $uploadFileName['data'], '', true,$i);

                // $file = $path . $crtImg['big'];
                $url  = getenv("SITE_URL") . $path . $crtImg['big'];
                 
              if ($i == 0) {
                       $data['gambar_thumb'] = getenv("SITE_URL") . $path . $crtImg['small'];
                       $data['gambar_primary'] = getenv("SITE_URL") . $path . $crtImg['big'];
                    }
                $data['content'] = str_replace($img[$i], $url, $data['content']);
            } else{
               if (isset($data['id'])) {
                    $query = $this->db->select('content')
                        ->from('m_infokajian')
                        ->where('id', '=', $data['id'])
                        ->find();

                    if (strpos($query->content, $img[$i]) !== false) {
                        $image = $img[$i];
                    } else {
                    if ($i == 0) {
                    $image           = get_img($img[$i], $path, 'img-' . $i . '-');                    
                    // $crtImg          = createImageThumb($path , $image, '', false);
                    $crtImg          = createImageCopy_kajian($path , $image, '', true);
                    $data['gambar_thumb'] = getenv("SITE_URL") . $path . $crtImg['small'];
                    $data['gambar_primary'] = getenv("SITE_URL") . $path . $crtImg['big'];
                    $url  = getenv("SITE_URL") . $path . $crtImg['big'];
                    $data['content'] = str_replace($img[$i], $url, $data['content']);
                    }
                    }
                } else {
                    
                    if ($i == 0) {
                     $image           = get_img($img[$i], $path, 'img-' . $i . '-');
                     // var_dump($image);exit();                   
                    // $crtImg          = createImageThumb($path , $image, '', false);
                    $crtImg          = createImageCopy_kajian($path , $image, '', true);
                    $data['gambar_thumb'] = getenv("SITE_URL") . $path . $crtImg['small'];
                    $data['gambar_primary'] = getenv("SITE_URL") . $path . $crtImg['big'];
                    $url  = getenv("SITE_URL") . $path . $crtImg['big'];
                    $data['content'] = str_replace($img[$i], $url, $data['content']);
                    }
                }
            }            
        }  // end
        }else {
            $url_primary  = getenv("SITE_URL"). '../img/default.png';
            $url_thumb  = getenv("SITE_URL"). '../img/default-thumb.png';
            $data['gambar_thumb'] = $url_thumb;
            $data['gambar_primary'] = $url_primary;

                // $data['content'] = str_replace($img[$i], $url_primary, $data['content']);
        }
       // print_r($data['content']);exit();
        //cek apakah alias unique
        $db->select("alias")
            ->from("m_infokajian")
            ->where('alias', '=', $data['alias'])
            ->andwhere('is_deleted', '=', 0);
        if (isset($data['id'])) {
            $db->andwhere('id', '<>', $data['id']);
        }
        $periksa_alias = $db->find();

        if (!$periksa_alias) {
            if (isset($data['id'])) {
                
            $data['tanggal'] = strtotime( date('Y-m-d', strtotime($data['tanggal'])));
            $data['jam'] = strtotime( date('H:i', strtotime($data['jam'])));          
                // var_dump($data);exit();
                $model = $db->update("m_infokajian", $data, ['id' => $data['id']]);

            } else {
            $data['tanggal'] = strtotime( date('Y-m-d', strtotime($data['tanggal'])));
            $data['jam'] = strtotime( date('H:i', strtotime($data['jam'])));
                 
                $model           = $db->insert("m_infokajian", $data);
            }
            return successResponse($response, $model);
        } else {
            //respon jika alias sama
            return unprocessResponse($response, ['Alias harus unik, buatlah alias yang baru!']);
        }
    }
    return unprocessResponse($response, $validasi);
});

$app->post('/appinfokajian/update', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    try {
        $data['tanggal'] = strtotime( date('Y-m-d', strtotime($data['tanggal'])));
        $data['jam'] = strtotime( date('H:i', strtotime($data['jam'])));
        $model = $db->update("m_infokajian", $data, ['id' => $data['id']]);
        

        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal disimpan']);
    }
//    }
});

/**
 * delete m_infokajian
 */
$app->delete('/appinfokajian/delete/{id}', function ($request, $response) {
    $db = $this->db;

    try {
        $delete = $db->delete('m_infokajian', array('id' => $request->getAttribute('id')));
        return successResponse($response, ['data berhasil dihapus']);
    } catch (Exception $e) {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});

/** UPLOAD GAMBAR CKEDITOR */
$app->post('/appinfokajian/upload', function ($request, $response) {
    $files = $request->getUploadedFiles();
    $newfile = $files['upload'];
   
    if (file_exists("file/" . $newfile->getClientFilename())) {
        echo $newfile->getClientFilename() . " already exists please choose another image.";
    } else {

        $path = '../img/infokajian/' . date("m-Y") . '/';
        if (!file_exists($path)) {
            mkdir($path, 0777);
        }

      // $uploadFileName = urlParsing($newfile->getClientFilename());
        $uploadFileName = $newfile->getClientFilename();
        $upload = $newfile->moveTo($path . $uploadFileName);

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

/** END UPLOAD */