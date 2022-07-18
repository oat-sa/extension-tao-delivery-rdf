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
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\test\unit\model\validation;

use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\validation\DeliveryValidatorFactory;
use tao_helpers_form_validators_AlphaNum;

class DeliveryValidatorFactoryTest extends TestCase
{
    /** @var DeliveryValidatorFactory */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new DeliveryValidatorFactory();
    }

    public function testCreateMultiple(): void
    {
        $validators = $this->subject->createMultiple();

        /** @var tao_helpers_form_validators_AlphaNum $validator1 */
        $validator1 = $validators['http_2_www_0_tao_0_lu_1_Ontologies_1_TAODelivery_0_rdf_3_AssessmentProjectId'][0];

        $this->assertInstanceOf(tao_helpers_form_validators_AlphaNum::class, $validator1);
        $this->assertTrue($validator1->getOptions()['allow_punctuation']);
    }
}
