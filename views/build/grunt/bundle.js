module.exports = function(grunt) {
    'use strict';

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};
    var uglify      = grunt.config('uglify') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out         = 'output';

    /**
     * Remove bundled and bundling files
     */
    clean.taodeliveryrdfbundle = [out];

    /**
     * Compile tao files into a bundle
     */
    requirejs.taodeliveryrdfbundle = {
        options: {
            baseUrl : '../js',
            dir : out,
            mainConfigFile : './config/requirejs.build.js',
            paths : { 'taoDeliveryRdf' : root + '/taoDeliveryRdf/views/js' },
            modules : [{
                name: 'taoDeliveryRdf/controller/routes',
                include : ext.getExtensionsControllers(['taoDeliveryRdf']),
                exclude : ['mathJax'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.taodeliveryrdfbundle = {
        files: [
            { src: [out + '/taoDeliveryRdf/controller/routes.js'],  dest: root + '/taoDeliveryRdf/views/js/controllers.min.js' },
            { src: [out + '/taoDeliveryRdf/controller/routes.js.map'],  dest: root + '/taoDeliveryRdf/views/js/controllers.min.js.map' }
        ]
    };

    grunt.config('clean', clean);
    grunt.config('requirejs', requirejs);
    grunt.config('uglify', uglify);
    grunt.config('copy', copy);

    // bundle task
    grunt.registerTask('taodeliveryrdfbundle', ['clean:taodeliveryrdfbundle', 'requirejs:taodeliveryrdfbundle', 'copy:taodeliveryrdfbundle']);
};
