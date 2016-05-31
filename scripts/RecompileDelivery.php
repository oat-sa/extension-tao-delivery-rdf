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

use oat\oatbox\action\Action;
use common_report_Report as Report;
use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\SimpleDeliveryFactory;
use oat\oatbox\action\ResolutionException;

/**
 * Class RecompileDelivery
 * @package oat\taoDeliveryRdf\scripts
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 *
 * Run example:
 * ```
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\RecompileDelivery'
 * ```
 */
class RecompileDelivery implements Action
{
    /**
     * @var array Available script modes
     */
    static $modes = ['list', 'compile'];

    /**
     * @var \Report
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
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoTests');
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');

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

            $newDelivery = $this->compileDelivery($delivery);

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
     * @return \core_kernel_classes_Resource new delivery resource
     */
    private function compileDelivery(\core_kernel_classes_Resource $delivery)
    {
        $testProperty = new \core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryOrigin');
        $classProperty = new \core_kernel_classes_Property(RDF_TYPE);
        $test = $delivery->getOnePropertyValue($testProperty);
        $destinationClass = new \core_kernel_classes_Class($delivery->getOnePropertyValue($classProperty)->getUri());

        $deliveryCreationReport = SimpleDeliveryFactory::create($destinationClass, $test, $delivery->getLabel());
        $newDelivery = $deliveryCreationReport->getData();

        return $newDelivery;
    }

    /**
     * @throws ResolutionException
     * @return sting
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