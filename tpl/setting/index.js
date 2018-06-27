app.controller('settingCtrl', function($scope, Data, toaster, $rootScope) {
    Data.get('m_setting/view').then(function(result) {
        console.log(result.data)
        
        $scope.form = result.data;
    });
    $scope.save = function(form) {
        Data.post('m_setting/update', form).then(function(result) {
            if (result.status_code == 200) {
                $scope.is_edit = false;
                toaster.pop('success', "Berhasil", "Data berhasil tersimpan");
            } else {
                toaster.pop('error', "Terjadi Kesalahan", setErrorMessage(result.errors));
            }
        });
    };
})