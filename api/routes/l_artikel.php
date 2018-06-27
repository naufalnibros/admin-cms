<?php

$app->get('/l_artikel/getTahun', function ($request, $response) {
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

$app->get('/l_artikel/lihatData/{bulan}/{tahun}', function ($request, $response) {
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
        $jumlah = [];

        for($val=1; $val<=$last_day; $val++){
            $start_date = new Datetime($tahun . '-' . $bulan . '-' .$val);
            $end_date = new Datetime($tahun . '-' . $bulan . '-' .$val);
            $end_date->modify('+1 day');

            $start = strtotime($start_date->format('Y-m-d'));
            $end = strtotime($end_date->format('Y-m-d'));
            
            $model = $db->select("COUNT(a.id) as jumlah")
                        ->from('artikel as a')
                        ->where('created_by', '=', $value->id)
                        ->customWhere("a.created_at BETWEEN '$start' AND '$end'", 'AND')
                        ->find();
            array_push($jumlah, $model->jumlah);
        }

        $data[$no]['artikel'] = $jumlah;
        $no++;
    }

    return successResponse($response, ['data' => $data, 'total_day' => $last_day, 'day' => $hari]);
});