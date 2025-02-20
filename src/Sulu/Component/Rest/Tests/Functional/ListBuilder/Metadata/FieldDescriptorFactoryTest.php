<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Functional\ListBuilder\Metadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCaseFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCountFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineGroupConcatFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineIdentityFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactory;
use Sulu\Component\Rest\ListBuilder\Metadata\ListXmlLoader;
use Sulu\Component\Rest\ListBuilder\Metadata\ProviderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class FieldDescriptorFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var string
     */
    private $configCachePath;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var ProviderInterface[]
     */
    private $chain;

    public function setup(): void
    {
        parent::setUp();

        $parameterBag = $this->prophesize(ParameterBagInterface::class);
        $parameterBag->resolveValue('%sulu.model.contact.class%')->willReturn('SuluContactBundle:Contact');
        $parameterBag->resolveValue('%sulu.model.contact.class%.avatar')->willReturn(
            'SuluContactBundle:Contact.avatar'
        );
        $parameterBag->resolveValue('%sulu.model.contact.class%.contactAddresses')->willReturn(
            'SuluContactBundle:Contact.contactAddresses'
        );
        $parameterBag->resolveValue(Argument::any())->willReturnArgument(0);

        $filesystem = new Filesystem();
        $this->configCachePath = __DIR__ . '/cache';
        if ($filesystem->exists($this->configCachePath)) {
            $filesystem->remove($this->configCachePath);
        }
        $filesystem->mkdir($this->configCachePath);

        $this->fieldDescriptorFactory = new FieldDescriptorFactory(
            new ListXmlLoader($parameterBag->reveal()),
            [__DIR__ . '/Resources'],
            $this->configCachePath,
            $this->debug
        );
    }

    public function testGetFieldDescriptors()
    {
        $fieldDescriptor = $this->fieldDescriptorFactory->getFieldDescriptors('complete');

        $expectedFieldDescriptors = ['extension', 'id', 'firstName', 'lastName', 'avatar', 'fullName', 'city'];
        $fieldDescriptorKeys = \array_keys($fieldDescriptor);

        $this->assertEquals(
            \asort($expectedFieldDescriptors),
            \asort($fieldDescriptorKeys)
        );

        $expected = [
            'id' => ['name' => 'id', 'translation' => 'public.id', 'disabled' => true, 'type' => 'integer'],
            'firstName' => ['name' => 'firstName', 'translation' => 'contact.contacts.firstName', 'default' => true],
            'lastName' => ['name' => 'lastName', 'translation' => 'contact.contacts.lastName', 'default' => true],
            'avatar' => [
                'name' => 'avatar',
                'translation' => 'public.avatar',
                'default' => true,
                'type' => 'thumbnails',
                'sortable' => false,
            ],
            'fullName' => [
                'instance' => DoctrineConcatenationFieldDescriptor::class,
                'name' => 'fullName',
                'translation' => 'public.name',
                'disabled' => true,
                'sortable' => false,
                'class' => 'test-class',
            ],
            'city' => ['name' => 'city', 'translation' => 'contact.address.city', 'default' => true],
            'extension' => ['name' => 'extension', 'translation' => 'extension.extension', 'default' => true],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorsMinimal()
    {
        $fieldDescriptor = $this->fieldDescriptorFactory->getFieldDescriptors('minimal');

        $this->assertEquals(['id', 'firstName', 'lastName'], \array_keys($fieldDescriptor));

        $expected = [
            'id' => ['name' => 'id', 'translation' => 'public.id', 'disabled' => true, 'type' => 'integer'],
            'firstName' => ['name' => 'firstName', 'translation' => 'contact.contacts.firstName', 'default' => true, 'width' => 'shrink'],
            'lastName' => ['name' => 'lastName', 'translation' => 'contact.contacts.lastName', 'default' => true],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorsGroupConcat()
    {
        $fieldDescriptor = $this->fieldDescriptorFactory->getFieldDescriptors('group-concat');

        $this->assertEquals(['tags'], \array_keys($fieldDescriptor));

        $expected = [
            'tags' => [
                'name' => 'tags',
                'translation' => 'Tags',
                'instance' => DoctrineGroupConcatFieldDescriptor::class,
                'disabled' => true,
                'width' => 'shrink',
            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorsCase()
    {
        $fieldDescriptor = $this->fieldDescriptorFactory->getFieldDescriptors('case');

        $this->assertEquals(['tag'], \array_keys($fieldDescriptor));

        $expected = [
            'tag' => [
                'name' => 'tag',
                'translation' => 'Tag',
                'instance' => DoctrineCaseFieldDescriptor::class,
                'disabled' => true,
                'width' => 'shrink',
            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);

        $this->assertEquals(
            '(CASE WHEN SuluTagBundle_Tag.name IS NOT NULL THEN SuluTagBundle_Tag.name ELSE SuluTagBundle_Tag.name END)',
            $fieldDescriptor['tag']->getSelect()
        );
    }

    public function testGetFieldDescriptorsIdentity()
    {
        $fieldDescriptor = $this->fieldDescriptorFactory->getFieldDescriptors('identity');

        $this->assertEquals(['tags'], \array_keys($fieldDescriptor));

        $expected = [
            'tags' => [
                'name' => 'tags',
                'translation' => 'Tags',
                'instance' => DoctrineIdentityFieldDescriptor::class,
                'disabled' => true,
                'width' => 'shrink',
            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorsOptions()
    {
        $fieldDescriptor = $this->fieldDescriptorFactory->getFieldDescriptors('options');

        $this->assertEquals(['city'], \array_keys($fieldDescriptor));

        $expected = [
            'city' => [
                'name' => 'city',
                'translation' => 'City',
                'disabled' => true,
                'joins' => [
                    'SuluContactBundle:ContactAddress' => [
                        'entity-name' => 'SuluContactBundle:ContactAddress',
                        'field-name' => 'SuluContactBundle_Contact.contactAddresses',
                        'method' => 'LEFT',
                        'condition' => 'SuluContactBundle_ContactAddress.locale = :locale',
                    ],
                    'user' => [
                        'entity-name' => 'user',
                        'field-name' => 'SuluSecurityBundle:User',
                        'method' => 'LEFT',
                        'condition' => 'user.idContacts = contact.id',
                    ],
                ],
            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorsCount()
    {
        $fieldDescriptor = $this->fieldDescriptorFactory->getFieldDescriptors('count');

        $this->assertEquals(['tags'], \array_keys($fieldDescriptor));

        $expected = [
            'tags' => [
                'name' => 'tags',
                'translation' => 'Tags',
                'instance' => DoctrineCountFieldDescriptor::class,
                'disabled' => true,
                'width' => 'shrink',
            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorsMixed()
    {
        $fieldDescriptor = $this->fieldDescriptorFactory->getFieldDescriptors('mixed');

        $this->assertCount(2, $fieldDescriptor);
        $this->assertFieldDescriptor(
            ['name' => 'id', 'translation' => 'Id', 'disabled' => true],
            $fieldDescriptor['id']
        );
        $this->assertFieldDescriptor(
            ['name' => 'name', 'translation' => 'Name', 'disabled' => true, 'instance' => FieldDescriptor::class],
            $fieldDescriptor['name']
        );
    }

    public function testGetFieldDescriptorsNotExisting()
    {
        $this->assertNull($this->fieldDescriptorFactory->getFieldDescriptors('not-existing'));
    }

    private function assertFieldDescriptors(array $expected, array $fieldDescriptors)
    {
        foreach ($expected as $name => $expectedData) {
            $this->assertFieldDescriptor($expectedData, $fieldDescriptors[$name]);
        }
    }

    private function assertFieldDescriptor(array $expected, FieldDescriptorInterface $fieldDescriptor)
    {
        $expected = \array_merge(
            [
                'instance' => DoctrineFieldDescriptor::class,
                'name' => null,
                'translation' => null,
                'disabled' => false,
                'default' => false,
                'type' => 'string',
                'sortable' => true,
                'width' => 'auto',
            ],
            $expected
        );

        $this->assertInstanceOf($expected['instance'], $fieldDescriptor);
        $this->assertEquals($expected['name'], $fieldDescriptor->getName());
        $this->assertEquals($expected['translation'], $fieldDescriptor->getTranslation());
        $this->assertEquals($expected['disabled'], $fieldDescriptor->getDisabled());
        $this->assertEquals($expected['default'], $fieldDescriptor->getDefault());
        $this->assertEquals($expected['type'], $fieldDescriptor->getType());
        $this->assertEquals($expected['sortable'], $fieldDescriptor->getSortable());
        $this->assertEquals($expected['width'], $fieldDescriptor->getWidth());

        if (\array_key_exists('joins', $expected)) {
            foreach ($expected['joins'] as $name => $joinExpected) {
                $this->assertJoin($joinExpected, $fieldDescriptor->getJoins()[$name]);
            }
        }
    }

    private function assertJoin(array $expected, DoctrineJoinDescriptor $join)
    {
        $expected = \array_merge(
            [
                'entity-name' => null,
                'field-name' => null,
                'method' => null,
                'condition' => null,
            ],
            $expected
        );

        $this->assertEquals($expected['entity-name'], $join->getEntityName());
        $this->assertEquals($expected['field-name'], $join->getJoin());
        $this->assertEquals($expected['method'], $join->getJoinMethod());
        $this->assertEquals($expected['condition'], $join->getJoinCondition());
    }
}
