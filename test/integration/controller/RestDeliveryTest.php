<?php

namespace oat\taoDeliveryRdf\test\integration\controller;

use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\test\integration\RestTestRunner;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

class RestDeliveryTest extends RestTestRunner
{
    use OntologyAwareTrait;

    /** @var DeliveryAssemblyService */
    protected $deliveryService;
    /** @var \taoQtiTest_models_classes_QtiTestService */
    protected $testService;
    /** @var \taoItems_models_classes_ItemsService */
    protected $itemService;

    /**
     * Skip test if taoQtiTest is not installed
     * Init service
     */
    public function setUp()
    {
        parent::setUp();

        if (!\common_ext_ExtensionsManager::singleton()->isInstalled('taoQtiTest')) {
            $this->markTestSkipped('taoQtiTest extension is not available.');
        }

        $this->deliveryService = DeliveryAssemblyService::singleton();
        $this->testService     = \taoQtiTest_models_classes_QtiTestService::singleton();
        $this->itemService     = \taoItems_models_classes_ItemsService::singleton();
    }

    /**
     * Create a test from qti package
     * @return string $testUri
     */
    protected function initDeliveryGeneration()
    {
        $qtiModel     = new \core_kernel_classes_Class(\taoQtiTest_models_classes_QtiTestService::INSTANCE_TEST_MODEL_QTI);
        $packagePath  = __DIR__ . '/../samples/package/package-basic.zip';

        $uri = '';

        /** @var \common_report_Report $response */
        $report = $this->testService->importMultipleTests($qtiModel, $packagePath);

        if ($report->getType() != \common_report_Report::TYPE_SUCCESS) {
            $this->fail('Unable to create fixture test');
        }

        /** @var \common_report_Report $subReport */
        foreach($report as $subReport) {
            $test = $subReport->getData();
            $uri = $test->rdfsResource->getUri();
            if (!empty($uri)) {
                break;
            }
        }

        if (empty($uri)) {
            $this->fail('Unable to create fixture test: id not found');
        }

        return $uri;
    }

    /**
     * Curl RestDelivery/generate with $testUri
     * @param bool $testUri
     * @return mixed
     */
    public function curlDeliveryGenerate($testUri=false)
    {
        if ($testUri===false) {
            $post_data = ['test' => $this->initDeliveryGeneration()];
        } elseif ($testUri=='') {
            $post_data = [];
        } else {
            $post_data = ['test' => $testUri];
        }

        $url = $this->host . 'taoDeliveryRdf/RestDelivery/generate';
        $return = $this->curl($url, CURLOPT_POST, 'data', array(CURLOPT_POSTFIELDS => $post_data));

        return json_decode($return, true);
    }

    /**
     * Test correct response
     */
    public function testGenerate()
    {
        $testUri = $this->initDeliveryGeneration();
        $data = $this->curlDeliveryGenerate($testUri);

        $this->assertTrue(is_array($data));

        $this->assertTrue(isset($data['success']));
        $this->assertTrue($data['success']);

        $this->assertTrue(isset($data['data']['delivery']));
        $delivery = new \core_kernel_classes_Resource($data['data']['delivery']);
        $this->assertTrue($delivery->exists());

        $this->removeDeliveryTest($delivery, $testUri);
    }

    /**
     * @todo fix failed test case. The actual error message is "Unexpected error. Please contact administrator"
     * 
     * Test Wrong uri
     * @dataProvider wrongUriProvider
     */
    public function testWrongTestUriGenerate($uri, $errorMsg)
    {
        $data = $this->curlDeliveryGenerate((string) $uri);

        $this->assertTrue(isset($data['success']));
        $this->assertFalse($data['success']);

        $this->assertEquals($errorMsg, $data['errorMsg']);
    }

    /**
     * Provides not existing uri & empty uri
     * @return array
     */
    public function wrongUriProvider()
    {
        return [
            ['wrongUri', 'Unable to find a test associated to the given uri.'],
            ['', 'At least one mandatory parameter was required but found missing in your request'],
        ];
    }

    /**
     * Delete items, test & delivery
     *
     * @param \core_kernel_classes_Resource $delivery
     * @param $testUri
     */
    public function removeDeliveryTest(\core_kernel_classes_Resource $delivery, $testUri)
    {
        $test  = new \core_kernel_classes_Resource($testUri);
        $items = $this->testService->getItems($test);

        /** @var \core_kernel_classes_Resource $item */
        foreach ($items as $item) {
            // Delete resource & files
            $this->itemService->deleteResource($item);
            $this->assertFalse($item->exists());
        }

        $this->testService->deleteContent($test);
        $this->testService->deleteResource($test);
        $this->assertFalse($test->exists());

        $this->deliveryService->deleteInstance($delivery);
        $this->assertFalse($delivery->exists());
    }

    public function curlCreateClass($label = false, $comment = false, $parentUri = false)
    {
        $data = [];
        if ($label !== false) {
            $data['delivery-label'] = $label;
        }

        if ($comment !== false) {
            $data['delivery-comment'] = $comment;
        }

        if ($parentUri !== false) {
            $data['delivery-parent'] = $parentUri;
        }

        $url = $this->host . 'taoDeliveryRdf/RestDelivery/createClass';
        $return = $this->curl($url, CURLOPT_POST, 'data', array(CURLOPT_POSTFIELDS => $data));

        return json_decode($return, true);
    }

    public function testCreateClass()
    {
        $label = 'fixture';
        $comment = 'commentFixture';
        $deliveryParent = (new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI))->createSubClass();

        $data = $this->curlCreateClass($label, $comment, $deliveryParent->getUri());

        $this->assertTrue(is_array($data));
        $this->assertTrue(isset($data['success']));
        $this->assertTrue($data['success']);

        $this->assertTrue(isset($data['data']));
        $this->assertTrue(isset($data['data']['delivery-uri']));


        $deliveryClass = new \core_kernel_classes_Class($data['data']['delivery-uri']);
        $this->assertTrue($deliveryClass->exists());
        $this->assertEquals($label, $deliveryClass->getLabel());
        $this->assertEquals($comment, $deliveryClass->getComment());

        $parent = $deliveryClass->getParentClasses();
        $this->assertEquals($deliveryParent->getUri(), current($parent)->getUri());

        $deliveryClass->delete();
        $deliveryParent->delete();
    }

    public function testCreateSubClass()
    {
        $label = 'fixture';
        $comment = 'commentFixture';
        $deliveryParent1 = (new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI))->createSubClass();
        $deliveryParent = $deliveryParent1->createSubClass();

        $data = $this->curlCreateClass($label, $comment, $deliveryParent->getUri());

        $this->assertTrue(is_array($data));
        $this->assertTrue(isset($data['success']));
        $this->assertTrue($data['success']);

        $this->assertTrue(isset($data['data']));
        $this->assertTrue(isset($data['data']['delivery-uri']));


        $deliveryClass = new \core_kernel_classes_Class($data['data']['delivery-uri']);
        $this->assertTrue($deliveryClass->exists());
        $this->assertEquals($label, $deliveryClass->getLabel());
        $this->assertEquals($comment, $deliveryClass->getComment());

        $parent = $deliveryClass->getParentClasses();
        $this->assertEquals($deliveryParent->getUri(), current($parent)->getUri());

        $deliveryClass->delete();
        $deliveryParent->delete();
        $deliveryParent1->delete();
    }

    public function testCreateClassWithoutLabel()
    {
        $data = $this->curlCreateClass();

        $this->assertTrue(is_array($data));
        $this->assertTrue(isset($data['success']));
        $this->assertFalse($data['success']);

        $this->assertTrue(isset($data['errorMsg']));
        $this->assertEquals('At least one mandatory parameter was required but found missing in your request', $data['errorMsg']);
    }

    public function testCreateClassWithoutComment()
    {
        $label = 'fixture';
        $data = $this->curlCreateClass($label);

        $this->assertTrue(is_array($data));
        $this->assertTrue(isset($data['success']));
        $this->assertTrue($data['success']);

        $this->assertTrue(isset($data['data']));
        $this->assertTrue(isset($data['data']['delivery-uri']));


        $deliveryClass = new \core_kernel_classes_Class($data['data']['delivery-uri']);
        $this->assertTrue($deliveryClass->exists());
        $this->assertEquals($label, $deliveryClass->getLabel());
        $this->assertEmpty($deliveryClass->getComment());

        $deliveryClass->delete();
    }

    // @todo fix failed test case. The actual error message is "Unexpected error. Please contact administrator"
    public function testCreateClassWithoutWrongParentUri()
    {
        $label = 'fixture';
        $data = $this->curlCreateClass($label, false, 'wrongUri');

        $this->assertTrue(is_array($data));
        $this->assertTrue(isset($data['success']));
        $this->assertFalse($data['success']);

        $this->assertTrue(isset($data['errorMsg']));
        $this->assertEquals('Class uri provided is not a valid class.', $data['errorMsg']);
    }

    protected function getUserData()
    {
        $data = parent::getUserData();
        $data[GenerisRdf::PROPERTY_USER_PASSWORD] = '12345Admin@@@';
        return $data;
    }

}
