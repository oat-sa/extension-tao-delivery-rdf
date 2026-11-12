<?php

namespace oat\taoDeliveryRdf\test\unit\model;

use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\taoGroups\models\GroupsService;
use PHPUnit\Framework\TestCase;

class GroupAssignmentTest extends TestCase
{
    public function testGetAssignedUsersPassesGroupUriToGetUsers(): void
    {
        $groupUri = 'http://example.org/group';
        $group = $this->createMock(core_kernel_classes_Resource::class);
        $group->method('getUri')->willReturn($groupUri);
        $group->method('__toString')->willReturn($groupUri . "\nGroup label");

        $groupClass = $this->createMock(core_kernel_classes_Class::class);
        $groupClass->method('searchInstances')->willReturn([$group]);

        $groupsService = $this->createMock(GroupsService::class);
        $groupsService->method('getRootClass')->willReturn($groupClass);
        $groupsService->expects($this->once())
            ->method('getUsers')
            ->with($this->identicalTo($groupUri))
            ->willReturn([]);

        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturn($groupsService);

        $originalServiceManager = ServiceManager::getServiceManager();
        ServiceManager::setServiceManager($serviceManager);

        try {
            (new GroupAssignment())->getAssignedUsers('http://example.org/delivery');
        } finally {
            ServiceManager::setServiceManager($originalServiceManager);
        }
    }
}
