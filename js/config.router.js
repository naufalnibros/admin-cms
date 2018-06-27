angular.module('app').run(
    ['$rootScope', '$state', '$stateParams', 'Data', '$transitions',
        function($rootScope, $state, $stateParams, Data, $transitions) {
            $rootScope.$state = $state;
            $rootScope.$stateParams = $stateParams;
            /** Pengecekan login */
            $transitions.onStart({}, function($transition$) {
                var toState = $transition$.$to();
                Data.get('site/session').then(function(results) {
                    if (results.status_code == 200) {
                        $rootScope.user = results.data.user;
                        console.log($rootScope.user)
                        /** Check hak akses */
                        var globalmenu = ['site.dashboard', 'master.userprofile', 'access.signin', 'laporan.l_artikel'];
                        if (globalmenu.indexOf(toState.name) >= 0) {} else {
                            if (results.data.user.akses[(toState.name).replace(".", "_")]) {} else {
                                $state.go("access.forbidden");
                            }
                        }
                        /** End */
                    } else {
                        $state.go("access.signin");
                    }
                });

                Data.get('site/domain').then(function(results) {
                    if (results.status_code == 200) {
                        $rootScope.domain = results.data.url;
                    } else {
                        $rootScope.domain = 'localhost';
                    }
                });

            });
        }
    ]);
angular.module('app').config(function($httpProvider) {
    $httpProvider.interceptors.push(function($q, $rootScope) {
        var numberOfHttpRequests = 0;
        return {
            request: function(config) {
                numberOfHttpRequests += 1;
                $rootScope.waitingForHttp = true;
                return config;
            },
            requestError: function(error) {
                numberOfHttpRequests -= 1;
                $rootScope.waitingForHttp = (numberOfHttpRequests !== 0);
                return $q.reject(error);
            },
            response: function(response) {
                numberOfHttpRequests -= 1;
                $rootScope.waitingForHttp = (numberOfHttpRequests !== 0);
                return response;
            },
            responseError: function(error) {
                numberOfHttpRequests -= 1;
                $rootScope.waitingForHttp = (numberOfHttpRequests !== 0);
                return $q.reject(error);
            }
        };
    });
});
angular.module('app').config(
    ['$stateProvider', '$urlRouterProvider',
        function($stateProvider, $urlRouterProvider) {
            $urlRouterProvider.otherwise('/site/dashboard');
            $stateProvider.state('site', {
                    abstract: true,
                    url: '/site',
                    templateUrl: 'tpl/app.html'
                }).state('site.dashboard', {
                    url: '/dashboard',
                    templateUrl: 'tpl/dashboard.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load(['chart.js']).then(function() {
                                    return $ocLazyLoad.load('tpl/site/dashboard.js');
                                });
                            }
                        ]
                    }
                })
                /** Set default page */
                .state('access', {
                    url: '/access',
                    template: '<div ui-view class="fade-in-right-big smooth"></div>'
                }).state('access.signin', {
                    url: '/signin',
                    templateUrl: 'tpl/page_signin.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load('tpl/site/site.js').then();
                            }
                        ]
                    }
                }).state('access.404', {
                    url: '/404',
                    templateUrl: 'tpl/page_404.html'
                }).state('access.forbidden', {
                    url: '/forbidden',
                    templateUrl: 'tpl/page_forbidden.html'
                })
                /** End */
                /** Router request master */
                .state('master', {
                    url: '/master',
                    templateUrl: 'tpl/app.html'
                }).state('master.userprofile', {
                    url: '/profile',
                    templateUrl: 'tpl/m_user/profil.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load('tpl/m_user/profil.js');
                            }
                        ]
                    }
                }).state('master.setting', {
                    url: '/setting',
                    templateUrl: 'tpl/setting/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load(['naif.base64']).then(function() {
                                    return $ocLazyLoad.load('tpl/setting/index.js');
                                })
                            }
                        ]
                    }
                }).state('master.user', {
                    url: '/user',
                    templateUrl: 'tpl/m_user/user.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load('tpl/m_user/user.js');
                            }
                        ]
                    }
                }).state('master.roles', {
                    url: '/roles',
                    templateUrl: 'tpl/m_roles/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load('tpl/m_roles/roles.js');
                            }
                        ]
                    }
                }).state('master.artikel', {
                    url: '/artikel',
                    templateUrl: 'tpl/artikel/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                // return $ocLazyLoad.load(['']).then(function () {
                                return $ocLazyLoad.load('tpl/artikel/artikel.js');
                                // });
                            }
                        ]
                    }
                }).state('master.kiriman', {
                    url: '/kiriman',
                    templateUrl: 'tpl/kiriman/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['naif.base64']).then(function () {
                                    return $ocLazyLoad.load('tpl/kiriman/kiriman.js');
                                });
                            }
                        ]
                    }
                }).state('master.halaman', {
                    url: '/halaman',
                    templateUrl: 'tpl/m_halaman/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                // return $ocLazyLoad.load(['']).then(function () {
                                return $ocLazyLoad.load('tpl/m_halaman/index.js');
                                // });
                            }
                        ]
                    }
                }).
            state('master.kategori', {
                    url: '/kategori',
                    templateUrl: 'tpl/m_kategori/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load(['naif.base64']).then(function() {
                                    return $ocLazyLoad.load('tpl/m_kategori/kategori.js');
                                });
                            }
                        ]
                    }
                }).state('master.banner', {
                    url: '/banner',
                    templateUrl: 'tpl/m_banner/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load(['naif.base64']).then(function() {
                                    return $ocLazyLoad.load('tpl/m_banner/index.js');
                                });
                            }
                        ]
                    }
                }).state('master.infokajian', {
                    url: '/infokajian',
                    templateUrl: 'tpl/m_infokajian/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load('tpl/m_infokajian/infokajian.js');
                            }
                        ]
                    }
                }).state('master.produk', {
                    url: '/produk',
                    templateUrl: 'tpl/m_produk/produk.html',
                    resolve: {
                      deps: ['$ocLazyLoad',
                        function($ocLazyLoad) {
                          return $ocLazyLoad.load(['naif.base64']).then(function() {
                            return $ocLazyLoad.load('tpl/m_produk/index.js');
                          });
                        }
                      ]
                    }
                })
                 .state('master.topnews', {
                    url: '/topnews',
                    templateUrl: 'tpl/m_topnews/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load('tpl/m_topnews/index.js');
                            }
                        ]
                    }
                })
                // .state('master.galeri', {
                //     url: '/galeri',
                //     templateUrl: 'tpl/m_galeri/index.html',
                //     resolve: {
                //         deps: ['$ocLazyLoad',
                //             function ($ocLazyLoad) {
                //                 return $ocLazyLoad.load('tpl/m_galeri/galeri.js');
                //             }
                //         ]
                //     }
                // })
                // .state('master.sosmed', {
                //     url: '/sosmed',
                //     templateUrl: 'tpl/m_sosmed/index.html',
                //     resolve: {
                //         deps: ['$ocLazyLoad',
                //             function ($ocLazyLoad) {
                //                 return $ocLazyLoad.load('tpl/m_sosmed/index.js');
                //             }
                //         ]
                //     }
                // })
                .state('master.penggaturan', {
                    url: '/penggaturan',
                    templateUrl: 'tpl/m_penggaturan/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function($ocLazyLoad) {
                                return $ocLazyLoad.load('tpl/m_penggaturan/index.js');
                            }
                        ]
                    }
                })
                /** End master request */
                   /** Router laporan */
                .state('laporan', {
                    url: '/laporan',
                    templateUrl: 'tpl/app.html'
                }).state('laporan.artikel', {
                    url: '/laporan-artikel',
                    templateUrl: 'tpl/l_artikel/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('tpl/l_artikel/l_artikel.js');
                            }
                        ]
                    }
                }).state('laporan.view', {
                    url: '/laporan-view',
                    templateUrl: 'tpl/l_view/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('tpl/l_view/l_view.js');
                            }
                        ]
                    }
                })
        }
    ]);
