<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\SchemaMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

class PropertyMetadataTest extends TestCase
{
    public function provideGetter()
    {
        return [
            ['title', true],
            ['article', false],
        ];
    }

    /**
     * @dataProvider provideGetter
     */
    public function testGetter($name, $mandatory)
    {
        $property = new PropertyMetadata($name, $mandatory);
        $this->assertEquals($name, $property->getName());
        $this->assertEquals($mandatory, $property->isMandatory());
    }

    public function provideToJsonSchema()
    {
        return [
            ['title', false, null, null],
            ['article', false, new StringMetadata(), ['name' => 'article', 'type' => 'string']],
            ['article', true, new SchemaMetadata(), ['name' => 'article', 'required' => []]],
        ];
    }

    /**
     * @dataProvider provideToJsonSchema
     */
    public function testToJsonSchema($name, $mandatory, $schemaMetadata, $expectedSchema)
    {
        $property = new PropertyMetadata($name, $mandatory, $schemaMetadata);
        $jsonSchema = $property->toJsonSchema();

        $this->assertEquals($jsonSchema, $expectedSchema);
    }
}
