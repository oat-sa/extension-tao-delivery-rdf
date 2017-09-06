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
 */

define([
    'jquery',
    'i18n',
    'core/dataProvider/request',
    'ui/feedback',
    'util/url',
    'ui/generis/widget/loader'
], function (
    $,
    __,
    request,
    feedback,
    url,
    generisWidgetLoader
) {
    'use strict';

    return {
        start: function () {
            var route = url.route('getAvailableTests', 'DeliveryMgmt', 'taoDeliveryRdf');

            request(route, {}, 'get', {})
            .then(function (data) {
                var $form = $('#simpleWizard');
                var factory = generisWidgetLoader('http://www.tao.lu/datatypes/WidgetDefinitions.rdf#ComboSearchBox');
                var widget;

                // Widget creation
                widget = factory({}, {
                    placeholder: __('Select the test you would like to publish'),
                    range: data.range,
                    uri: 'delivery-selector'
                })
                .render('.test-select-container');

                // Form element events
                $form.on('submit', function (e) {
                    var value = widget.get();

                    if (!value) {
                        e.preventDefault();
                        feedback().error(__('Please select a test!'));
                        return false;
                    }

                    $form.find('#test').val(value);
                });
            })
            .catch(function (err) {
                feedback().error(err);
            });
        }
    };
});
