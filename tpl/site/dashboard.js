app.controller('dashboardCtrl', function ($scope, Data, $state) {
    $scope.authError = null;

    $scope.login = function (form) {
        $scope.authError = null;

        Data.post('site/login/', form).then(function (result) {
            if (result.status == 0) {
                $scope.authError = result.errors;
            } else {
                $state.go('site.dashboard');
            }
        });
    };

  Data.get('dashboard/trending').then(function (data) {
    $scope.trending = data.data;
  });

  /* awal chart */
  Data.get('dashboard/getTahun').then(function (data) {
    $scope.sekarang = data.data.sekarang;
    $scope.bulan = data.data.bulan;
    $scope.tahun = data.data.tahun;
  });
  
  $scope.date = new Date();
  $scope.xyear = $scope.date.getFullYear();
  $scope.xmonth = $scope.date.getMonth() + 1;
  
  //saat load, otomatis menginisialisasi chart
  Data.get('dashboard/chart/'+ $scope.xmonth+'/'+ $scope.xyear).then(function (data) {
          // $scope.series = ['Views', 'a'];
          $scope.labels = data.data.hari;
          $scope.data = data.data.jumlah;
      });   
  $scope.bln = new Date().getMonth()+1;
  $scope.getChart = function (bulan, tahun) {
      if(tahun < 1 || tahun == undefined){
        tahun = $scope.xyear;
      }
      
      if(bulan == '' || bulan == undefined){
        bulan = $scope.xmonth;
      }

      console.log('Chart :', bulan,  tahun );
      
      Data.get('dashboard/chart/'+ bulan+'/'+ tahun).then(function (data) {
          // $scope.series = ['Views', 'a'];
          $scope.labels = data.data.hari; //urutan hari dalam sebulan
          $scope.data = data.data.jumlah; 
      });
      
  };
  $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.formtitle = "Lihat Data : " + form.judul;
        $scope.form = form;
        $scope.form.tanggal = new Date(form.jam);
        $scope.form.jam = new Date(form.jam);
        $scope.form.publish = parseInt(form.publish);
    };
    $scope.cancel = function () {
        $scope.is_edit = false;
        $scope.is_view = false;
    };
    /* akhir chart */


    /*Data Line Chart*/
      // $scope.labels = ["January", "February", "March", "April", "May", "June", "July"];
      // $scope.series = ['View'];
      // $scope.data = [
      //   [65, 59, 80, 81, 56, 55, 40],
      //   [28, 48, 40, 19, 86, 27, 90]
      // ];
      $scope.onClick = function (points, evt) {
        console.log(points, evt);
      };
      $scope.datasetOverride = [{ yAxisID: 'y-axis-1' }, { yAxisID: 'y-axis-2' }];
      $scope.options = {
        scales: {
          yAxes: [
            {
              id: 'y-axis-1',
              type: 'linear',
              display: true,
              position: 'left'
            },
            {
              id: 'y-axis-2',
              type: 'linear',
              display: true,
              position: 'right'
            }
          ]
        }
      };

   /*Data Line Chart*/

   /*Doughnut Chart*/

  //  $scope.labelsdoughnut = ["Download Sales", "In-Store Sales", "Mail-Order Sales"];
  //  $scope.datadoughnut = [300, 500, 100];
   
   /*Doughnut Chart*/

   /*Bar Chart*/

  // $scope.labelsbar = ['2006', '2007', '2008', '2009', '2010', '2011', '2012'];
  // $scope.seriesbar = ['Series A', 'Series B'];

  // $scope.databar = [
  //   [65, 59, 80, 81, 56, 55, 40],
  //   [28, 48, 40, 19, 86, 27, 90]
  // ];

  /*Bar Chart*/


  /*Horisontal*/
  //  $scope.labelshorisontal = ['2006', '2007', '2008', '2009', '2010', '2011', '2012'];
  //   $scope.serieshorisontal = ['Series A', 'Series B'];

  //   $scope.datahorisontal = [
  //     [65, 59, 80, 81, 56, 55, 40],
  //     [28, 48, 40, 19, 86, 27, 90]
  //   ];
  /*Horisontal*/

})
