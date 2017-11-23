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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
define(['lodash', 'jquery', 'i18n', 'ui/filter', 'ui/feedback', 'util/url', 'core/promise', 'ui/report', 'taoTaskQueue/model/taskQueue', 'layout/loading-bar'], function (_, $, __, filterFactory, feedback, urlUtils, Promise, report, taskQueue, loadingBar) {
    'use strict';

    var provider = {

        /**
         * List available tests
         * @returns {Promise}
         */
        list: function list(data) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: urlUtils.route('getAvailableTests', 'DeliveryMgmt', 'taoDeliveryRdf'),
                    data: {
                        q: data.q,
                        page: data.page
                    },
                    type: 'GET',
                    dataType: 'JSON'
                }).done(function (tests) {
                    if (tests) {
                        resolve(tests);
                    } else {
                        reject(new Error(__('Unable to load tests')));
                    }
                }).fail(function () {
                    reject(new Error(__('Unable to load tests')));
                });
            });
        }
    };

    return {
        start: function () {
            var $filterContainer = $('.test-select-container');
            var $formElement = $('#test');
            var $form = $('#simpleWizard');
            var $container = $form.closest('.content-block');

            filterFactory($filterContainer, {
                placeholder: __('Select the test you want to publish to the test-takers'),
                width: '64%',
                quietMillis: 1000,
                label: __('Select the test')
            }).on('change', function (test) {
                $formElement.val(test);
            }).on('request', function (params) {
                provider
                    .list(params.data)
                    .then(function (tests) {
                        params.success(tests);
                    })
                    .catch(function (err) {
                        params.error(err);
                        feedback().error(err);
                    });
            }).render('<%= text %>');

            $form.on('submit', function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                loadingBar.start();

                //pause polling all status during creation process to prevent concurrency issue
                taskQueue.pollAllStop();
                taskQueue.create($form.prop('action'), $form.serializeArray()).then(function(result){
                    var task = result.task;
                    if(result.finished){
                        //finished quickly display report
                        report({replace:true}, task.report.children[0]).render($container);
                        taskQueue.archive(task.id).then(function(){
                            taskQueue.pollAll();
                        });
                    }else{
                        //move this to the queue
                        report({replace:true}, {
                            type: 'info',
                            message : '<strong>"'+task.taskLabel+'"</strong> takes a long time to execute so it has been moved to the background.'
                        }).render($container);

                        _.delay(function(){
                            taskQueue.trigger('taskcreated', task);
                            taskQueue.pollAll(true);
                        }, 600);
                    }
                    loadingBar.stop();
                }).catch(function(err){
                    taskQueue.pollAll();
                    feedback().error(err);
                });
            });
        }
    };
});


