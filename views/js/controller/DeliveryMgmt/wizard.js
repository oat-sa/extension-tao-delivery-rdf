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
    'lodash',
    'i18n',
    'core/dataProvider/request',
    'ui/feedback',
    'util/url',
    'tpl!taoDeliveryRdf/components/DeliveryMgmt/wizard',
    'css!taoDeliveryRdf/components/DeliveryMgmt/wizard'
], function (
    $,
    _,
    __,
    request,
    feedback,
    url,
    tpl
) {
    'use strict';

    return {
        start: function () {
            var route = url.route('getAvailableTests', 'DeliveryMgmt', 'taoDeliveryRdf');

            request(route, {}, 'get', {})
            .then(function (data) {
                var $document, $form, $wizard, $input, $dropdown, $dropdownSearch, $dropdownMenuItem;

                $document = $(document);
                $form = $('#simpleWizard');
                $wizard = $('.test-select-container')
                    .append(tpl(data))
                    .find('> .wizard');
                $input = $wizard.find('> .input');
                $dropdown = $wizard.find('> .dropdown');
                $dropdownSearch = $wizard.find('> .dropdown > .search > input');
                $dropdownMenuItem = $wizard.find('> .dropdown > .menu > .item');

                // Document event handlers
                function outsideWizardClickHandler(e) {
                    if (!$(e.target).closest($wizard).length) {
                        if ($dropdown.is(':visible')) {
                            $dropdown.hide();
                            $document.off('click', outsideWizardClickHandler);
                        }
                    }
                }

                // Form element events
                $form.on('submit', function (e) {
                    var value = $input.find('input').data('value');

                    if (!value) {
                        e.preventDefault();
                        feedback().error(__('Please select a test!'));
                        return false;
                    }

                    $form.find('#test').val(value);
                });

                // Wizard element events

                // Input element events
                $input
                .on('click', function () {
                    $dropdown.show();
                    $dropdownSearch.focus();
                    $document.on('click', outsideWizardClickHandler);
                });

                // Dropdown element events

                // Dropdown search element events
                $dropdownSearch
                .on('keyup', _.debounce(function (e) {
                    var $focused = $dropdownMenuItem.filter('.focused');
                    var $this = $(this);
                    var hasFocus = false;

                    if (e.key === 'Escape') {
                        $dropdown.hide();
                    }

                    if (e.key === 'Enter') {
                        if ($focused.length) {
                            $focused.first().trigger('click');
                        } else {
                            $dropdown.hide();
                        }
                    }

                    $dropdownMenuItem.removeClass('focused');
                    $dropdownMenuItem.each(function (i, item) {
                        var $item = $(item);
                        var haystack;
                        var needle;

                        haystack = $item.data('text').toUpperCase();
                        needle = $this.val().trim().toUpperCase();

                        if (!needle || haystack.includes(needle)) {
                            if (!hasFocus) {
                                hasFocus = true;
                                $item.addClass('focused');
                            }
                            $item.show();
                        } else {
                            $item.hide();
                        }
                    });

                    if ($dropdownMenuItem.is(':visible')) {
                        $dropdown.find('> .menu > .no-results').hide();
                    } else {
                        $dropdown.find('> .menu > .no-results').show();
                    }
                }, 100));

                // Dropdown menu item element events
                $dropdownMenuItem
                .on('click', function () {
                    var $this = $(this);

                    $input.find('input')
                    .val($this.data('text'))
                    .data('value', ($this.data('value')));

                    $dropdown.hide();
                })
                .on('hover', function () {
                    $dropdownMenuItem.removeClass('focused');
                    $(this).addClass('focused');
                });

            })
            .catch(function (err) {
                feedback().error(err);
            });
        }
    };
});
