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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
define([
    'jquery',
    'i18n',
    'util/url',
    'layout/actions',
    'provider/resources',
    'ui/destination/selector',
    'ui/tabs',
    'ui/datatable',
], function ($, __, url, actionManager, resourceProviderFactory, destinationSelectorFactory, tabsFactory) {
    'use strict';

    const $window = $(window);

    function getNumRows() {
        const lineHeight       = 30;
        const searchPagination = 70;
        const $upperElem       = $('.content-container h2');
        const topSpace         = $upperElem.offset().top
            + $upperElem.height()
            + parseInt($upperElem.css('margin-bottom'), 10)
            + lineHeight
            + searchPagination;
        const availableHeight = $window.height() - topSpace - $('footer.dark-bar').outerHeight();
        if(!window.MSInputMethodContext && !document.documentMode && !window.StyleMedia) {
            return 25;
        }
        return Math.min(Math.floor(availableHeight / lineHeight), 25);
    }

    function viewResult() {
        console.log('res')
    }

    return {

        start : function start() {

            // Selectors
            const $container = $('.selector-container');
            const $usageTestsContainer = $('.usage-tests-container');
            const $usageSessionsContainer = $('.usage-sessions-container');
            const $tabsContentContainer = $('.usage-tabs-container');
            const $contentBlock = $('.content-panel .content-container .content-block').first();
            const $deliverTenantSelectList = $('.deliver-tenant-list');

            // Callback functions for the selected tabs
            const onTaoSessionsClick = () => {
                $container.removeClass('hidden');
            };

            const onTaoTestsClick = () => {
                if ($deliverTenantSelectList.children().length) {
                    $container.removeClass('hidden');
                }
                else {
                    $container.addClass('hidden');
                }
            };

            // Initialization of the tab component
            const $tabContainer = $('.tab-selector', $contentBlock);
            const tabs = [
                {
                    label: 'Tests',
                    name: 'tao-tests'
                },
                {
                    label: 'Sessions',
                    name: 'tao-sessions'
                }
            ];

            tabsFactory($tabContainer, {
                showHideTarget: $tabsContentContainer,
                hideLoneTab: true,
                tabs: tabs
            })
                .on('tabchange-tao-sessions', onTaoSessionsClick)
                .on('tabchange-tao-tests', onTaoTestsClick);

            $usageTestsContainer
                .datatable({
                    url: url.route('data', 'ResultsMonitoring', 'taoOutcomeUi'), //todo: find normal url
                    filter: true,
                    labels: {
                        filter: __('Search by results')
                    },
                    model: [{
                        id: 'testLabel',
                        label: __('Label'),
                        sortable: true
                    }, {
                        id: 'location',
                        label: __('Location'),
                        sortable: true,
                    }, {
                        id: 'last-modified',
                        label: __('Last modified on'),
                        sortable: true
                    }],
                    paginationStrategyTop: 'none',
                    paginationStrategyBottom: 'simple',
                    rows: getNumRows(),
                    sortby: 'result_id',
                    sortorder: 'desc',
                    actions : {
                        'view' : {
                            id: 'view',
                            label: __('View'),
                            action: viewResult
                        }
                    }
                });

            $usageSessionsContainer
                .datatable({
                    url: url.route('data', 'ResultsMonitoring', 'taoOutcomeUi'), //todo: find normal url
                    filter: true,
                    labels: {
                        filter: __('Search by results')
                    },
                    model: [{
                        id: 'sessionLabel',
                        label: __('Name'),
                        sortable: true
                    }, {
                        id: 'last-published',
                        label: __('Last published on'),
                        sortable: true,
                    }],
                    paginationStrategyTop: 'none',
                    paginationStrategyBottom: 'simple',
                    rows: getNumRows(),
                    sortby: 'result_id',
                    sortorder: 'desc',
                });
        }
    };
});