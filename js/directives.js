/** Export html to excel */
var saveExcel = (function() {
    var a = document.createElement("a");
    document.body.appendChild(a);
    a.style = "display: none";
    return function(data, fileName) {
        var blob = new Blob([data], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        });
        var url = window.URL.createObjectURL(blob);
        a.href = url;
        a.download = fileName;
        a.click();
        window.URL.revokeObjectURL(url);
    };
}());
/** End */
/* Html from JSON*/
angular.module('app').filter('thisHtml', ['$sce', function($sce) {
    return function(text) {
        return $sce.trustAsHtml(text);
    };
}]);
/** End */
/* Datetime to date object */
angular.module('app').filter("asDate", function() {
    return function(input) {
        if (input == "" || input == null) {
            return "";
        } else {
            return new Date(input);
        }
    }
});
/** End */
angular.module('customFilter', []).filter('filtercari', function() {
    return function(input, search) {
        if (!input) return input;
        if (!search) return input;
        var expected = ('' + search).toLowerCase();
        var result = {};
        angular.forEach(input, function(value, key) {
            var actual = ('' + value).toLowerCase();
            if (actual.indexOf(expected) !== -1) {
                result[key] = value;
            }
        });
        return result;
    }
});
/** range */
angular.module('app').filter('range', function() {
    return function(input, total) {
        total = parseInt(total);
        for (var i = 0; i < total; i++) {
            input.push(i);
        }
        return input;
    };
});
/** Read input type file */
angular.module('app').directive("fileread", [function() {
    return {
        scope: {
            fileread: "="
        },
        link: function(scope, element, attributes) {
            element.bind("change", function(changeEvent) {
                var reader = new FileReader();
                reader.onload = function(loadEvent) {
                    scope.$apply(function() {
                        scope.fileread = loadEvent.target.result;
                    });
                }
                reader.readAsDataURL(changeEvent.target.files[0]);
            });
        }
    }
}]);
/** End */
/** Pagination text */
angular.module('app').directive('pageSelect', function() {
    return {
        restrict: 'E',
        template: '<input type="text" class="select-page" ng-model="inputPage" ng-change="selectPage(inputPage)">',
        link: function(scope, element, attrs) {
            scope.$watch('currentPage', function(c) {
                scope.inputPage = c;
            });
        }
    }
});
/** End */
/** Directive print */
function printDirective() {
    function link(scope, element, attrs) {
        element.on('click', function() {
            var elemToPrint = document.getElementById(attrs.printElementId);
            if (elemToPrint) {
                printElement(elemToPrint);
            }
        });
    }

    function printElement(elem) {
        var popupWin = window.open('', '_blank', 'width=1000,height=700');
        popupWin.document.open()
        popupWin.document.write('<html><head><link rel="stylesheet" type="text/css" href="css/print.css" /></head><body onload="window.print();window.close();">' + elem.innerHTML + '</html>');
        popupWin.document.close();
    }
    return {
        link: link,
        restrict: 'A'
    };
}
angular.module('app').directive('ngPrint', [printDirective]);
/** End */
angular.module('app').config(['cfpLoadingBarProvider', function(cfpLoadingBarProvider) {
    cfpLoadingBarProvider.includeBar = true;
}])


app.directive('ngModel', function( $filter ) {
    return {
        require: '?ngModel',
        link: function(scope, elem, attr, ngModel) {
            if( !ngModel )
                return;
            if( attr.type !== 'time' )
                return;
                    
            ngModel.$formatters.unshift(function(value) {
                return value.replace(/:00\.000$/, '')
            });
        }
    }   
});     
/** CKeditor */
var app = angular.module('ngCkeditor', []);
var $defer, loaded = false;
app.run(['$q', '$timeout', function($q, $timeout) {
    $defer = $q.defer();
    if (angular.isUndefined(CKEDITOR)) {
        throw new Error('CKEDITOR not found');
    }
    CKEDITOR.disableAutoInline = true;

    function checkLoaded() {
        if (CKEDITOR.status == 'loaded') {
            loaded = true;
            $defer.resolve();
        } else {
            checkLoaded();
        }
    }
    CKEDITOR.on('loaded', checkLoaded);
    $timeout(checkLoaded, 100);
}]);
app.directive('ckeditor', ['$timeout', '$q', function($timeout, $q) {
    'use strict';
    return {
        restrict: 'AC',
        require: ['ngModel', '^?form'],
        scope: false,
        link: function(scope, element, attrs, ctrls) {
            var ngModel = ctrls[0];
            var form = ctrls[1] || null;
            var EMPTY_HTML = '<p></p>',
                isTextarea = element[0].tagName.toLowerCase() == 'textarea',
                data = [],
                isReady = false;
            if (!isTextarea) {
                element.attr('contenteditable', true);
            }
            var onLoad = function() {
                var options = {
                    toolbarGroups: [
                        // {name: 'clipboard', groups: ['clipboard', 'undo']},
                        // {name: 'editing', groups: ['find', 'selection', 'spellchecker']},
                        {
                            name: 'links'
                        }, {
                            name: 'insert'
                        },
                        // {name: 'forms'},
                        // {
                        //     name: 'tools'
                        // }, 
                        {
                            name: 'styles'
                        }, {
                            name: 'others'
                        }, {
                            name: 'basicstyles',
                            groups: ['basicstyles']
                        }, {
                            name: 'paragraph',
                            groups: ['list', 'indent', 'blocks', 'align', 'bidi']
                        }, {
                            name: 'colors'
                        }, {
                            name: 'document',
                            groups: ['mode']
                        },
                        // {name: 'about'}
                    ],
                    disableNativeSpellChecker: false,
                    uiColor: '#FAFAFA',
                    height: '500px',
                    width: '100%',
                    //filebrowserUploadUrl: "../api/appartikel/upload" //untuk upload web
                    filebrowserUploadUrl: "api/appartikel/upload" //untuk lokal
                };
                options = angular.extend(options, scope[attrs.ckeditor]);
                var instance = (isTextarea) ? CKEDITOR.replace(element[0], options) : CKEDITOR.inline(element[0], options),
                    configLoaderDef = $q.defer();
                element.bind('$destroy', function() {
                    instance.destroy(false //If the instance is replacing a DOM element, this parameter indicates whether or not to update the element with the instance contents.
                    );
                });
                var setModelData = function(setPristine) {
                        var data = instance.getData();
                        if (data == '') {
                            data = null;
                        }
                        $timeout(function() { // for key up event
                            (setPristine !== true || data != ngModel.$viewValue) && ngModel.$setViewValue(data);
                            (setPristine === true && form) && form.$setPristine();
                        }, 0);
                    },
                    onUpdateModelData = function(setPristine) {
                        if (!data.length) {
                            return;
                        }
                        var item = data.pop() || EMPTY_HTML;
                        isReady = false;
                        instance.setData(item, function() {
                            setModelData(setPristine);
                            isReady = true;
                        });
                    }
                instance.on('change', setModelData);
                instance.on('blur', setModelData);
                instance.on('instanceReady', function() {
                    scope.$broadcast("ckeditor.ready");
                    scope.$apply(function() {
                        onUpdateModelData(true);
                    });
                    instance.document.on("keyup", setModelData);
                });
                instance.on('customConfigLoaded', function() {
                    configLoaderDef.resolve();
                });
                ngModel.$render = function() {
                    data.push(ngModel.$viewValue);
                    if (isReady) {
                        onUpdateModelData();
                    }
                };
            };
            if (CKEDITOR.status == 'loaded') {
                loaded = true;
            }
            if (loaded) {
                onLoad();
            } else {
                $defer.promise.then(onLoad);
            }
        }
    };
}]);
/** end CKeditor */