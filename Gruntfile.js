/* jshint node:true */
module.exports = function (grunt) {
    'use strict';

    grunt.initConfig({

        // Setting folder templates.
        dirs: {
            admin_css: 'assets/admin/css',
            admin_js: 'assets/admin/js',
            frontend_css: 'assets/frontend/css',
            frontend_js: 'assets/frontend/js'
        },

        // JavaScript linting with JSHint.
        jshint: {
            options: {
                jshintrc: '.jshintrc'
            },
            all: [
                'Gruntfile.js',
                '<%= dirs.admin_js %>/*.js',
                '!<%= dirs.admin_js %>/*.min.js',
                '<%= dirs.frontend_js %>/*.js',
                '!<%= dirs.frontend_js %>/*.min.js'
            ]
        },

        // Sass linting with Stylelint.
        stylelint: {
            options: {
                configFile: '.stylelintrc'
            },
            all: [
                '<%= dirs.frontend_css %>/*.scss',
                '<%= dirs.admin_css %>/*.scss'
            ]
        },

        // Minify .js files.
        uglify: {
            options: {
                ie8: true,
                parse: {
                    strict: false
                },
                output: {
                    comments: /@license|@preserve|^!/
                }
            },
            admin: {
                files: [{
                        expand: true,
                        cwd: '<%= dirs.admin_js %>/',
                        src: [
                            '*.js',
                            '!*.min.js'
                        ],
                        dest: '<%= dirs.admin_js %>/',
                        ext: '.min.js'
                    }]
            },
            frontend: {
                files: [{
                        expand: true,
                        cwd: '<%= dirs.frontend_js %>/',
                        src: [
                            '*.js',
                            '!*.min.js'
                        ],
                        dest: '<%= dirs.frontend_js %>/',
                        ext: '.min.js'
                    }]
            }
        },

        // Compile all .scss files.
        sass: {
            compile: {
                options: {
                    sourceMap: 'none'
                },
                files: [{
                        expand: true,
                        cwd: '<%= dirs.admin_css %>/',
                        src: ['*.scss'],
                        dest: '<%= dirs.admin_css %>/',
                        ext: '.css'
                    },
                    {
                        expand: true,
                        cwd: '<%= dirs.frontend_css %>/',
                        src: ['*.scss'],
                        dest: '<%= dirs.frontend_css %>/',
                        ext: '.css'
                    }
                ]
            }
        },

        // Generate RTL .css files
        rtlcss: {
            admin: {
                expand: true,
                cwd: '<%= dirs.admin_css %>',
                src: [
                    '*.css',
                    '!*-rtl.css'
                ],
                dest: '<%= dirs.admin_css %>/',
                ext: '-rtl.css'
            },
            frontend: {
                expand: true,
                cwd: '<%= dirs.frontend_css %>',
                src: [
                    '*.css',
                    '!*-rtl.css'
                ],
                dest: '<%= dirs.frontend_css %>/',
                ext: '-rtl.css'
            }
        },

        // Minify all .css files.
        cssmin: {
            target: {
                files: [{
                        expand: true,
                        cwd: '<%= dirs.admin_css %>/',
                        src: ['*.css', '!*-rtl.css', '!*.min.css'],
                        dest: '<%= dirs.admin_css %>/',
                        ext: '.min.css'
                    },
                    {
                        expand: true,
                        cwd: '<%= dirs.frontend_css %>/',
                        src: ['*.css', '!lib/.*', '!*-rtl.css', '!*.min.css'],
                        dest: '<%= dirs.frontend_css %>/',
                        ext: '.min.css'
                    }
                ]
            }
        },

        // Watch changes for assets.
        watch: {
            admin_css: {
                files: ['<%= dirs.admin_css %>/*.scss'],
                tasks: ['sass', 'rtlcss', 'cssmin']
            },
            frontend_css: {
                files: ['<%= dirs.frontend_css %>/*.scss'],
                tasks: ['sass', 'rtlcss', 'cssmin']
            },
            js: {
                files: [
                    '<%= dirs.admin_js %>/*js',
                    '<%= dirs.frontend_js %>/*js',
                    '!<%= dirs.admin_js %>/*.min.js',
                    '!<%= dirs.frontend_js %>/*.min.js'
                ],
                tasks: ['uglify']
            }
        },

        // Generate POT files.
        makepot: {
            options: {
                type: 'wp-plugin',
                domainPath: 'languages',
                potHeaders: {
                    'report-msgid-bugs-to': 'https://github.com/dualcube/dc-woocommerce-multi-vendor/issues',
                    'language-team': 'WC Marketplace <support@wc-marketplace.com>',
                    'last-translator': 'WC Marketplace<support@wc-marketplace.com>'
                }
            },
            dist: {
                options: {
                    potFilename: 'dc-woocommerce-multi-vendor.pot',
                    exclude: [
                        'tmp/.*'
                    ]
                }
            }
        },

        // Check textdomain errors.
        checktextdomain: {
            options: {
                text_domain: 'dc-woocommerce-multi-vendor',
                keywords: [
                    '__:1,2d',
                    '_e:1,2d',
                    '_x:1,2c,3d',
                    'esc_html__:1,2d',
                    'esc_html_e:1,2d',
                    'esc_html_x:1,2c,3d',
                    'esc_attr__:1,2d',
                    'esc_attr_e:1,2d',
                    'esc_attr_x:1,2c,3d',
                    '_ex:1,2c,3d',
                    '_n:1,2,4d',
                    '_nx:1,2,4c,5d',
                    '_n_noop:1,2,3d',
                    '_nx_noop:1,2,3c,4d'
                ]
            },
            files: {
                src: [
                    '**/*.php', // Include all files
                    '!node_modules/**', // Exclude node_module
                    '!tmp/**'                 // Exclude tmp/
                ],
                expand: true
            }
        },

        // Autoprefixer.
        postcss: {
            options: {
                processors: [
                    require('autoprefixer')({
                        browsers: [
                            '> 0.1%',
                            'ie 8',
                            'ie 9'
                        ]
                    })
                ]
            },
            dist: {
                src: [
                    '<%= dirs.admin_css %>/*.css',
                    '<%= dirs.frontend_css %>/*.css'
                ]
            }
        }
    });

    // Load NPM tasks to be used here
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-rtlcss');
    grunt.loadNpmTasks('grunt-postcss');
    grunt.loadNpmTasks('grunt-stylelint');
    grunt.loadNpmTasks('grunt-wp-i18n');
    grunt.loadNpmTasks('grunt-checktextdomain');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // Register tasks
    grunt.registerTask('default', [
        'js',
        'css',
        // 'i18n'
    ]);

    grunt.registerTask('js', [
        //'jshint',
        'uglify:admin',
        'uglify:frontend'
    ]);

    grunt.registerTask('css', [
        'sass',
        'cssmin',
        'postcss',
        // 'rtlcss',
    ]);

    // Only an alias to 'default' task.
    grunt.registerTask('dev', [
        'default'
    ]);

    grunt.registerTask('i18n', [
        'checktextdomain',
        'makepot'
    ]);
};