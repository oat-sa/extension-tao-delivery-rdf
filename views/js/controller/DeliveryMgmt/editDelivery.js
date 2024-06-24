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
 * Copyright (c) 2013-2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */
define([
    'jquery',
    'util/url',
    'services/features',
    'ui/modal',
    'css!taoDeliveryRdfCss/delivery-rdf.css'
], function ($, urlUtil, features) {
    'use strict';

    /**
     * Finds property blocks starting with propName and hides it adding .hidden class
     * @param {string} propName 
     */
    function hidePropertyBlockByName(propName) {
        const inputCssQuery = `form[action="/taoDeliveryRdf/DeliveryMgmt/editDelivery"] input[name^="${propName}"]`;
        $(inputCssQuery).closest('form > div').addClass('hidden');
    }

    return {
        start(){

            const featuresPath = 'taoDeliveryRdf/deliveryMgmt/';
            if(!features.isVisible(`${featuresPath}resourceIdentifier`)) {
                hidePropertyBlockByName('id');
            }
            if(!features.isVisible(`${featuresPath}maxExecutions`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_Maxexec');
            }
            if(!features.isVisible(`${featuresPath}maxExecutions`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_Maxexec');
            }
            if(!features.isVisible(`${featuresPath}startDate`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_PeriodStart');
            }
            if(!features.isVisible(`${featuresPath}endDate`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_PeriodEnd');
            }
            if(!features.isVisible(`${featuresPath}displayOrder`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_DisplayOrder');
            }
            if(!features.isVisible(`${featuresPath}access`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_AccessSettings_0');
            }
            if(!features.isVisible(`${featuresPath}proctoringSettings`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_ProctorAccessible_0');
            }
            if(!features.isVisible(`${featuresPath}publicationId`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_taoDeliverConnect_0_rdf_3_PublicationId');
            }
            if(!features.isVisible(`${featuresPath}assessmentProjectId`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_AssessmentProjectId');
            }
            if(!features.isVisible(`${featuresPath}testRunnerFeatures`)) {
                hidePropertyBlockByName('http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_DeliveryTestRunnerFeatures_');
            }
                        
            $('#exclude-btn').click(function() {
                const delivery = $(this).data('delivery');

                $('#testtaker-form').load(
                    urlUtil.route('excludeTesttaker', 'DeliveryMgmt', 'taoDeliveryRdf', {'uri' : delivery}),
                    () => {
                        $('body').prepend($('#modal-container'));
                        $('#testtaker-form').modal();
                    }
                );
            });
        }
    };
});
