app.controller('kategoriCtrl', function($scope, Data, toaster,$uibModal) {
    var tableStateRef;
    var control_link = "appkategori";
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.form = {};
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.listHakakses = [];
    /** get list data */
    $scope.callServer = function callServer(tableState) {
        tableStateRef = tableState;
        $scope.isLoading = true;
        /** set offset and limit */
        var offset = tableState.pagination.start || 0;
        var limit = tableState.pagination.number || 10;
        var param = {
            offset: offset,
            limit: limit
        };
        /** set sort and order */
        if (tableState.sort.predicate) {
            param['sort'] = tableState.sort.predicate;
            param['order'] = tableState.sort.reverse;
        }
        /** set filter */
        if (tableState.search.predicateObject) {
            param['filter'] = tableState.search.predicateObject;
        }
        Data.get(control_link + '/index', param).then(function(response) {
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
        });
    }; 

    //  Data.get('appkategori/kategoriparent').then(function(data) {
    //        $scope.list   =   data.data.list;
    //  });

    //   $scope.getkode = function(form) {
    //     Data.get('appkategori/kodeparent/' + form.parent_id).then(function(data) {
    //        $scope.form.kode   =   data.data.kode;
    //  });
    // };
    /** create */
    $scope.create = function(form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.formtitle = "Form Tambah Data"; 
        $scope.form = {};
     //     Data.get('appkategori/kategoriparent').then(function(data) {
     //       $scope.list   =   data.data.list;
     // });

    };

     $scope.UrlParse = function (value) {
        var words = value.split(" ");
        if (words.length <= 6) {
            $scope.form.alias = value.replace(/[^a-z0-9]/gi, '-').toLowerCase();
        } else {
            var kata = words[0] + " " + words[1] + " " + words[2] + " " + words[3] + " " + words[4] + " " + words[5];
            $scope.form.alias = kata.replace(/[^a-z0-9]/gi, '-').toLowerCase();
        }
    };
    
    /** update */
    $scope.update = function(form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.formtitle = "Edit Data : " + form.nama; 
        $scope.form = form;
    };
    /** view */
    $scope.view = function(form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.formtitle = "Lihat Data : " + form.nama; 
        $scope.form = form; 
    };
    /** save action */
    $scope.save = function(form) { 
        Data.post(control_link + '/save', form).then(function(result) {
            if (result.status_code == 200) {
                $scope.is_edit = false;
                $scope.callServer(tableStateRef);
                toaster.pop('success', "Berhasil", "Data berhasil tersimpan");
            } else {
                toaster.pop('error', "Terjadi Kesalahan", setErrorMessage(result.errors));
            }
        });
    };

    $scope.resize = function(){
        
        var MAX_HEIGHT = 200;
        
        //dapatkan gambarnya, catat lebar dan tingginya
        var img = document.getElementById("myImage");
        var w = img.width;
        var h = img.height;
        
        //inisialisasi canvasnya
        var canvas = document.createElement('canvas');
        
        //Periksa ukuran image, dan lakukan resize
        if(h > MAX_HEIGHT){
            w = w * (MAX_HEIGHT / h);
            h = MAX_HEIGHT;
        }
        
        //Buat drawing object
        var ctx = canvas.getContext('2d');
        canvas.width  = w;
        canvas.height = h;
        
        ctx.drawImage(img, 0, 0, w, h);
        //document.body.appendChild(canvas);
        
        //Convert ke base 64 image
        var pngUrl = canvas.toDataURL();
    
        $scope.kecil = pngUrl;
        //console.log($scope.kecil);
    };
    /** cancel action */
    $scope.cancel = function() {
        if (!$scope.is_view) {
            $scope.callServer(tableStateRef);
        }
        $scope.is_edit = false;
        $scope.is_view = false;
    };
    $scope.viewGambar = function (form) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/kiriman/viewgambar.html',
            controller: 'viewImgCtrl',
            size: 'md',
            backdrop: 'static',
            resolve: {
                form: function () {
                    return form;
                }
            }
        });
        modalInstance.result.then(function (result) {
        }, function () {

        });
    };

     $scope.trash = function (row) {
    swal({
          title: "Peingatan ! ",
          text: "Apakah Anda Yakin Ingin Menhapus Data Ini",
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DD6B55",
          confirmButtonText: "Iya, di Hapus",
          cancelButtonText: "Tidak",
          closeOnConfirm: false,
          closeOnCancel: false
        },
        function(isConfirm){
          if (isConfirm) {
             row.is_deleted = 1;
            Data.post(control_link + '/save', row).then(function(result) {
                $scope.displayed.splice($scope.displayed.indexOf(row), 1);
            });
            swal("Terhapus", "Data Berhasil Di Hapus.", "success");
          } else {
            swal("Membatalkan", "Membatalkan Menghapus Data:)", "error");
          }
        });
    };




    $scope.restore = function (row) {
    swal({
          title: "Peingatan ! ",
          text: "Apakah Anda Yakin Ingin Restore Data Ini",
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DD6B55",
          confirmButtonText: "Iya, di Restore",
          cancelButtonText: "Tidak",
          closeOnConfirm: false,
          closeOnCancel: false
        },
        function(isConfirm){
          if (isConfirm) {
            row.is_deleted = 0;
            Data.post(control_link + '/save', row).then(function(result) {
                $scope.displayed.splice($scope.displayed.indexOf(row), 1);
            });
            swal("Restore", "Data Berhasil Di Restore.", "success");
          } else {
            swal("Membatalkan", "Membatalkan Restore Data:)", "error");
          }
        });
    };


  
         $scope.delete = function (row) {
            swal({
          title: "Peringatan",
          text: "Anda Akan Menghapus Permanent I",
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DD6B55",
          confirmButtonText: "Ya,di hapus",
          cancelButtonText: "Tidak",
          closeOnConfirm: false,
          closeOnCancel: false
        },
        function(isConfirm){
          if (isConfirm) {
          Data.delete(control_link + '/delete/' + row.id).then(function(result) {
            if (result.status_code == 200) {
                $scope.is_edit = false;
                $scope.displayed.splice($scope.displayed.indexOf(row), 1);
                toaster.pop('success', "Berhasil", "Data berhasil dihapus");
                swal("Terhapus", "Data terhapus.", "success");
            } else {
                toaster.pop('error', "Terjadi Kesalahan", setErrorMessage(result.errors));
                swal("Membatalkan", "Membatakan menghapus data", "error");
            }
            });
          } else {
            swal("Membatalkan", "Membatakan menghapus data", "error");
          }
        });
    };
})
app.controller('viewImgCtrl', function ($state, $scope, toaster, Data, $uibModalInstance, form) {

    $scope.formmodal = form;
    $scope.close = function () {
        $uibModalInstance.dismiss('cancel');
    };
});