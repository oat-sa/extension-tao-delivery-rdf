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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */


use oat\taoDeliveryRdf\install\RegisterDeliveryContainerService;
use oat\taoDeliveryRdf\install\RegisterDeliveryFactoryService;
use oat\taoDeliveryRdf\scripts\RegisterEvents;
use oat\taoDeliveryRdf\scripts\install\OverrideRuntime;
use oat\taoDeliveryRdf\scripts\install\RegisterDeliveryAssemblyWrapperService;
use oat\taoDeliveryRdf\scripts\install\RegisterFileSystem;
use oat\taoDeliveryRdf\scripts\install\SetUpQueueTasks;
use oat\taoDeliveryRdf\scripts\update\Updater;
use oat\tao\model\user\TaoRoles;

return [
  'name'        => 'taoDeliveryRdf',
  'label'       => 'Delivery Management',
  'description' => 'Manages deliveries using the ontology',
  'license'     => 'GPL-2.0',
  'version'     => '13.0.0',
    'author'      => 'Open Assessment Technologies SA',
    'requires'    => [
        'generis'     => '>=12.32.1',
        'tao'         => '>=45.0.0',
        'taoGroups'   => '>=4.0.0',
        'taoTests'    => '>=12.1.0',
        'taoQtiTest'  => '>=35.4.0',
        'taoDelivery' => '>=13.3.0'
    ],
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoDeliveryRdfManager',
    'acl' => [
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoDeliveryRdfManager', ['ext' => 'taoDeliveryRdf']],
        ['grant', TaoRoles::REST_PUBLISHER, ['ext' => 'taoDeliveryRdf', 'mod' => 'RestDelivery']],
        ['grant', TaoRoles::REST_PUBLISHER, ['ext' => 'taoDeliveryRdf', 'mod' => 'RestTest']],
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole','oat\taoDeliveryRdf\controller\Guest@guest'],
    ],
    'install' => [
        'rdf' => [
            __DIR__ . DIRECTORY_SEPARATOR . "install" . DIRECTORY_SEPARATOR . 'ontology' . DIRECTORY_SEPARATOR . 'taodelivery.rdf',
            __DIR__ . DIRECTORY_SEPARATOR . "install" . DIRECTORY_SEPARATOR . 'ontology' . DIRECTORY_SEPARATOR . 'widgetdefinitions.rdf'
        ],
        'php' => [
            __DIR__ . DIRECTORY_SEPARATOR . "install" . DIRECTORY_SEPARATOR . 'registerAssignment.php',
            RegisterDeliveryContainerService::class,
            RegisterEvents::class,
            RegisterDeliveryFactoryService::class,
            OverrideRuntime::class,
            SetUpQueueTasks::class,
            RegisterDeliveryAssemblyWrapperService::class,
            RegisterFileSystem::class
        ]
    ],
    //'uninstall' => array(),
    'update' => Updater::class,
    'routes' => [
        '/taoDeliveryRdf' => 'oat\\taoDeliveryRdf\\controller'
    ],
    'constants' => [
        # views directory
        "DIR_VIEWS" => __DIR__ . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoDeliveryRdf/',
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ]
];
