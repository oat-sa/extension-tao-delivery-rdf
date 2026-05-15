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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA.
 *
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */
define([
    'taoDeliveryRdf/controller/Usage/index'
], function (usageController) {
    'use strict';

    QUnit.module('Usage controller');

    QUnit.test('module', function (assert) {
        assert.expect(3);
        assert.equal(typeof usageController, 'object', 'The Usage controller exposes an object');
        assert.equal(typeof usageController.getModeSettings, 'function', 'The controller exposes getModeSettings');
        assert.equal(typeof usageController.getColumns, 'function', 'The controller exposes getColumns');
    });

    QUnit.test('delivery mode disables search and pagination', function (assert) {
        assert.expect(5);

        const settings = usageController.getModeSettings('delivery');

        assert.strictEqual(settings.filter, false, 'Search filter is disabled');
        assert.strictEqual(settings.paginationStrategyTop, 'none', 'Top pagination is hidden');
        assert.strictEqual(settings.paginationStrategyBottom, 'none', 'Bottom pagination is hidden');
        assert.strictEqual(settings.sortby, 'label', 'Default sort is label');
        assert.strictEqual(settings.sortorder, 'asc', 'Default sort order is ascending');
    });

    QUnit.test('test mode enables search and pagination', function (assert) {
        assert.expect(5);

        const settings = usageController.getModeSettings('test');

        assert.strictEqual(settings.filter, true, 'Search filter is enabled');
        assert.strictEqual(settings.paginationStrategyTop, 'none', 'Top pagination is hidden');
        assert.strictEqual(settings.paginationStrategyBottom, 'simple', 'Bottom pagination is enabled');
        assert.strictEqual(settings.sortby, 'publicationTime', 'Default sort is publication time');
        assert.strictEqual(settings.sortorder, 'desc', 'Default sort order is descending');
    });

    QUnit.test('columns depend on mode', function (assert) {
        assert.expect(4);

        const deliveryColumns = usageController.getColumns('delivery');
        const testColumns = usageController.getColumns('test');

        assert.strictEqual(deliveryColumns.length, 2, 'Delivery usage has two columns');
        assert.strictEqual(deliveryColumns[1].sortable, false, 'Delivery location is not sortable');

        assert.strictEqual(testColumns.length, 3, 'Test usage has three columns');
        assert.strictEqual(testColumns[1].sortable, true, 'Test location is sortable');
    });

    QUnit.test('unsupported mode falls back to test settings and columns', function (assert) {
        assert.expect(2);

        const testSettings = usageController.getModeSettings('test');
        const unsupportedSettings = usageController.getModeSettings('unsupported');

        assert.deepEqual(
            unsupportedSettings,
            testSettings,
            'Unknown mode uses the same datatable settings as test mode'
        );

        const testColumns = usageController.getColumns('test');
        const unsupportedColumns = usageController.getColumns('unsupported');

        assert.deepEqual(
            unsupportedColumns,
            testColumns,
            'Unknown mode uses the same column model as test mode'
        );
    });
});
