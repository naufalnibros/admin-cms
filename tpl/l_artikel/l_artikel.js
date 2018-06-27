app.controller('lartikelCtrl', function ($scope, Data, toaster) {
    
    Data.get('l_artikel/getTahun').then(function (data) {
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

        console.log('Lihat data :', bulan,  tahun, $scope.lihat );
        

        Data.get('l_artikel/lihatData/'+ bulan+'/'+ tahun).then(function (data) {
            $scope.penulis = data.data.data; 
            $scope.total_day = data.data.total_day; 
            $scope.day = data.data.day; 
        });
        
    };



})

