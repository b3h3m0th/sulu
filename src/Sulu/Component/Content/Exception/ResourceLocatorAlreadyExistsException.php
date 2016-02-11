<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

use Exception;

class ResourceLocatorAlreadyExistsException extends Exception
{
    /**
     * @var string
     */
    private $resourceLocator;

    /**
     * @var string
     */
    private $path;

    public function __construct($resourceLocator, $path)
    {
        $this->resourceLocator = $resourceLocator;
        $this->path = $path;
        parent::__construct(
            sprintf(
                'Resource locator "%s" already exists. Route node at path "%s"',
                $this->resourceLocator,
                $this->path
            ),
            1103
        );
    }

    /**
     * Returns the resource locator that already existed.
     *
     * @return string
     */
    public function getResourceLocator()
    {
        return $this->resourceLocator;
    }

    /**
     * Returns the path of the route node already holding the existing resource locator.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
