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

use oat\taoDeliveryRdf\install\RegisterDeliveryFactoryService;
use oat\taoDeliveryRdf\scripts\install\OverrideRuntime;
use oat\taoDeliveryRdf\scripts\install\RegisterDeliveryAssemblyWrapperService;
use oat\taoDeliveryRdf\scripts\install\SetUpQueueTasks;
use oat\taoDeliveryRdf\scripts\install\RegisterFileSystem;

return [
  'name'        => 'taoDeliveryRdf',
  'label'       => 'Delivery Management',
  'description' => 'Manages deliveries using the ontology',
  'license'     => 'GPL-2.0',
  'version'     => '12.2.1',
    'author'      => 'Open Assessment Technologies SA',
    'requires'    => [
        'generis'     => '>=12.32.1',
        'tao'         => '>=38.3.1',
        'taoGroups'   => '>=4.0.0',
        'taoTests'    => '>=12.1.0',
        'taoQtiTest'  => '>=35.4.0',
        'taoDelivery' => '>=13.3.0'
    ],
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoDeliveryRdfManager',
    'acl' => [
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoDeliveryRdfManager', ['ext' => 'taoDeliveryRdf']],
        ['grant', \oat\tao\model\user\TaoRoles::REST_PUBLISHER, ['ext' => 'taoDeliveryRdf', 'mod' => 'RestDelivery']],
        ['grant', \oat\tao\model\user\TaoRoles::REST_PUBLISHER, ['ext' => 'taoDeliveryRdf', 'mod' => 'RestTest']],
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole','oat\taoDeliveryRdf\controller\Guest@guest'],
    ],
    'install' => [
        'rdf' => [
            __DIR__ . DIRECTORY_SEPARATOR . "install" . DIRECTORY_SEPARATOR . 'ontology' . DIRECTORY_SEPARATOR . 'taodelivery.rdf',
            __DIR__ . DIRECTORY_SEPARATOR . "install" . DIRECTORY_SEPARATOR . 'ontology' . DIRECTORY_SEPARATOR . 'widgetdefinitions.rdf'
        ],
        'php' => [
            __DIR__ . DIRECTORY_SEPARATOR . "install" . DIRECTORY_SEPARATOR . 'registerAssignment.php',
            'oat\\taoDeliveryRdf\\install\\RegisterDeliveryContainerService',
            'oat\\taoDeliveryRdf\\scripts\\RegisterEvents',
            RegisterDeliveryFactoryService::class,
            OverrideRuntime::class,
            SetUpQueueTasks::class,
            RegisterDeliveryAssemblyWrapperService::class,
            RegisterFileSystem::class
        ]
    ],
    //'uninstall' => array(),
    'update' => 'oat\\taoDeliveryRdf\\scripts\\update\\Updater',
    'routes' => [
        '/taoDeliveryRdf' => 'oat\\taoDeliveryRdf\\controller'
    ],
    'constants' => [
        # views directory
        "DIR_VIEWS" => dirname(__FILE__) . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoDeliveryRdf/',
    ],
    'extra' => [
        'structures' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ]
];
