<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Simple;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\Cache\Traits\DoctrineTrait;

class DoctrineCache extends AbstractCache
{
    use DoctrineTrait;

    /**
     * @param string $namespace
     * @param int    $defaultLifetime
     */
    public function __construct(Cache $provider, $namespace = '', $defaultLifetime = 0)
    {
        parent::__construct('', $defaultLifetime);
        $this->provider = $provider;

        if ($this->provider instanceof CacheProvider) {
            $provider->setNamespace($namespace);
        }
    }

    /**
     * @return Cache
     */
    public function getDoctrineProvider()
    {
        return $this->provider;
    }
}
