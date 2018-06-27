<?php

$app->get('/dashboard/getTahun', function ($request, $response) {
    $date = date("Y");
    $year=(int)$date;
    // var_dump($year);exit();
    $tahun = [];
    for($i=3; $i>0; --$i){
        --$year;
        array_push($tahun, intval($year));
    }

    // for ($i = 0 ; $i <= 4 ; $i++) { 
    //    $tahun[] = $year - $i;
    // }
    // exit();
    $koleksi_bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $bulan = []; 
    $no=0;
    foreach($koleksi_bulan as $value){
        $bulan[$no]['val'] = $no+1;
        $bulan[$no]['nama'] = $value;
        $no++;
    }

    $a = date("m");
    $sekarang = (int)$a-1;
    
    return successResponse($response, ['tahun' => $tahun, 'bulan' => $bulan, 'sekarang' => $koleksi_bulan[$sekarang]]);
});

$app->get('/dashboard/chart/{bulan}/{tahun}', function ($request, $response) {
    $bulan = intval($request->getAttribute('bulan'));
    $tahun = intval($request->getAttribute('tahun'));
    
    $db = new Cahkampung\Landadb(Db());
    
    //dapatkan jumlah hari dalam bulan ini
    $last  = new Datetime($tahun . '-' . $bulan . '-01'); 
    $last->modify('last day of this month'); 
    $last_day = $last->format('d');
    
    $hari = [];
    $jumlah = [];
    for($val=1; $val<=$last_day; $val++){
        $date = new Datetime($tahun . '-' . $bulan . '-' .$val);
        $this_day = strtotime($date->format('Y-m-d'));
        
        $model = $db->select("COUNT(id) as view")
                    ->from('views')
                    ->where('tanggal', '=', $this_day)
                    ->find();

        array_push($jumlah, $model->view);
        array_push($hari, $val);
    }

    return successResponse($response, ['hari' => $hari, 'jumlah' => $jumlah]);
});

$app->get('/dashboard/trending', function ($request, $response) {
    $db = new Cahkampung\Landadb(Db());
    $trending = $db->select("COUNT(artikel.judul) as view, artikel.*")
            ->from('views')
            ->join("left join", 'artikel', 'artikel.id = views.artikel_id')
            ->groupBy('artikel.judul')
            ->orderBy('view DESC')
            ->limit(10)
            ->findAll();
           
    
    return successResponse($response, $trending);
});

/* API Detail Artikel */
$app->get('/dashboard/detailArtikel/{alias}', function ($request, $response) {
    $db = $this->db;
    $alias   = $request->getAttribute('alias');

    $model = $db->select("*")
            ->from("artikel")
            ->where("alias", "=", $alias)
            ->find();

    return successResponse($response, $model);
});

/* API List kategori */
$app->get('/dashboard/kategori', function ($request, $response) {
    $db = $this->db;

    $model = $db->select("*")
            ->from("kategori")
            ->findAll();

    return successResponse($response, $model);
});

/* API Pencarian Berita berdasarkan kategori */
$app->get('/dashboard/cariKategori/{kategori}', function ($request, $response) {
    $db         = $this->db;
    $kategori   = $request->getAttribute('kategori');
    
    $model = $db->select("*")
            ->from("artikel as ar")
            ->join('left join', 'kategori_artikel as ka', 'ar.id = ka.artikel_id')
            ->join('left join', 'kategori as k', 'k.id = ka.kategori_id')
            ->where('k.nama', '=', $kategori)
            ->findAll();

    return successResponse($response, $model);
});

/* API Pencarian Berita berdasarkan Judul */
$app->get('/dashboard/cariJudul/{judul}', function ($request, $response) {
    $db         = $this->db;
    $judul   = $request->getAttribute('judul');
    
    $model = $db->select("*")
            ->from("artikel")
            ->where('judul', 'LIKE', $judul)
            ->findAll();

    return successResponse($response, $model);
});