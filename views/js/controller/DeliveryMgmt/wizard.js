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
define(['jquery', 'i18n', 'ui/filter', 'ui/feedback', 'util/url', 'core/promise'], function ($, __, filterFactory, feedback, urlUtils, Promise) {
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
        }
    };
});


