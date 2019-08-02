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
    'taoDeliveryRdf/util/providers/testsProvider',
    'taoDeliveryRdf/util/forms/deliveryFormHelper',
    'lib/jquery.mockjax/jquery.mockjax'
], function ($, testsProvider, deliveryFormHelper) {
    'use strict';

    QUnit.module('deliveryFormHelper');

    QUnit.test('module', function(assert) {
        assert.expect(1);

        assert.equal(typeof deliveryFormHelper, 'object', 'The deliveryFormHelper module exposes an object');
    });

    var deliveryFormHelperApi = [
        { name: 'createSelectorInput' },
        { name: 'replaceSubmitWithTaskButton' },
        { name: 'setupTaoLocalForm' }
    ];

    QUnit.cases.init(deliveryFormHelperApi).test('instance API ', function(data, assert) {
        assert.expect(1);
        assert.equal(
            typeof deliveryFormHelper[data.name],
            'function',
            'The deliveryFormHelper instance exposes a "' + data.name + '" function'
        );
    });

    QUnit.test('testsProvider dependency', function(assert) {
        assert.equal(typeof testsProvider, 'object', 'Provider is loaded');
        assert.equal(typeof testsProvider.listTests, 'function', 'Provider has a listTests function');
    });

    QUnit.test('createSelectorInput', function(assert) {
        var ready = assert.async();

        // mocks
        var mockTaskButton = {
            isEnabled: false,
            enable: function() { mockTaskButton.isEnabled = true; },
            disable: function() { mockTaskButton.isEnabled = false; }
        };
        var $filterContainer = $('.test-select-container');
        var $inputElement = $('#test');
        var options = {
            $filterContainer: $filterContainer,
            $inputElement: $inputElement,
            taskButton: mockTaskButton,
            dataProvider: {
                list: testsProvider.listTests
            },
            inputPlaceholder: 'Placeholder',
            inputLabel: 'Label'

        };
        var filter,
            $select2Label,
            $select2Container,
            $select2Offscreen;

        var reqParamsData = {q: 'myQuery', page: 1 };
        $.mockjax({
            url: /taoDeliveryRdf\/DeliveryMgmt\/getAvailableTests/,
            status: 200,
            dataType: 'json',
            contentType: 'application/json',
            data: function(json) {
                assert.deepEqual(json, reqParamsData, 'Sent params received at configured url');
                ready();
                return true;
            },
            responseText: { success: true, ans: 42 }
        });

        filter = deliveryFormHelper.createSelectorInput(options);

        assert.equal(typeof filter, 'object', 'A filter is returned'); // improve
        // rendering
        $select2Label = $('label.form_desc', $filterContainer);
        $select2Container = $('.select2-container', $filterContainer);
        $select2Offscreen = $('.select2-offscreen', $filterContainer);
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
        filter.trigger('request', {
            data: reqParamsData,
            success: function() {},
            error: function() {}
        });
    });

    QUnit.test('replaceSubmitWithTaskButton', function(assert) {
        // mocks
        var mockResponse = {
            task: {
                taskLabel: 'fakeLabel',
                report: {
                    children: [{
                        data: {
                            uriResource: 'fakeUri'
                        }
                    }]
                }
            }
        };
        var mockEmptyResponse = {
            task: {
                report: {
                    children: [{
                        type: 'error',
                        message: 'fakeMessage'
                    }]
                }
            }
        };

        var $form = $('#simpleWizard');
        var $reportContainer = $form.closest('.content-block');
        var options = {
            $form: $form,
            $reportContainer: $reportContainer,
            buttonTitle: 'Title',
            buttonLabel: 'Label'
        };
        var taskButton = deliveryFormHelper.replaceSubmitWithTaskButton(options);
        var $newButton;

        // taskButton
        assert.equal(typeof taskButton, 'object', 'A task button object was created');
        assert.equal(typeof taskButton.config.taskQueue, 'object', 'The task button has a taskQueue');
        assert.ok(/\/taoDeliveryRdf\/DeliveryMgmt\/wizard/.test(taskButton.config.taskCreationUrl), 'The task button has the right url');

        // old element not there
        assert.equal($('button.form-submitter', $form).length, 0, 'The original button is gone');
        // new element there
        $newButton = ($('button.loading-button', $form));
        assert.equal($newButton.length, 1, 'A new button is present');
        assert.equal($newButton.attr('disabled'), 'disabled', 'The button is disabled');
        assert.ok(taskButton.is('disabled'), 'The component is disabled');
        // title/label
        assert.equal($newButton.attr('title'), 'Title', 'The button title is correct');
        assert.equal($newButton.find(':contains("Label")').length, 1, 'The button label is correct');

        // report feedback
        taskButton.trigger('finished', mockResponse);
        assert.equal($('.feedback.feedback-info :contains("fakeLabel completed")').length, 1, 'Success feedback was created');
        // error in container
        taskButton.trigger('finished', mockEmptyResponse);
        assert.equal($('.task-report-container').length, 1, 'Error feedback was rendered to container');
        assert.equal($('.task-report-container div.message').text(), 'fakeMessage', 'Error feedback contains message');
    });
});
