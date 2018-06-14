module.exports = function(grunt) {

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoDeliveryRdf/views/';

    sass.taodeliveryrdf = { };
    sass.taodeliveryrdf.files = { };
    sass.taodeliveryrdf.files[root + 'css/selector.css'] = root + 'scss/selector.scss';

    watch.taodeliveryrdfsass = {
        files : [root + 'scss/**/*.scss'],
        tasks : ['sass:taodeliveryrdf', 'notify:taodeliveryrdfsass'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taodeliveryrdfsass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taodeliveryrdfsass', ['sass:taodeliveryrdf']);
};
