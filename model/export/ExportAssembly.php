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
 * Copyright (c) 2015-2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *               
 */

namespace oat\taoDeliveryRdf\model\export;

use oat\oatbox\extension\AbstractAction;
use oat\taoDeliveryRdf\model\AssemblerServiceInterface;
use common_report_Report as Report;

/**
 * Exports the specified Assembly
 * 
 * @author Joel Bout
 *
 */
class ExportAssembly extends AbstractAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \Exception
     * @throws \common_ext_ExtensionException
     */
    public function __invoke($params) {
        if (count($params) < 2) {
            return new Report(Report::TYPE_ERROR, __('Usage: %s DELIVERY_URI OUTPUT_FILE [USE_SHARED_FILESYSTEM]', __CLASS__));
        }

        $deliveryUri = array_shift($params);
        $delivery = new \core_kernel_classes_Resource($deliveryUri);
        if (!$delivery->exists()) {
            return new Report(Report::TYPE_ERROR, __('Delivery \'%s\' not found', $deliveryUri));
        }
        
        $file = array_shift($params);

        $sharedFsParam = array_shift($params);
        $useSharedFileSystem = false;
        if (!is_null($sharedFsParam)) {
            $sharedFsParam = trim($sharedFsParam);
            if ($sharedFsParam === 'true') {
                $useSharedFileSystem = true;
            } elseif ($sharedFsParam !== 'false') {
                return new Report(Report::TYPE_ERROR, __("The shared filesystem parameter should be a boolean value 'true' or 'false'."));
            }
        }

        $assemblerService = $this->getServiceLocator()->get(AssemblerServiceInterface::SERVICE_ID);

        if (!$useSharedFileSystem) {
            $tmpFile = $assemblerService->exportCompiledDelivery($delivery);
            \tao_helpers_File::move($tmpFile, $file);
        } else {
            $assemblerService->exportCompiledDelivery($delivery, $file);
        }

        $finalReport = new Report(Report::TYPE_SUCCESS, __('Exported %1$s to %2$s', $delivery->getLabel(), $file));
        if ($useSharedFileSystem) {
            $finalReport->add(
                new Report(Report::TYPE_INFO, "File exported to shared file system.")
            );
        }

        return $finalReport;
    }

}
