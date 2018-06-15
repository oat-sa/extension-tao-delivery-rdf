<?php
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

namespace oat\taoDeliveryRdf\model;

/**
 * Interface AssemblerServiceInterface
 *
 * Interface to be implemented in order to be an AssemblerService. An assembler service
 * aims at importing/exporting compiled deliveries from TAO platforms to other systems (including other TAO
 * platforms).
 *
 * @package oat\taoDeliveryRdf\model
 */
interface AssemblerServiceInterface
{
    const SERVICE_ID = 'taoDeliveryRdf/AssemblerService';

    /**
     * Import Delivery
     *
     * Import a compiled delivery into a specific class.
     *
     * @param \core_kernel_classes_Class $deliveryClass
     * @param string $archiveFile Path to archive file.
     * @return \common_report_Report
     */
    public function importDelivery(\core_kernel_classes_Class $deliveryClass, $archiveFile);

    /**
     * Export Compiled Delivery
     *
     * Exports a delivery into its compiled form. In case of the $fsExportPath argument is set,
     * the compiled delivery will be stored in the 'taoDelivery' shared file system, at $fsExportPath location.
     *
     * @param \core_kernel_classes_Resource $compiledDelivery
     * @param string $fsExportPath (optional) A relative path to use to store the compiled delivery into the 'taoDelivery' shared file system.
     * @return string The path to the compiled delivery on the local file system OR the 'taoDelivery' shared file system, depending on whether $fsExportPath is set.
     */
    public function exportCompiledDelivery(\core_kernel_classes_Resource $compiledDelivery, $fsExportPath = '');
}