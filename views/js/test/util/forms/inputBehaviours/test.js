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
    'taoDeliveryRdf/util/providers',
    'taoDeliveryRdf/util/forms/inputBehaviours',
    'lib/jquery.mockjax/jquery.mockjax'
], function ($, testProviders, inputBehaviours) {
    'use strict';

    QUnit.module('inputBehaviours');

    QUnit.test('module', function(assert) {
        assert.expect(1);

        assert.equal(typeof inputBehaviours, 'object', 'The inputBehaviours module exposes an object');
    });

    var inputBehavioursApi = [
        { name: 'createSelectorInput' },
        { name: 'replaceSubmitWithTaskButton' },
        { name: 'setupTaoLocalForm' }
    ];

    QUnit.cases.init(inputBehavioursApi).test('instance API ', function(data, assert) {
        assert.expect(1);
        assert.equal(
            typeof inputBehaviours[data.name],
            'function',
            'The inputBehaviours instance exposes a "' + data.name + '" function'
        );
    });

    QUnit.test('provider dependency', function(assert) {
        assert.equal(typeof testProviders, 'object', 'Providers are loaded');
        assert.equal(typeof testProviders.listTests, 'function', 'Providers has a listTests function');
    });

    QUnit.test('createSelectorInput', function(assert) {
        const ready = assert.async();

        // mocks
        const mockTaskButton = {
            isEnabled: false,
            enable: () => { mockTaskButton.isEnabled = true; },
            disable: () => { mockTaskButton.isEnabled = false; }
        };
        const reqParamsData = {q: 'myQuery', page: 1 };
        $.mockjax({
            url: /taoDeliveryRdf\/DeliveryMgmt\/getAvailableTests/,
            status: 200,
            dataType: 'json',
            contentType: 'application/json',
            data: json => {
                assert.deepEqual(json, reqParamsData, 'Sent params received at configured url');
                ready();
                return true;
            },
            responseText: { success: true, ans: 42 }
        });

        const $filterContainer = $('.test-select-container');
        const $inputElement = $('#test');
        const options = {
            $filterContainer: $filterContainer,
            $inputElement: $inputElement,
            taskButton: mockTaskButton,
            dataProvider: {
                list: testProviders.listTests
            },
            inputPlaceholder: 'Placeholder',
            inputLabel: 'Label'

        };
        const filter = inputBehaviours.createSelectorInput(options);

        assert.equal(typeof filter, 'object', 'A filter is returned'); // improve
        // rendering
        const $select2Label = $('label.form_desc', $filterContainer);
        const $select2Container = $('.select2-container', $filterContainer);
        const $select2Offscreen = $('.select2-offscreen', $filterContainer);
        assert.equal($select2Label.length, 1, 'A label is rendered');
        assert.equal($select2Container.length, 1, 'A container is created');
        assert.ok($select2Offscreen.length >= 1, 'Offscreen element(s) is created');
        assert.equal($select2Label.text(), 'Label', 'The label is correct');
        assert.ok($(':contains(Placeholder)', $select2Container).length >= 1, 'The correct placeholder is inside');
        // on change
        filter.trigger('change', 'newValue');
        assert.equal($inputElement.val(), 'newValue', 'The hidden input was updated with the selected value');
        assert.equal(mockTaskButton.isEnabled, true, 'The task button got enabled');
        filter.trigger('change', '');
        assert.equal($inputElement.val(), '', 'The hidden input was updated with the selected value');
        assert.equal(mockTaskButton.isEnabled, false, 'The task button got disabled');
        // on request (mockjax)
        const reqParams = {
            data: reqParamsData,
            success: () => {},
            error: () => {}
        };
        filter.trigger('request', reqParams);
    });

    QUnit.test('replaceSubmitWithTaskButton', function(assert) {
        const options = {};
        const taskButton = inputBehaviours.replaceSubmitWithTaskButton(options);

        assert.ok(true);
    });

    QUnit.test('setupTaoLocalForm', function(assert) {
        const $form = $('#simpleWizard');
        inputBehaviours.setupTaoLocalForm($form, testProviders);

        assert.ok(true);
    });

});
