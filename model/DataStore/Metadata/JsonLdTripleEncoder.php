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

use oat\tao\model\export\JsonLdTripleEncoderInterface;
use stdClass;

class JsonLdTripleEncoder implements JsonLdTripleEncoderInterface
{
    public function encode(stdClass $triple, array $dataToEncode): array
    {
        /*
         * - predicate: https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i61891fd32890c1210487eecb1cd298b
         * - subject: https://test-tao-deploy.docker.localhost/ontologies/tao.rdf#i6189202811630158abd04ce58dc9ac7
         * - object: http://test.test.test/PARENT-CODE-1
         */

        if ($triple->object !== 'http://test.test.test/PARENT-CODE-1') {
            return $dataToEncode; //@FIXME remove after testing...
        }

        return $dataToEncode;

        $triple->predicate;
        $triple->object;
        $triple->subject;
    }
}
