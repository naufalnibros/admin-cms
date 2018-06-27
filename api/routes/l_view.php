<?php

$app->get('/l_view/getTahun', function ($request, $response) {
    $year=date("Y");
    $tahun=[];
    
    for($i=4; $i>0; --$i){
        array_push($tahun, intval($year));
        $year--;
    }
    
    $koleksi_bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $bulan = []; 
    $no=0;
    foreach($koleksi_bulan as $value){
        $bulan[$no]['val'] = $no+1;
        $bulan[$no]['nama'] = $value;
        $no++;
    }

    return successResponse($response, ['tahun' => $tahun, 'bulan' => $bulan]);
});

$app->get('/l_view/lihatData/{bulan}/{tahun}', function ($request, $response) {
    $bulan = intval($request->getAttribute('bulan'));
    $tahun = intval($request->getAttribute('tahun'));
    
    $db = new Cahkampung\Landadb(Db());
    
    //dapatkan jumlah hari dalam bulan ini
    $last  = new Datetime($tahun . '-' . $bulan . '-01'); 
    $last->modify('last day of this month'); 
    $last_day = $last->format('d');
    
    $penulis = $db->select("nama, id")
                ->from("m_pengguna")
                ->where('is_deleted', '=', 0)
                ->findAll();
    
    $hari = [];
    for($val=1; $val<=$last_day; $val++){
        array_push($hari, $val);
    }

    $data = [];
    $no = 0;
    foreach($penulis as $key => $value){
        
        $data[$no]['penulis'] = $value->nama;
        $data[$no]['id'] = $value->id;
        $jumlah = [];

        for($val=1; $val<=$last_day; $val++){
            $start_date = new Datetime($tahun . '-' . $bulan . '-' .$val);
            $end_date = new Datetime($tahun . '-' . $bulan . '-' .$val);
            $end_date->modify('+1 day');

            $start = strtotime($start_date->format('Y-m-d'));
            $end = strtotime($end_date->format('Y-m-d'));
            
            $model = $db->select("COUNT(a.id) as jumlah")
                        ->from("m_pengguna as p")
                        ->join('left join', 'artikel as a', 'a.created_by = p.id')
                        ->join('left join', 'views as v', 'v.artikel_id = a.id')
                        ->where('created_by', '=', $value->id)
                        ->where('v.tanggal', '=', $start)
                        ->find();
            array_push($jumlah, $model->jumlah);
        }

        $data[$no]['artikel'] = $jumlah;
        $no++;
    }

    return successResponse($response, ['data' => $data, 'total_day' => $last_day, 'day' => $hari]);
});


$app->get('/l_view/detailView/{user_id}/{hari}/{bulan}/{tahun}', function ($request, $response) {
    $db = new Cahkampung\Landadb(Db());

    $user_id = $request->getAttribute('user_id');
    $tahun = $request->getAttribute('tahun');
    $bulan = $request->getAttribute('bulan');
    $hari = $request->getAttribute('hari');
    
    //dapatkan jumlah hari dalam bulan ini
    $start  = new Datetime($tahun . '-' . $bulan . '-' . $hari); 
    $next  = new Datetime($tahun . '-' . $bulan . '-' . $hari); 
    $next->modify('+1 day');
    $start = strtotime($start->format('Y-m-d'));
    $next = strtotime($next->format('Y-m-d'));
    
    $tanggal = date('Y-m-d', $start);

    $laporan = $db->select("COUNT(v.id) as view, a.judul")
                ->from("m_pengguna as p")
                ->join('left join', 'artikel as a', 'a.created_by = p.id')
                ->join('left join', 'views as v', 'v.artikel_id = a.id')
                ->where('p.id', '=', $user_id)
                ->andWhere('v.tanggal', '=', $start)
                ->groupBy('a.judul')
                ->orderBy('view DESC ,judul ASC')
                ->findAll();
    // var_dump($laporan);
    // exit();
    $penulis = $db->find("SELECT nama FROM m_pengguna as p WHERE p.id ={$user_id}");

    return successResponse($response, ['penulis' => $penulis->nama, 'tanggal' => $tanggal, 'laporan' => $laporan]);
});