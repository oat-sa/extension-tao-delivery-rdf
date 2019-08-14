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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoDeliveryRdf\scripts\tools;

use common_report_Report as Report;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\script\ScriptAction;
use oat\taoDeliveryRdf\model\AssemblerServiceInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

class ImportDeliveryAssembly extends ScriptAction
{
    use OntologyAwareTrait;

    const OPTION_ASSEMBLY_FILE = 'assembly-file';

    const OPTION_CLASS_URI = 'class-uri';

    const OPTION_USE_ORIGINAL_URI = 'use-original-uri';

    /**
     * @var Report
     */
    private $report;

    /**
     * @return string
     */
    protected function provideDescription()
    {
        return 'Import compiled delivery assembly with possibility to specify class and use delivery\'s original URI';
    }

    /**
     * @return array
     */
    protected function provideOptions()
    {
        return [
            self::OPTION_ASSEMBLY_FILE => [
                'prefix' => 'f',
                'required' => true,
                'longPrefix' => self::OPTION_ASSEMBLY_FILE,
                'description' => 'Path to the compiled assembly file.'
            ],
            self::OPTION_USE_ORIGINAL_URI => [
                'prefix' => 'uri',
                'required' => true,
                'flag' => true,
                'longPrefix' => self::OPTION_USE_ORIGINAL_URI,
                'description' => 'Use delivery URI from assembly package.',
            ],
            self::OPTION_CLASS_URI => [
                'prefix' => 'class',
                'longPrefix' => self::OPTION_CLASS_URI,
                'description' => 'Import into provided class.',
            ],
        ];
    }

    /**
     * @return Report
     */
    protected function run()
    {
        $this->report = Report::createInfo('Delivery assembly import started');

        try {
            $file = $this->getOption(self::OPTION_ASSEMBLY_FILE);
            $importClass = $this->getImportClass();
            /** @var AssemblerServiceInterface $importer */
            $importer = $this->getServiceLocator()->get(AssemblerServiceInterface::SERVICE_ID);

            $importReport = $importer->importDelivery($importClass, $file, $this->getOption(self::OPTION_USE_ORIGINAL_URI));

            $this->report->add($importReport);
        } catch (\Exception $e) {
            $this->report->add(Report::createFailure("Export failed: " . $e->getMessage()));
        }

        return $this->report;
    }

    /**
     * @return array
     */
    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints a help statement'
        ];
    }

    /**
     * @return \core_kernel_classes_Class
     */
    private function getImportClass()
    {
        if ($this->hasOption(self::OPTION_CLASS_URI)) {
            return $this->getOption(self::OPTION_CLASS_URI);
        }

        return $this->getClass(DeliveryAssemblyService::CLASS_URI);
    }
}
