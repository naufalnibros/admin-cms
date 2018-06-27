app.controller('lviewCtrl', function ($scope, Data, toaster) {
    
    Data.get('l_view/getTahun').then(function (data) {
        $scope.bulan = data.data.bulan;
        $scope.tahun = data.data.tahun;
    });
//  $scope.exportData = function (namaSekolah, tahun) {
  $scope.exportData = function () {
        var blob = new Blob([document.getElementById('exportlaporan').innerHTML], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
        });
        saveAs(blob, "Laporan-Penulis-Harian.xls");
    };

    $scope.lihatData = function (bulan, tahun) {
        $scope.lihat = 1;
        // console.log('Lihat data :', bulan,  tahun, $scope.lihat );
        
        Data.get('l_view/lihatData/'+ bulan+'/'+ tahun).then(function (data) {
            $scope.penulis = data.data.data; 
            $scope.total_day = data.data.total_day; 
            $scope.day = data.data.day; 
        });
    };

    $scope.detailView = function (user_id, hari, bulan, tahun) {

        // console.log(user_id, ' ', hari, ' ',  bulan, ' ', tahun);

        Data.get('l_view/detailView/'+ user_id +'/'+ hari +'/'+bulan +'/'+ tahun).then(function (data) {
            $scope.modal_user = data.data.penulis;
            $scope.modal_tanggal = data.data.tanggal;
            $scope.modal_laporan = data.data.laporan;
        });
    };

})

