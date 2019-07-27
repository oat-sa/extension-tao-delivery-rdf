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
    'jquery',
    'lodash',
    'i18n',
    'ui/filter',
    'ui/feedback',
    'layout/actions',
    'ui/taskQueue/taskQueue',
    'ui/taskQueueButton/standardButton'
], function ($, _, __, filterFactory, feedback, actionManager, taskQueue, taskCreationButtonFactory) {
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

    return {
        /**
         * Enhances a hidden form field, rendering a text input with filter, autocomplete and dropdown
         * @param {Object} options
         * @param {jQuery} options.$filterContainer
         * @param {jQuery} options.$formElement
         * @param {taskQueueButton} options.taskButton - button which submits the form
         * @param {Function} options.dataProvider - provider function which returns a Promise
         * @param {String} options.inputPlaceholder
         * @param {String} options.inputLabel
         * @returns {filter} component which manages the form input
         */
        createSelectorInput({
            $filterContainer,
            $inputElement,
            taskButton,
            dataProvider,
            inputPlaceholder = __('Select the test you want to publish to the test-takers'),
            inputLabel = __('Select the test')
        }) {
            return filterFactory($filterContainer, {
                placeholder: inputPlaceholder,
                label: inputLabel,
                width: '64%',
                quietMillis: 1000
            })
            .on('change', function(selection) {
                $inputElement.val(selection);
                if (selection) {
                    taskButton.enable();
                } else {
                    taskButton.disable();
                }
            })
            .on('request', function(params) {
                dataProvider
                    .list(params.data)
                    .then(function(data) {
                        params.success(data);
                    })
                    .catch(function(err) {
                        params.error(err);
                        feedback().error(err);
                    });
            })
            .render('<%- text %>');
        },

        /**
         * Replaces rendered submit input with a button that sends a task to taskQueue over AJAX
         * @param {Object} options
         * @param {jQuery} options.$form
         * @param {jQuery} options.$reportContainer
         * @param {Object} options.buttonTitle
         * @param {Object} options.buttonLabel
         * @returns {taskQueueButton}
         */
        replaceSubmitWithTaskButton({
            $form,
            $reportContainer,
            buttonTitle = __('Publish the test'),
            buttonLabel = __('Publish')
        }) {
            //find the old submitter
            const $oldSubmitter = $form.find('.form-submitter');
            //prepare the new component
            const taskCreationButton = taskCreationButtonFactory({
                type : 'info',
                icon : 'delivery',
                title : buttonTitle,
                label : buttonLabel,
                taskQueue : taskQueue,
                taskCreationUrl : $form.prop('action'),
                taskCreationData : function getTaskCreationData(){
                    return $form.serializeArray();
                },
                taskReportContainer : $reportContainer
            })
            .on('finished', function(result){
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
            })
            .on('continue', function(){
                refreshTree();
            })
            .on('error', function(err){
                //format and display error message to user
                feedback().error(err);
                this.trigger('finished');
            })
            .render($oldSubmitter.closest('.form-toolbar'))
            .disable();

            //replace the old submitter with the new one
            $oldSubmitter.replaceWith(taskCreationButton.getElement());

            return taskCreationButton;
        },

        /**
         * Set up the wizard form for publishing a TAO Local delivery
         * @param {jQuery} $form
         * @param {Object} providers - contains function(s) for fetching data
         */
        setupTaoLocalForm($form, providers) {
            const $reportContainer = $form.closest('.content-block');
            const $filterContainer = $('.test-select-container', $form);
            const $inputElement = $('#test', $form);

            // Replace submit button with taskQueue requester
            const taskButton = this.replaceSubmitWithTaskButton({
                $form,
                $reportContainer
            });

            // Enhanced selector input for tests:
            this.createSelectorInput({
                $filterContainer,
                $inputElement,
                taskButton,
                dataProvider: {
                    list: providers.listTests
                }
            });
        }
    };
});
