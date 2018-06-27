app.controller('infokajianCtrl', function ($scope, Data, toaster) {
    var tableStateRef;
    var control_link = "appinfokajian";
    $scope.displayed = [];
    $scope.listkategori = [];
    $scope.form = {};
    $scope.is_edit = false;
    $scope.is_view = false;
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
            console.log(response.data.lists)
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
        });
    };
    
    Data.get('appkategori/index').then(function (data) {
        $scope.listkategori = data.data.list;
    });

    $scope.opened = {};
    $scope.toggle = function ($event, elemId) {
        $event.preventDefault();
        $event.stopPropagation();
        $scope.opened[elemId] = !$scope.opened[elemId];
    };
    $scope.formatDate = function(date) {
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var strTime = hours + ':' + minutes;
        $scope.form.jam = new Date(date.getMonth() + 1 + "/" + date.getDate() + "/" + date.getFullYear() + "  " + strTime);
        console.log($scope.form.jam);
    }
    /** create action */
    $scope.create = function (form) {
        var date = new Date();
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.formtitle = "Form Tambah Data";
        $scope.form = {};
        $scope.form.tanggal = date;
        $scope.formatDate(date);
        $scope.form.publish = '1';
    };
    // urlParsing js
   $scope.UrlParse = function(value) {
        var words = value.split(" ");
        if (words.length <= 100) {
            var char = value.replace(/[^a-z0-9 ]/gi, '').toLowerCase();
            $scope.form.alias = char.replace(/[ ]/gi, '-').toLowerCase();
        } else {
            var kata = words[0] + " " + words[1] + " " + words[2] + " " + words[3] + " " + words[4] + " " + words[5];
            var char = kata.replace(/[^a-z0-9 ]/gi, '').toLowerCase();
            $scope.form.alias = char.replace(/[ ]/gi, '-').toLowerCase();
        }
    };
    $scope.keword = function(value) {
        var words = value.split(" ");
        $scope.form.keyword = value.replace(/[^a-z0-9]/gi, ', ').toLowerCase();
    };
    $scope.countchar = function(value) {
        var words = 0;
        if (value) {
            words = value.length;
        }
        $scope.getchar = words;
    }
    $scope.countchar();
    $scope.get_koma = function(value) {
        $scope.form.keyword = value.replace(/\n/g, ", ").toLowerCase();
    };
    /** update action */
    $scope.update = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.formtitle = "Edit Data : " + form.judul;
        $scope.form = form;
        $scope.form.tanggal = new Date(form.tanggal);
        $scope.form.jam = new Date(form.jam);
        $scope.form.is_publish = parseInt(form.is_publish);
        console.log(form.content)
    };
    /** view data */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.formtitle = "Lihat Data : " + form.judul;
        $scope.form = form;
        var jam = form.jam * 1000;
        $scope.form.tanggal = new Date(jam);
        $scope.form.jam = new Date(jam);
        $scope.form.is_publish = parseInt(form.is_publish);
    };
    /** save request */
    $scope.save = function (form) {
        console.log(form);
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
                Data.post(control_link + '/update', row).then(function (result) {
                    $scope.displayed.splice($scope.displayed.indexOf(row), 1);
                });
                swal("Terhapus", "Data Berhasil Di Hapus.", "success");
            } else {
                swal("Membatalkan", "Membatalkan Menghapus Data:)", "error");
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
                row.is_deleted = 0;
                Data.post(control_link + '/update', row).then(function (result) {
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
})