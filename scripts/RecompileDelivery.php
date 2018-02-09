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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoDeliveryRdf\scripts;

use common_report_Report as Report;
use oat\generis\model\OntologyRdf;
use oat\oatbox\extension\AbstractAction;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\oatbox\action\ResolutionException;

//Load extension to define necessary constants.
\common_ext_ExtensionsManager::singleton()->getExtensionById('taoTests');
\common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');

/**
 * Class RecompileDelivery
 * @package oat\taoDeliveryRdf\scripts
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 *
 * Run examples:
 *
 * - Show list of deliveries:
 * ```
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\RecompileDelivery' list
 * ```
 *
 * - Recompile delivery by identifier
 * ```
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\RecompileDelivery' compile 'http://sample/first.rdf#i1464967192451980'
 * ```
 */
class RecompileDelivery extends AbstractAction
{
    /**
     * @var array Available script modes
     */
    static public $modes = ['list', 'compile'];

    /**
     * List of properties to be copied from parent delivery
     * @var array
     */
    static public $propertiesToCopy = [
        DeliveryContainerService::PROPERTY_END,
        DeliveryContainerService::PROPERTY_START,
        DeliveryContainerService::PROPERTY_MAX_EXEC,
    ];

    /**
     * @var Report
     */
    protected $report;

    /**
     * @var array list of given params
     */
    protected $params;

    /**
     * @var
     */
    protected $mode;

    /**
     * @param $params
     * @return Report
     */
    public function __invoke($params)
    {
        $this->params = $params;

        try {
            $this->process();
        } catch (ResolutionException $e) {
            $this->helpAction($e->getMessage());
        }

        return $this->report;
    }

    /**
     * Process action call
     *
     * @throws ResolutionException
     */
    private function process()
    {
        if (empty($this->params)) {
            throw new ResolutionException('Parameters were not given');
        }
        $mode = $this->getMode();

        switch ($mode) {
            case 'list':
                $this->listAction();
                break;
            case 'compile':
                $this->compileAction();
                break;
        }
    }

    /**
     * Recompile deliveries
     */
    private function compileAction()
    {
        $deliveryIds = array_slice($this->params, 1);

        $deliveryClass = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery');

        $this->report = new Report(
            Report::TYPE_INFO,
            'Recompile deliveries:'
        );

        foreach ($deliveryIds as $deliveryId) {
            $delivery = new \core_kernel_classes_Resource($deliveryId);
            if (!$delivery->exists()) {
                $this->report->add(new Report(
                    Report::TYPE_ERROR,
                    "Delivery $deliveryId does not exists"
                ));
                continue;
            } else if (!$delivery->isInstanceOf($deliveryClass)) {
                $this->report->add(new Report(
                    Report::TYPE_ERROR,
                    "$deliveryId is not delivery resource"
                ));
                continue;
            }

            try {
                $newDelivery = $this->compileDelivery($delivery);
            } catch (\common_Exception $e){
                $this->report->add(new Report(
                    Report::TYPE_ERROR,
                    $e->getMessage()
                ));
            }

            $this->report->add(new Report(
                Report::TYPE_SUCCESS,
                "$deliveryId successfully compiled. New Id: {$newDelivery->getUri()}"
            ));
        }
    }

    /**
     * Show list of all existing deliveries
     */
    private function listAction()
    {
        $deliveryClass = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery');
        $deliveries = $deliveryClass->getInstances(true);
        $result = [];
        foreach ($deliveries as $delivery) {
            /** @var \core_kernel_classes_Resource $delivery*/
            $result[] = $delivery->getUri() . ' - ' . $delivery->getLabel();
        }
        $this->report = new Report(
            Report::TYPE_INFO,
            implode(PHP_EOL, $result)
        );
    }

    /**
     * Set help report
     * @param string $message error message to be shown before help information
     */
    private function helpAction($message = null)
    {
        if ($message !== null) {
            $this->report = new Report(
                Report::TYPE_ERROR,
                $message . PHP_EOL
            );
        }

        $helpReport = new Report(
            Report::TYPE_INFO,
            "Usage: " . __CLASS__ . " <mode> [<args>]" . PHP_EOL . PHP_EOL
            . "Available modes:" . PHP_EOL
            . "   list        get list of all deliveries" . PHP_EOL
            . "   compile     recompile deliveries" . PHP_EOL
        );

        if ($this->report) {
            $this->report->add($helpReport);
        } else {
            $this->report = $helpReport;
        }
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @throws \common_Exception
     * @return \core_kernel_classes_Resource new delivery resource
     */
    private function compileDelivery(\core_kernel_classes_Resource $delivery)
    {
        $testProperty = new \core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryOrigin');
        $classProperty = new \core_kernel_classes_Property(OntologyRdf::RDF_TYPE);
        $test = $delivery->getOnePropertyValue($testProperty);
        $destinationClass = new \core_kernel_classes_Class($delivery->getOnePropertyValue($classProperty)->getUri());

        $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
        $deliveryCreationReport = $deliveryFactory->create($destinationClass, $test, $delivery->getLabel());
        if ($deliveryCreationReport->getType() == \common_report_Report::TYPE_ERROR) {
            \common_Logger::i('Unable to recompile delivery execution' . $delivery->getUri());
            throw new \common_Exception($deliveryCreationReport->getMessage());
        }
        /** @var \core_kernel_classes_Resource $newDelivery */
        $newDelivery = $deliveryCreationReport->getData();

        foreach (self::$propertiesToCopy as $propertyToCopy) {
            $propertyToCopy = new \core_kernel_classes_Property($propertyToCopy);
            $val = $delivery->getOnePropertyValue($propertyToCopy);
            if ($val) {
                $newDelivery->setPropertyValue($propertyToCopy, $val);
            }
        }

        $this->addPrefixToLabel($delivery);

        return $newDelivery;
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     */
    private function addPrefixToLabel(\core_kernel_classes_Resource $delivery)
    {
        $label = $delivery->getLabel();
        $label .= " - old"; //todo: use an option instead of hardcoded value
        $delivery->setLabel($label);
    }

    /**
     * @throws ResolutionException
     * @return string
     */
    private function getMode()
    {
        $mode = $this->params[0];
        if (!in_array($mode, self::$modes)) {
            throw new ResolutionException('Wrong mode was specified');
        }
        return $mode;
    }
}