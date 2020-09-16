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
 * Copyright (c) 2017-2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\controller;

use common_report_Report as Report;
use tao_helpers_Http as HttpHelper;
use common_exception_Error as Error;
use oat\tao\model\import\ImporterNotFound;
use oat\tao\model\taskQueue\TaskLogInterface;
use tao_actions_RestController as RestController;
use common_exception_NotFound as NotFoundException;
use oat\taoDeliveryRdf\model\tasks\ImportAndCompile;
use common_exception_BadRequest as BadRequestException;
use common_exception_NotImplemented as NotImplementedException;
use common_exception_MissingParameter as MissingParameterException;

/**
 * Class RestTest
 *
 * @package oat\taoDeliveryRdf\controller
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RestTest extends RestController
{
    public const REST_IMPORTER_ID = 'importerId';
    public const REST_FILE_NAME = 'testPackage';
    public const REST_DELIVERY_PARAMS = 'delivery-params';
    public const REST_DELIVERY_CLASS_LABELS = 'delivery-class-labels';

    /** @var array */
    private $body;

    /**
     * Import test and compile it into delivery
     *
     * @throws Error
     * @throws MissingParameterException
     * @throws NotImplementedException
     */
    public function compileDeferred(): void
    {
        if ($this->getRequestMethod() !== \Request::HTTP_POST) {
            throw new NotImplementedException('Only post method is accepted to compile test');
        }

        if (!$this->hasRequestParameter(self::REST_IMPORTER_ID)) {
            throw new MissingParameterException(self::REST_IMPORTER_ID, $this->getRequestURI());
        }

        if (HttpHelper::hasUploadedFile(self::REST_FILE_NAME)) {
            try {
                $importerId = $this->getParameter(self::REST_IMPORTER_ID);
                $file = HttpHelper::getUploadedFile(self::REST_FILE_NAME);
                $customParams = $this->getDecodedParameter(self::REST_DELIVERY_PARAMS);
                $deliveryClassLabels = $this->getDecodedParameter(self::REST_DELIVERY_CLASS_LABELS);

                $task = ImportAndCompile::createTask($importerId, $file, $customParams, $deliveryClassLabels);
            } catch (ImporterNotFound $e) {
                $this->returnFailure(new NotFoundException($e->getMessage()));
            }

            $result = ['reference_id' => $task->getId()];

            /** @var TaskLogInterface $taskLog */
            $taskLog = $this->getServiceLocator()->get(TaskLogInterface::SERVICE_ID);
            $report = $taskLog->getReport($task->getId());

            if (!empty($report)) {
                if ($report instanceof Report) {
                    // Serialize report to array
                    $report = json_decode(json_encode($report));
                }

                $result['common_report_Report'] = $report;
            }

            $this->returnSuccess($result);
        } else {
            $this->returnFailure(new BadRequestException('Test package file was not given'));
        }
    }

    /**
     * @param string $name
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    private function getParameter(string $name, $default = null)
    {
        $bodyParameters = $this->getPsrRequest()->getParsedBody();

        if (is_array($bodyParameters) && isset($bodyParameters[$name])) {
            return $bodyParameters[$name];
        }

        if (is_object($bodyParameters) && property_exists($bodyParameters, $name)) {
            return $bodyParameters->{$name};
        }

        return $default;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    private function getDecodedParameter(string $name): array
    {
        $data = $this->getParameter($name, []);

        return is_array($data)
            ? $data
            : json_decode(html_entity_decode($data), true);
    }
}
