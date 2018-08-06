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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoDeliveryRdf\scripts\tools;

use common_report_Report as Report;
use oat\generis\model\kernel\persistence\smoothsql\search\filter\Filter;
use oat\generis\model\kernel\persistence\smoothsql\search\filter\FilterOperator;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\ResolutionException;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoDeliveryRdf\model\DeliverArchiveExistingException;
use oat\taoDeliveryRdf\model\DeliveryArchiveNotExistingException;
use oat\taoDeliveryRdf\model\DeliveryZipException;
use oat\taoDeliveryRdf\model\DeliveryArchiveService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

/**
 * Run examples:
 *
 * - Show list of deliveries:
 * ```
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\tools\DeliveryExecutionArchive' list
 * ```
 */
class DeliveryExecutionArchive extends AbstractAction
{
    use OntologyAwareTrait;
    /**
     * @var array Available script modes
     */
    static public $options = ['list', 'archive', 'unarchive', 'delete'];

    /**
     * @var Report
     */
    protected $report;

    /**
     * @var array list of given params
     */
    protected $params;

    /**
     * @param $params
     * @return Report
     * @throws \common_exception_Error
     */
    public function __invoke($params)
    {
        $this->params = $params;

        try {
            $this->process();

        } catch (\Exception $e) {
            $this->helpAction($e->getMessage());
        }

        return $this->report;
    }

    /**
     * Process action call
     *
     * @throws ResolutionException
     * @throws \common_exception_Error
     * @throws ServiceNotFoundException
     */
    private function process()
    {
        $time_start = microtime(true);

        if (empty($this->params)) {
            throw new ResolutionException('Parameters were not given');
        }
        $option = $this->getOptionUsed();

        switch ($option) {
            case 'list':
                $this->listAction();
                break;
            case 'unarchive':
                $this->unArchiveAction();
                break;
            case 'archive':
                $this->archiveAction();
                break;
            case 'delete':
                $this->deleteArchivesAction();
                break;
        }

        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start)/60;

        $this->report->add(new Report(Report::TYPE_INFO, 'Time:' . round($execution_time, 4) .' Minutes.' ));
    }

    /**
     * Show list of all existing deliveries
     */
    private function listAction()
    {
        $deliveryClass = $this->getClass(DeliveryAssemblyService::CLASS_URI );
        $deliveries = $deliveryClass->getInstances(true);
        $result = [];
        foreach ($deliveries as $delivery) {
            /** @var \core_kernel_classes_Resource $delivery */
            $result[] = $this->deliveryDescription($delivery);
        }
        $this->report = new Report(
            Report::TYPE_INFO,
            implode(PHP_EOL, $result)
        );
    }

    /**
     * @throws \common_exception_Error
     * @throws ServiceNotFoundException
     */
    private function unArchiveAction()
    {
        $this->report = new Report(
            Report::TYPE_INFO,
            'Unarchived deliveries:'
        );

        $deliveryClass = $this->getClass(DeliveryAssemblyService::CLASS_URI );
        $deliveries = $deliveryClass->searchInstances([
            new Filter(DeliveryAssemblyService::PROPERTY_DELIVERY_TIME, null, FilterOperator::createIsNotNull())
        ]);

        /** @var DeliveryArchiveService $archiveService */
        $archiveService = $this->getServiceLocator()->get(DeliveryArchiveService::SERVICE_ID);
        $this->propagate($archiveService);

        /** @var \core_kernel_classes_Resource $compiledDelivery */
        foreach ($deliveries as $compiledDelivery) {
            try {
                $fileName = $archiveService->unArchive($compiledDelivery, $this->isForced());
                $this->report->add(new Report(Report::TYPE_SUCCESS, 'Delivery '.$this->deliveryDescription($compiledDelivery).' unarchived completed: ' .$fileName  ));
            }catch (DeliveryArchiveNotExistingException $exception) {
                $this->report->add(new Report(Report::TYPE_ERROR,  'Delivery '.$this->deliveryDescription($compiledDelivery).' '. $exception->getMessage()));
            }catch (DeliveryZipException $exception) {
                $this->report->add(new Report(Report::TYPE_ERROR, 'Delivery '.$this->deliveryDescription($compiledDelivery).' '. $exception->getMessage()));
            }
        }
    }

    /**
     * @throws \common_exception_Error
     * @throws ServiceNotFoundException
     */
    private function archiveAction()
    {
        $this->report = new Report(
            Report::TYPE_INFO,
            'Archived deliveries:'
        );

        $deliveryClass = $this->getClass(DeliveryAssemblyService::CLASS_URI );
        $deliveries = $deliveryClass->searchInstances([
            new Filter(DeliveryAssemblyService::PROPERTY_DELIVERY_TIME, null, FilterOperator::createIsNotNull())
        ]);
        /** @var DeliveryArchiveService $archiveService */
        $archiveService = $this->getServiceLocator()->get(DeliveryArchiveService::SERVICE_ID);
        $this->propagate($archiveService);

        /** @var \core_kernel_classes_Resource $compiledDelivery */
        foreach ($deliveries as $compiledDelivery) {
            try {
                $fileName = $archiveService->archive($compiledDelivery, $this->isForced());
                $this->report->add(new Report(Report::TYPE_SUCCESS, 'Delivery '.$this->deliveryDescription($compiledDelivery).' archive created: ' .$fileName));
            } catch (DeliverArchiveExistingException $exception) {
                $this->report->add(new Report(Report::TYPE_ERROR, 'Delivery '.$this->deliveryDescription($compiledDelivery).' '. $exception->getMessage() . ' use --force to regenerate'  ));
            } catch (DeliveryZipException $exception) {
                $this->report->add(new Report(Report::TYPE_ERROR, 'Delivery '.$this->deliveryDescription($compiledDelivery).' '. $exception->getMessage()));
            }
        }
    }

    /**
     * @throws \common_exception_Error
     * @throws ServiceNotFoundException
     */
    private function deleteArchivesAction()
    {
        $this->report = new Report(
            Report::TYPE_INFO,
            'Deleted Archived deliveries:'
        );

        $deliveryClass = $this->getClass(DeliveryAssemblyService::CLASS_URI );
        $deliveries = $deliveryClass->searchInstances([
            new Filter(DeliveryAssemblyService::PROPERTY_DELIVERY_TIME, null, FilterOperator::createIsNotNull())
        ]);
        /** @var DeliveryArchiveService $archiveService */
        $archiveService = $this->getServiceLocator()->get(DeliveryArchiveService::SERVICE_ID);
        $this->propagate($archiveService);

        /** @var \core_kernel_classes_Resource $compiledDelivery */
        foreach ($deliveries as $compiledDelivery) {
            $fileName = $archiveService->deleteArchive($compiledDelivery);

            $this->report->add(new Report(Report::TYPE_SUCCESS, 'Delivery '.$this->deliveryDescription($compiledDelivery).' archive deleted: ' .$fileName  ));
        }
    }

    /**
     * @throws ResolutionException
     * @return string
     */
    private function getOptionUsed()
    {
        $mode = $this->params[0];

        if (!in_array($mode, self::$options)) {
            throw new ResolutionException('Wrong mode was specified');
        }
        return $mode;
    }

    /**
     * @return bool
     */
    protected function isForced()
    {
        $isForce = isset($this->params[1]) ? $this->params[1] : false;
        if ($isForce === '--force') {
            return true;
        }

        return false;
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @return string
     */
    private function deliveryDescription($delivery)
    {
        return $delivery->getLabel() . ' - ' .  $delivery->getUri();
    }

    /**
     * Set help report
     * @param string $message error message to be shown before help information
     * @throws \common_exception_Error
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
            . "   archive     archive all deliveries use --force to forge regeneration" . PHP_EOL
            . "   unarchive   unarchive all deliveries --force to forge unarchiving" . PHP_EOL
            . "   delete      delete all archives deliveries" . PHP_EOL
        );

        if ($this->report) {
            $this->report->add($helpReport);
        } else {
            $this->report = $helpReport;
        }
    }
}