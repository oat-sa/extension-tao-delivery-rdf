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
define([
    'jquery',
    'i18n',
    'taoDeliveryRdf/util/providers',
    'taoDeliveryRdf/util/forms/inputBehaviours'
], function ($, __, testProviders, inputBehaviours) {
    'use strict';

    return {
        start() {
            const $form = $('#simpleWizard');
            const $reportContainer = $form.closest('.content-block');
            const $filterContainer = $('.test-select-container');
            const $formElement = $('#test');

            // Replace submit button with taskQueue requester
            const taskButton = inputBehaviours.replaceSubmitWithTaskButton({
                $form,
                $reportContainer,
                buttonTitle: __('Publish the test'),
                buttonLabel: __('Publish')
            });

            // Enhanced selector input for tests:
            inputBehaviours.createSelectorInput({
                $filterContainer,
                $formElement,
                taskButton,
                dataProvider: {
                    list: testProviders.listTests
                },
                inputPlaceholder: __('Select the test you want to publish to the test-takers'),
                inputLabel: __('Select the test')
            });
        }
    };
});
