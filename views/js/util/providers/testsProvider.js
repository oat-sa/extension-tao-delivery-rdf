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
    'i18n',
    'util/url'
], function ($, __, urlUtils) {
    'use strict';

    var providers = {
        /**
         * List available tests
         * @param {Object} data
         * @returns {Promise}
         */
        listTests(data) {
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

    return providers;

});
