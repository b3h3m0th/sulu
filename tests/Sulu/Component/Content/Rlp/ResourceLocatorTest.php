<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Rlp;

use Doctrine\ORM\EntityManager;
use PHPCR\SessionInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\PHPCR\PathCleanup;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class ResourceLocatorTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var RlpMapperInterface
     */
    private $rlpMapper;

    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    protected function setUp()
    {
        $this->purgeDatabase();
        $this->initOrm();
        $this->initPhpcr();

        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->session = $this->sessionManager->getSession();
        $this->rlpMapper = new PhpcrMapper($this->getContainer()->get('sulu.phpcr.session'));
        $this->strategy = new TreeStrategy($this->rlpMapper, new PathCleanup());

        $this->resourceLocator = new ResourceLocator($this->strategy, 'not-in-use');
    }

    protected function initOrm()
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function testWrite()
    {
        $property = new Property('url', array(), 'resource_locator');
        $property->setValue('/test');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $this->session->save();

        $this->resourceLocator->write($node, $property, 1, 'sulu_io', 'en');

        $this->assertEquals('/test', $node->getPropertyValue('url'));
    }

    public function testLoadFromProperty()
    {
        $property = new Property('url', array(), 'resource_locator');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $node->setProperty($property->getName(), '/test');
        $this->session->save();

        $this->resourceLocator->read($node, $property, 1, 'sulu_io', 'en');

        $this->assertEquals('/test', $property->getValue());
    }

    public function testLoadFromNode()
    {
        $property = new Property('url', array(), 'resource_locator');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $this->session->save();

        $this->rlpMapper->save($node, '/test', 'sulu_io', 'en');
        $this->session->save();

        $this->resourceLocator->read($node, $property, 'sulu_io', 'en');

        $this->assertEquals('/test', $property->getValue());
        $this->assertEquals('/test', $node->getPropertyValue('url'));
    }
}
