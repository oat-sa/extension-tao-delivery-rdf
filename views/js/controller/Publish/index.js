/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
define([
    'jquery',
    'lodash',
    'i18n',
    'util/url',
    'layout/actions',
    'provider/resources',
    'ui/destination/selector',
    'ui/feedback',
    'core/taskQueue/taskQueue'
], function ($, _, __, urlHelper, actionManager, resourceProviderFactory, destinationSelectorFactory, feedback, taskQueue) {
    'use strict';

    /**
     * wrapped the old jstree API used to refresh the tree and optionally select a resource
     * @param {String} [uriResource] - the uri resource node to be selected
     */
    var refreshTree = function refreshTree(uriResource){
        actionManager.trigger('refresh', {
            uri : uriResource
        });
    };

    return {
        start : function start() {

            var $container = $('.selector-container');
            var testUri = $container.data('test');
            var testLabel = $container.data('label');

            //get the resource provider configured with the action URL
            var resourceProvider = resourceProviderFactory();

            //set up a destination selector
            destinationSelectorFactory($container, {
                title : __('Publish "%s" to', testLabel),
                actionName : __('Publish'),
                icon : 'delivery',
                taskQueue : taskQueue,
                taskCreationData : {testUri : testUri},
                taskCreationUrl : urlHelper.route('publish', 'Publish', 'taoDeliveryRdf'),
                classUri: $container.data('root-class')
            })
            .on('query', function(params) {
                var self = this;

                //asks only classes
                params.classOnly = true;
                resourceProvider
                    .getResources(params, true)
                    .then(function(resources){
                        //ask the server the resources from the component query
                        self.update(resources, params);
                    })
                    .catch(function(err){
                        self.trigger('error', err);
                    });
            })
            .on('finished', function (result, button) {
                if (result.task
                    && result.task.report
                    && _.isArray(result.task.report.children)
                    && result.task.report.children.length
                    && result.task.report.children[0]) {
                    if (result.task.report.children[0].data
                        && result.task.report.children[0].data.uriResource) {
                        feedback().info(__('%s completed', result.task.taskLabel));

                        //TODO: redirect to the newly compiled delivery when TAO-5746 is merged
                        refreshTree(result.task.report.children[0].data.uriResource);
                    } else {
                        button.displayReport(result.task.report.children[0], __('Error'));
                    }
                }
            })
            .on('continue', function(){
                refreshTree(testUri);
            })
            .on('error', function(err){
                feedback().error(err);
            });
        }
    };
});