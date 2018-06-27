<?php
$app->get('/mig/images/{id}', function ($request, $response,$args) {


    $sql = $this->db->select('*')
        ->from('artikel')
        ->where('id','=',$args['id'])
        ->orderBy('id DESC')
        ->findAll();

    foreach ($sql as $key => $value) {
        $img = get_images($value->content);

        for ($i = 0; $i < count($img); $i++) {
            $path = '../img/artikel/' . date("m-Y") . '/';
            if (!file_exists($path)) {
                mkdir($path, 0777);
            }
            if ($i == 0) {
                $image                = get_img($img[$i], $path, 'img-' . $i . '-');
                $crtImg               = createImageCopy($path, $image, '', false);
                $data['gambar_thumb'] = getenv("SITE_URL") . $path . $crtImg['small'];
                $data['gambar_primary'] = getenv("SITE_URL") . $path . $crtImg['big'];
            }

            if (empty($img[0])){
                $data['gambar_thumb'] = '';
            }
        }
        $model = $this->db->update("artikel", $data, ['id' => $value->id]);

    }
    if ($model) {
        echo "sukses";
    }else{
        echo "gagl";
    }
});
$app->get('/mig/changeurl/{id}', function ($request, $response,$args) {
    // var_dump($args['id']);exit();
    $sql = $this->db->select('a.*')
    ->from('kategori_artikel as k')
    ->join('left join','artikel as a','k.artikel_id = a.id')
    ->where('k.kategori_id','=',$args['id'])
    ->findAll();
    $data = [];
    foreach ($sql as $key => $value) {
        if (strpos($value->content, 'http://penulis.gohijrah.com/api/') !== false) {

           $data = $value->id;
        }
    }

    print_r($data);exit();
});

$app->get('/mig/urlthumb', function ($request, $response,$args) {


    $sql = $this->db->select('gambar_thumb,id')
        ->from('artikel')
        // ->where('id','=',$args['id'])
        ->findAll();

       

        foreach ($sql as $key => $value) {
    
        $data['gambar_thumb'] = str_replace('http://admin.dilarangbego.com/api/../','http://admin.dilarangbego.com/api/../img',$value->gambar_thumb);

        $up = $this->db->update("artikel", $data, ['id' => $value->id]);


        }

        if ($up) {
            echo "Sukses";
        } else{
            echo "Salah";
        }

  
});


$app->get('/mig/urlgambarconten/{id}', function ($request, $response,$args) {


    $sql = $this->db->select('content,id')
        ->from('artikel')
        ->where('id','=',$args['id'])
        ->findAll();

        foreach ($sql as $key => $value) {
    
        $data['content'] = str_replace('http://27.111.36.178/admin/','http://admin.dilarangbego.com/',$value->content);

        $up = $this->db->update("artikel", $data, ['id' => $value->id]);


        }
        
        if ($up) {
            echo "Sukses";
        } else{
            echo "Salah";
        }

  
});


$app->get('/mig/dari/{dari}', function ($request, $response,$args) {


    $sql = $this->db->select('content,id')
        ->from('artikel')
        // ->where('id','=',$args['id'])
        ->limit(10)
        ->offset($args['dari'])
        ->orderBy('id ASC')
        ->findAll();

        foreach ($sql as $key => $value) {
    
        $data['content'] = str_replace('http://27.111.36.178/admin/','http://admin.dilarangbego.com/',$value->content);

        $up = $this->db->update("artikel", $data, ['id' => $value->id]);


        }
        
        if ($up) {
            echo "Sukses";
        } else{
            echo "Salah";
        }

  
});

$app->get('/mig/list', function ($request, $response,$args) {


    $sql = $this->db->select('content,id')
        ->from('artikel')
        ->findAll();

      echo json_encode($sql);

  
});
$app->get('/mig/cek', function ($request, $response,$args) {


    echo 'sapi lagi';

  
});

