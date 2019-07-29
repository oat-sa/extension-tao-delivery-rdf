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
    'lib/jquery.mockjax/jquery.mockjax'
], function ($, providers) {
    'use strict';

    var requests;

    // prevent the AJAX mocks to pollute the logs
    $.mockjaxSettings.logger = null;
    $.mockjaxSettings.responseTime = 1;

    // restore AJAX method after each test
    QUnit.testDone(function() {
        $.mockjax.clear();
    });

    QUnit.module('providers');

    QUnit.test('module', function(assert) {
        assert.expect(1);
        assert.equal(typeof providers, 'object', 'The providers module exposes an object');
    });

    QUnit.test('instance API ', function(assert) {
        assert.expect(1);
        assert.equal(typeof providers.listTests, 'function', 'The providers instance exposes a "listTests" function');
    });

    requests = {
        resolving: [
            {
                q: 'example',
                page: 1,
                status: 200,
                results: ['QTI Example test']
            },
            {
                q: 'xcckdmcv',
                page: 1,
                status: 200,
                results: []
            }
        ],
        rejecting: [
            {
                q: 'example',
                page: 1,
                status: 200,
                results: null,
                errorMsg: 'Unable to load tests'
            },
            {
                q: 'example',
                page: 1,
                status: 500,
                errorMsg: 'Unable to load tests'
            }
        ]
    };

    QUnit.cases.init(requests.resolving).test('listTests resolving', function(caseData, assert) {
        var ready = assert.async();
        var prom;

        $.mockjax({
            url: /taoDeliveryRdf\/DeliveryMgmt\/getAvailableTests/,
            status: caseData.status,
            dataType: 'json',
            contentType: 'application/json',
            data: function() {
                assert.ok(true, 'Request received at configured url');
                return true;
            },
            responseText: caseData.results
        });

        assert.expect(3);

        prom = providers.listTests(caseData)
            .then(function(value) {
                assert.deepEqual(value, caseData.results, 'The promise resolved with the expected value');
                ready();
            })
            .catch(function() {
                assert.ok(false, 'Should not reject');
                ready();
            });
        assert.ok(prom instanceof Promise, 'listTests returns a Promise');
    });

    QUnit.cases.init(requests.rejecting).test('listTests rejecting', function(caseData, assert) {
        var ready = assert.async();
        var prom;

        $.mockjax({
            url: /taoDeliveryRdf\/DeliveryMgmt\/getAvailableTests/,
            status: caseData.status,
            dataType: 'json',
            contentType: 'application/json',
            data: function() {
                assert.ok(true, 'Request received at configured url');
                return true;
            },
            responseText: caseData.results
        });

        assert.expect(4);

        prom = providers.listTests(caseData)
            .then(function() {
                assert.ok(false, 'Should not resolve');
                ready();
            })
            .catch(function(error) {
                assert.ok(error instanceof Error, 'The promise rejected with a proper error');
                assert.equal(error.message, caseData.errorMsg, 'The expected error message is attached');
                ready();
            });
        assert.ok(prom instanceof Promise, 'listTests returns a Promise');
    });
});
