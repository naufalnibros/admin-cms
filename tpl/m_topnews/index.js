app.controller('topNewsCtrl', function ($scope, Data, toaster,textAngularManager) {
    var tableStateRef;
    var control_link = "m_topnews";
    $scope.displayed = [];
    $scope.listartikel = [];
    $scope.form = {};
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.version = textAngularManager.getVersion();
    $scope.number = [];
    Data.get(control_link + '/getNumber').then(function (response) {
        $scope.number = response.data;
    });
    $scope.versionNumber = $scope.version.substring(1);
    $scope.orightml = '';
    $scope.form.content = $scope.orightml;
    $scope.disabled = false;

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
        Data.get(control_link + '/index', param).then(function (response) {
            // console.log(response.data.lists)
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
        });
    };
    
   

   $scope.cariArtikel = function (val) {
        if (val.length >= 3) {
           
            Data.get(control_link + '/getArtikel/' + val).then(function (data) {
                $scope.getArtikel = data.data;
                console.log(data.data);
            });
        } else if (val.length == undefined || val.length == 0) {

        } else {
            toaster.pop('warning', "Peringatan!", "Masukkan minimal 3 karakter");
        }
    };

    $scope.opened = {};
    $scope.toggle = function ($event, elemId) {
        $event.preventDefault();
        $event.stopPropagation();
        $scope.opened[elemId] = !$scope.opened[elemId];
    };
    /** create action */
    $scope.create = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.formtitle = "Form Tambah Data";
        $scope.form = {};
        // $scope.form.tanggal = new Date();
        // $scope.form.jam = new Date();
        // $scope.form.publish = '1';
    };
    // urlParsing js
    $scope.UrlParse = function (value) {
        var words = value.split(" ");
        if (words.length <= 100) {
            $scope.form.alias = value.replace(/[^a-z0-9]/gi, '-').toLowerCase();
        } else {
            var kata = words[0] + " " + words[1] + " " + words[2] + " " + words[3] + " " + words[4] + " " + words[5];
            $scope.form.alias = kata.replace(/[^a-z0-9]/gi, '-').toLowerCase();
        }
    };

    $scope.keword = function (value) {
        var words = value.split(" ");
        
            $scope.form.keyword = value.replace(/[^a-z0-9]/gi, ', ').toLowerCase();
    };
    
     $scope.get_koma = function (value) {
     $scope.form.keyword = value.replace(/\n/g, ", ").toLowerCase();
    };
    
    /** update action */
    $scope.update = function (form) {
        
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.formtitle = "Edit Data : " + form.artikel;
        $scope.form = form;
        // $scope.form.artikel_id = form.artikel_id;
        $scope.form.nomer = form.nomer.toString();
        $scope.form.date_start = new Date(form.date_start);
        $scope.form.date_end = new Date(form.date_end);
        Data.get(control_link + '/getArtikelById/' + form.artikel_id).then(function (data) {
               $scope.form.artikel = data.data;
            });
        console.log(form);
        // $scope.form.tanggal = new Date(form.jam);
        // $scope.form.jam = new Date(form.jam);
        // $scope.form.publish = parseInt(form.publish);
//        console.log('update', $scope.form);
    };
    /** view data */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.formtitle = "Lihat Data : " + form.artikel;
        // $scope.form.tanggal = new Date(form.jam);
        // $scope.form.jam = new Date(form.jam);
        // $scope.form.publish = parseInt(form.publish);
        $scope.form = form;
        // $scope.form.artikel_id = form.artikel_id;
        $scope.form.nomer = form.nomer.toString();
        $scope.form.date_start = new Date(form.date_start);
        $scope.form.date_end = new Date(form.date_end);
        Data.get(control_link + '/getArtikelById/' + form.artikel_id).then(function (data) {
               $scope.form.artikel = data.data;
            });
        console.log(form);
    };
    /** save request */
    $scope.save = function (form) {
        console.log(form);
         // form.publish = '1';
       var url = '/save';
       Data.post(control_link + url, form).then(function (result) {
           if (result.status_code == 200) {
               $scope.is_edit = false;
               $scope.callServer(tableStateRef);
               toaster.pop('success', "Berhasil", "Data berhasil tersimpan");
           } else {
               toaster.pop('error', "Terjadi Kesalahan", setErrorMessage(result.errors));
           }
       });
    };
     /** save draft request */
    $scope.save_draft = function (form) {
        console.log(form.publish);
        form.publish = '0';
       var url = '/save';
       Data.post(control_link + url, form).then(function (result) {
           if (result.status_code == 200) {
               $scope.is_edit = false;
               $scope.callServer(tableStateRef);
               toaster.pop('success', "Berhasil", "Data berhasil tersimpan");
           } else {
               toaster.pop('error', "Terjadi Kesalahan", setErrorMessage(result.errors));
           }
       });
    };
    /** cancel */
    $scope.cancel = function () {
        $scope.callServer(tableStateRef);
        $scope.is_edit = false;
        $scope.is_view = false;
    };
    $scope.trash = function (row) {
        swal({
            title: "Peringatan ! ",
            text: "Apakah Anda Yakin Ingin Menghapus Data Ini",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Iya, di Hapus",
            cancelButtonText: "No",
            closeOnConfirm: false,
            closeOnCancel: false
        }, function (isConfirm) {
            if (isConfirm) {
                row.is_deleted = 1;
                // row.tanggal = new Date(row.jam);
                // row.jam = new Date(row.jam);
                row.date_start = new Date(row.date_start);
                row.date_end = new Date(row.date_end);
                Data.post(control_link + '/trash', row).then(function (result) {
                    $scope.displayed.splice($scope.displayed.indexOf(row), 1);
                });
                swal("Terhapus", "Data Berhasil Di Hapus.", "success");
            } else {
                swal("Membatalkan", "Membatalkan Menghapus Data", "error");
            }
        });
    };
    /** restore data from trash */
    // $scope.restore = function(row) {
    //     if (confirm("Apa anda yakin akan MERESTORE item ini ?")) {
    //         row.is_deleted = 0;
    //         Data.post(control_link + '/update', row).then(function(result) {
    //             $scope.displayed.splice($scope.displayed.indexOf(row), 1);
    //         });
    //     }
    // };
    $scope.restore = function (row) {
        swal({
            title: "Peringatan ! ",
            text: "Apakah Anda Yakin Ingin Restore Data Ini",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Iya, di Restore",
            cancelButtonText: "Batal",
            closeOnConfirm: false,
            closeOnCancel: false
        }, function (isConfirm) {
            if (isConfirm) {
                row.date_start = new Date(row.date_start);
                row.date_end = new Date(row.date_end);
                row.is_deleted = 0;
                Data.post(control_link + '/trash', row).then(function (result) {
                    $scope.displayed.splice($scope.displayed.indexOf(row), 1);
                });
                swal("Restore", "Data Berhasil Di Restore.", "success");
            } else {
                swal("Membatalkan", "Membatalkan Restore Data:)", "error");
            }
        });
    };
    /** delete data */
    // $scope.delete = function(row) {
    //     if (confirm("Apa anda yakin akan MENGHAPUS PERMANENT item ini ?")) {
    //         Data.delete(control_link + '/delete/' + row.id).then(function(result) {
    //             $scope.displayed.splice($scope.displayed.indexOf(row), 1);
    //         });
    //     }
    // };
    $scope.delete = function (row) {
        swal({
            title: "Peringatan",
            text: "Anda Akan Menghapus Ini",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ya,di hapus",
            cancelButtonText: "Tidak",
            closeOnConfirm: false,
            closeOnCancel: false
        }, function (isConfirm) {
            if (isConfirm) {
                Data.delete(control_link + '/delete/' + row.id).then(function (result) {
                    $scope.displayed.splice($scope.displayed.indexOf(row), 1);
                });
                swal("Terhapus", "Data terhapus.", "success");
            } else {
                swal("Error", "Data belum terhapus", "error");
            }
        });
    };
    /** checkAll */
    $scope.checkAll = function (module, valueCheck) {
        var akses = {
            "master_akses": false,
            "master_user": false,
        };
        angular.forEach(akses, function ($value, $key) {
            if ($key.indexOf(module) >= 0)
                $scope.form.akses[$key] = valueCheck;
        });
    };

    $scope.rekomendasi = function (row) {
        console.log(row.tanggal)
        var rekom = row.rekomendasi;
        if (rekom == 0) {
            row.rekomendasi = 1;
         } else{
            row.rekomendasi = 0;
         }
        Data.post(control_link + '/rekom', row).then(function (result) {
           if (result.status_code == 200) {
               $scope.callServer(tableStateRef);
               toaster.pop('success', "Berhasil", "Perubahan berhasil tersimpan");
           } else {
               toaster.pop('error', "Terjadi Kesalahan", setErrorMessage(result.errors));
           }
       });
       
       //  if (row.rekomendasi == 0) {
       //      var pesan = "Apakah Anda Yakin Ingin Merekomendasikan Artikel Ini?";

       //  }else{
       //      var pesan = "Apakah Anda Yakin  Tidak Ingin Merekomendasikan Artikel Ini?";
       //  }
       // swal({
       //      title: "Peringatan ! ",
       //      text: pesan,
       //      type: "warning",
       //      showCancelButton: true,
       //      confirmButtonColor: "#DD6B55",
       //      confirmButtonText: "Iya, di Restore",
       //      cancelButtonText: "Batal",
       //      closeOnConfirm: false,
       //      closeOnCancel: false
       //  }, function (isConfirm) {
       //      if (isConfirm) {
       //          row.is_deleted = 0;
       //          Data.post(control_link + '/update', row).then(function (result) {
       //              $scope.displayed.splice($scope.displayed.indexOf(row), 1);
       //          });
       //          swal("Restore", "Data Berhasil Di Restore.", "success");
       //      } else {
       //          swal("Membatalkan", "Membatalkan Restore Data:)", "error");
       //      }
       //  });
    };
});