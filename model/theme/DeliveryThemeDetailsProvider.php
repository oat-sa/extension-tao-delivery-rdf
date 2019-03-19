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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoDeliveryRdf\model\theme;

use common_exception_Error;
use common_exception_NotImplemented;
use common_persistence_KeyValuePersistence;
use common_persistence_KvDriver;
use common_Serializable;
use core_kernel_classes_Resource;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\theme\ThemeDetailsProviderInterface;
use oat\taoDelivery\model\execution\DeliveryExecution;
use PHPSession;

class DeliveryThemeDetailsProvider extends ConfigurableService implements ThemeDetailsProviderInterface
{
    /**
     * The delivery theme id uri.
     */
    const DELIVERY_THEME_ID_URI = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ThemeName';

    /**
     * The default ttl of the cache persistence.
     */
    const CACHE_PERSISTENCE_DEFAULT_TTL = null;

    /**
     * The cache persistence config option.
     */
    const OPTION_CACHE_PERSISTENCE = 'cachePersistence';

    /**
     * The cache persistence ttl config option.
     */
    const OPTION_CACHE_PERSISTENCE_TTL = 'cachePersistenceTtl';

    /**
     * @var common_persistence_KvDriver
     */
    private $cachePersistence;

    /**
     * @var string
     */
    private $themeId = '';

    /**
     * Returns the delivery execution identifier.
     *
     * @return string
     */
    protected function getDeliveryExecutionId()
    {
        $request = \Context::getInstance()->getRequest();

        $deliveryExecutionId = '';
        if ($request->hasParameter('deliveryExecution')) {
            $deliveryExecutionId = \tao_helpers_Uri::decode(
                $request->getParameter('deliveryExecution')
            );
        } elseif ($request->hasParameter('testServiceCallId')) {
            $deliveryExecutionId = \tao_helpers_Uri::decode(
                $request->getParameter('testServiceCallId')
            );
        } elseif ($request->hasParameter('serviceCallId')) {
            $deliveryExecutionId = \tao_helpers_Uri::decode(
                $request->getParameter('serviceCallId')
            );
        }

        return $deliveryExecutionId;
    }

    /**
     * @inheritdoc
     */
    public function getThemeId()
    {
        $deliveryExecutionId = $this->getDeliveryExecutionId();

        if (empty($this->themeId)) {
            if (!empty($deliveryExecutionId)) {
                $deliveryId = $this->getDeliveryIdFromSession($deliveryExecutionId);
                if ($deliveryId !== false) {
                    $this->themeId = $this->getDeliveryThemeId($deliveryId);
                }
            }
        }

        return $this->themeId;
    }

    /**
     * Tells if the page has to be headless: without header and footer.
     *
     * @return bool|mixed
     */
    public function isHeadless()
    {
        return false;
    }

    /**
     * Returns the deliveryId from session.
     *
     * @param $deliveryExecutionId
     *
     * @return mixed
     */
    public function getDeliveryIdFromSession($deliveryExecutionId)
    {
        if (PHPSession::singleton()->hasAttribute(DeliveryExecution::getDeliveryIdSessionKey($deliveryExecutionId))) {
            return PHPSession::singleton()->getAttribute(DeliveryExecution::getDeliveryIdSessionKey($deliveryExecutionId));
        }

        return false;
    }

    /**
     * Returns the delivery theme id.
     *
     * @param $deliveryId
     *
     * @return string
     */
    public function getDeliveryThemeId($deliveryId)
    {
        $themeId = $this->getDeliveryThemeIdFromCache($deliveryId);
        if ($themeId === false) {
            $themeId = $this->getDeliveryThemeIdFromDb($deliveryId);
            $this->storeDeliveryThemeIdToCache($deliveryId, $themeId);
        }

        return $themeId;
    }

    /**
     * Returns the delivery theme id from cache or FALSE when it does not exist.
     *
     * @param $deliveryId
     *
     * @return bool|common_Serializable
     */
    public function getDeliveryThemeIdFromCache($deliveryId)
    {
        if ($this->getCachePersistence() === null) {
            return false;
        }

        return $this->getCachePersistence()->get($this->getCacheKey($deliveryId));
    }

    /**
     * Returns delivery theme id from database.
     *
     * @param $deliveryId
     *
     * @return string
     */
    public function getDeliveryThemeIdFromDb($deliveryId)
    {
        try {
            $delivery = new core_kernel_classes_Resource($deliveryId);
            $property = $delivery->getProperty(static::DELIVERY_THEME_ID_URI);

            return (string)$delivery->getOnePropertyValue($property);
        } catch (common_exception_Error $e) {
            return '';
        }
    }

    /**
     * Stores the delivery theme id to cache.
     *
     * @param $deliveryId
     * @param $themeId
     *
     * @return bool
     */
    public function storeDeliveryThemeIdToCache($deliveryId, $themeId)
    {
        try {
            if ($this->getCachePersistence() !== null) {
                return $this->getCachePersistence()->set($this->getCacheKey($deliveryId), $themeId, $this->getCacheTtl());
            }
        } catch (common_exception_NotImplemented $e) {
        }

        return false;
    }

    /**
     * Returns the cache key.
     *
     * @param $deliveryId
     *
     * @return string
     */
    public function getCacheKey($deliveryId)
    {
        return 'deliveryThemeId:' . $deliveryId;
    }

    /**
     * Returns the cache persistence.
     *
     * @return common_persistence_KvDriver
     */
    protected function getCachePersistence()
    {
        if (is_null($this->cachePersistence) && $this->hasOption(static::OPTION_CACHE_PERSISTENCE)) {
            $persistenceOption = $this->getOption(static::OPTION_CACHE_PERSISTENCE);
            $this->cachePersistence = (is_object($persistenceOption))
                ? $persistenceOption
                : common_persistence_KeyValuePersistence::getPersistence($persistenceOption);
        }

        return $this->cachePersistence;
    }

    /**
     * Returns the cache persistence's ttl.
     *
     * @return int|null
     */
    public function getCacheTtl()
    {
        if ($this->hasOption(static::OPTION_CACHE_PERSISTENCE_TTL)) {
            $cacheTtl = $this->getOption(static::OPTION_CACHE_PERSISTENCE_TTL);
            if (!is_null($cacheTtl)) {
                return $cacheTtl;
            }
        }

        return static::CACHE_PERSISTENCE_DEFAULT_TTL;
    }
}
