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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
/**
 * @author Martin Nicholson <martin@taotesting.com>
 */
define([
    'lodash',
    'i18n',
    'ui/filter',
    'ui/feedback',
    'layout/actions',
    'taoDeliveryRdf/util/providers',
    'ui/taskQueue/taskQueue',
    'ui/taskQueueButton/standardButton'
], function (_, __, filterFactory, feedback, actionManager, providers, taskQueue, taskCreationButtonFactory) {
    'use strict';

    /**
     * wrapped the old jstree API used to refresh the tree and optionally select a resource
     * @param {String} [uriResource] - the uri resource node to be selected
     */
    const refreshTree = function refreshTree(uriResource){
        actionManager.trigger('refresh', {
            uri : uriResource
        });
    };

    let taskCreationButton; // inject it?

    return {
        /**
         * Enhances a hidden form field, rendering a text input with filter, autocomplete and dropdown
         * @param {jQuery} $filterContainer
         * @param {jQuery} $formElement
         */
        createTestSelector($filterContainer, $formElement) {
            filterFactory($filterContainer, {
                placeholder: __('Select the test you want to publish to the test-takers'),
                width: '64%',
                quietMillis: 1000,
                label: __('Select the test')
            }).on('change', function (test) {
                $formElement.val(test);
                if(test){
                    taskCreationButton.enable();
                }else{
                    taskCreationButton.disable();
                }
            }).on('request', function (params) {
                providers
                    .list(params.data)
                    .then(function (tests) {
                        params.success(tests);
                    })
                    .catch(function (err) {
                        params.error(err);
                        feedback().error(err);
                    });
            }).render('<%- text %>');
        },

        /**
         * Replaces rendered submit input with a button that sends a task to taskQueue over AJAX
         * @param {jQuery} $form
         * @param {jQuery} $reportContainer
         */
        replaceSubmitWithTaskButton($form, $reportContainer) {
            //find the old submitter and replace it with the new component
            var $oldSubmitter = $form.find('.form-submitter');
            taskCreationButton = taskCreationButtonFactory({
                type : 'info',
                icon : 'delivery',
                title : __('Publish the test'),
                label : __('Publish'),
                taskQueue : taskQueue,
                taskCreationUrl : $form.prop('action'),
                taskCreationData : function getTaskCreationData(){
                    return $form.serializeArray();
                },
                taskReportContainer : $reportContainer
            }).on('finished', function(result){
                if (result.task
                    && result.task.report
                    && _.isArray(result.task.report.children)
                    && result.task.report.children.length
                    && result.task.report.children[0]) {
                    if(result.task.report.children[0].data
                        && result.task.report.children[0].data.uriResource){
                        feedback().info(__('%s completed', result.task.taskLabel));
                        refreshTree(result.task.report.children[0].data.uriResource);
                    }else{
                        this.displayReport(result.task.report.children[0], __('Error'));
                    }
                }
            }).on('continue', function(){
                refreshTree();
            }).on('error', function(err){
                //format and display error message to user
                feedback().error(err);
            }).render($oldSubmitter.closest('.form-toolbar')).disable();

            //replace the old submitter with the new one and apply its style
            $oldSubmitter.replaceWith(taskCreationButton.getElement().css({float: 'right'}));
        }
    };
});
