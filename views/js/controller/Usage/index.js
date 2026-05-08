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
    'jquery',
    'i18n',
    'util/url',
    'ui/datatable',
    'layout/actions/binder'
], function ($, __, url) {
    'use strict';

    const lineHeight = 30;
    const searchPagination = 70;

    function getNumRows() {
        const $upperElem = $('.content-container h2');
        const topOffset = $upperElem.length > 0 && $upperElem.offset() ? $upperElem.offset().top : 0;
        const upperHeight = $upperElem.length > 0 ? ($upperElem.height() || 0) : 0;
        const marginBottom = $upperElem.length > 0 ? (parseInt($upperElem.css('margin-bottom'), 10) || 0) : 0;
        const footerHeight = $('footer.dark-bar').outerHeight() || 0;
        const topSpace = topOffset
            + upperHeight
            + marginBottom
            + lineHeight
            + searchPagination;
        const availableHeight = $(window).height() - topSpace - footerHeight;

        if (!window.MSInputMethodContext && !document.documentMode && !window.StyleMedia) {
            return 25;
        }

        const computedRows = availableHeight > 0 ? Math.floor(availableHeight / lineHeight) : 1;

        return Math.max(1, Math.min(computedRows, 25));
    }

    function getColumns(mode) {
        if (mode === 'delivery') {
            return [
                {
                    id: 'label',
                    label: __('Label'),
                    sortable: true
                },
                {
                    id: 'location',
                    label: __('Location'),
                    sortable: false
                }
            ];
        }

        return [
            {
                id: 'label',
                label: __('Label'),
                sortable: true
            },
            {
                id: 'location',
                label: __('Location'),
                sortable: true
            },
            {
                id: 'publicationTime',
                label: __('Last modified on'),
                sortable: true
            }
        ];
    }

    return {
        start: function start() {
            const urlInfo = url.parse(window.location);
            const $grid = $('.usage-grid');
            const mode = $grid.data('mode');
            const uri = $grid.data('uri');

            if (typeof uri !== 'string' || uri.trim() === '' || (mode !== 'delivery' && mode !== 'test')) {
                return;
            }

            const dataUrl = mode === 'delivery'
                ? url.route('getDeliverySourceTestData', 'Usage', 'taoDeliveryRdf', { uri: uri })
                : url.route('getTestDeliveriesUsageData', 'Usage', 'taoDeliveryRdf', { uri: uri });

            const actions = [
                {
                    id: 'link-action',
                    title: __('View'),
                    label: __('View'),
                    disabled: function disabled() {
                        return !this.id;
                    },
                    action: function action(targetUri) {

                        const pathParams = mode === 'delivery'
                            ? {
                                ext: 'taoTests',
                                section: 'manage_tests',
                                structure: 'tests',
                                uri: targetUri
                            }
                            : {
                                ext: 'taoDeliveryRdf',
                                section: 'manage_delivery_assembly',
                                structure: 'delivery',
                                uri: targetUri
                            };

                        const openedWindow = window.open(
                            url.build(urlInfo.path, pathParams),
                            '_blank',
                            'noopener,noreferrer'
                        );

                        if (openedWindow) {
                            openedWindow.opener = null;
                        }
                    }
                }
            ];

            $grid.datatable({
                url: dataUrl,
                filter: true,
                labels: {
                    filter: mode === 'delivery' ? __('Search Tests') : __('Search Deliveries'),
                    title: mode === 'delivery' ? __('Search Tests') : __('Search Deliveries')
                },
                model: getColumns(mode),
                paginationStrategyTop: 'none',
                paginationStrategyBottom: 'simple',
                rows: getNumRows(),
                sortby: mode === 'delivery' ? 'label' : 'publicationTime',
                sortorder: mode === 'delivery' ? 'asc' : 'desc',
                actions: actions
            });
        }
    };
});
