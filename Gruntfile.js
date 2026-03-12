"use strict";
module.exports = function (grunt) {
    grunt.file.defaultEncoding = "utf8";
    grunt.file.preserveBOM = false;

    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),

        uglify: {
            options: {
                banner:
                    "/*! <%= pkg.name %> - v<%= pkg.version %> - " +
                    "<%= grunt.template.today(\"yyyy-mm-dd\") %> */\n",
                report: "gzip",
                compress: {
                    drop_console: false,
                    drop_debugger: true,
                    dead_code: true,
                    unused: true
                },
                mangle: {
                    reserved: ["jQuery", "$", "window", "document"]
                },
                sourceMap: false,
                preserveComments: function(node, comment) {
                    return comment.value.match(/^!|@preserve|@license|@cc_on/i);
                }
            },
            admin: {
                files: {
                    "assets/js/admin.min.js": ["assets/js/admin.js"]
                }
            },
            apiLogAdmin: {
                files: {
                    "assets/js/api-log-admin.min.js": ["assets/js/api-log-admin.js"]
                }
            },
            settingsPage: {
                files: {
                    "assets/js/settings-page.min.js": ["assets/js/settings-page.js"]
                }
            },
            migration: {
                files: {
                    "assets/js/migration.min.js": ["assets/js/migration.js"]
                }
            }
        }
    });

    grunt.loadNpmTasks("grunt-contrib-uglify");

    grunt.registerTask("js", ["uglify"]);
    grunt.registerTask("default", ["uglify"]);
    grunt.registerTask("minify", ["uglify"]);
};
