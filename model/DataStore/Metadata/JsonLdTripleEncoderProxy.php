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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\DataStore\Metadata;

use core_kernel_classes_Triple;
use oat\generis\model\data\Ontology;
use oat\tao\model\export\JsonLdTripleEncoderInterface;

class JsonLdTripleEncoderProxy implements JsonLdTripleEncoderInterface
{
    /** @var Ontology */
    private $ontology;

    /** @var JsonLdTripleEncoderInterface[] */
    private $encoders = [];

    public function __construct(Ontology $ontology)
    {
        $this->ontology = $ontology;
    }

    public function addEncoder(JsonLdTripleEncoderInterface $encoder): void
    {
        $this->encoders[] = $encoder;
    }

    public function encode(core_kernel_classes_Triple $triple, array $dataToEncode): array
    {
        //FIXME ============================= Remove after testing
        /**
         * - subject: https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i6189202811630158abd04ce58dc9ac7 (Item URI)
         * - predicate: https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i61891fd32890c1210487eecb1cd298b (Property URI)
         * - object: http://test.test.test/PARENT-CODE-1
         */
        $property = $this->ontology->getProperty($triple->predicate); // Schema Property

        $allowedProperties = [
            'https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i61891fd32890c1210487eecb1cd298b', // item remote
            'https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i61891ff4a51d2139433f1ba4e375526', // item local
        ];

        if (!in_array($property->getUri(), $allowedProperties)) {
            return $dataToEncode;
        }
        //FIXME ============================== Remove after testing

        foreach ($this->encoders as $encoder) {
            $dataToEncode = $encoder->encode($triple, $dataToEncode);
        }

        return $dataToEncode;
    }
}
