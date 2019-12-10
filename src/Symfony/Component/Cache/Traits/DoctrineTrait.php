<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Traits;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\Cache\MultiGetCache;
use Doctrine\Common\Cache\MultiPutCache;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
trait DoctrineTrait
{
    /**
     * @var Cache
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        parent::reset();

        if ($this->provider instanceof CacheProvider) {
            $this->provider->setNamespace($this->provider->getNamespace());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch(array $ids)
    {
        $unserializeCallbackHandler = ini_set('unserialize_callback_func', parent::class.'::handleUnserializeCallback');
        try {
            if ($this->provider instanceof MultiGetCache) {
                return $this->provider->fetchMultiple($ids);
            }

            return array_filter(array_map([$this->provider, 'doFetch'], $ids));
        } catch (\Error $e) {
            $trace = $e->getTrace();

            if (isset($trace[0]['function']) && !isset($trace[0]['class'])) {
                switch ($trace[0]['function']) {
                    case 'unserialize':
                    case 'apcu_fetch':
                    case 'apc_fetch':
                        throw new \ErrorException($e->getMessage(), $e->getCode(), E_ERROR, $e->getFile(), $e->getLine());
                }
            }

            throw $e;
        } finally {
            ini_set('unserialize_callback_func', $unserializeCallbackHandler);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doHave($id)
    {
        return $this->provider->contains($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doClear($namespace)
    {
        if ($this->provider instanceof ClearableCache) {
            return $this->provider->deleteAll();
        }

        if ($this->provider instanceof FlushableCache) {
            return $this->provider->flushAll();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete(array $ids)
    {
        $ok = true;
        foreach ($ids as $id) {
            $ok = $this->provider->delete($id) && $ok;
        }

        return $ok;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave(array $values, $lifetime)
    {
        if ($this->provider instanceof MultiPutCache) {
            return $this->provider->saveMultiple($values, $lifetime);
        }

        $ok = true;
        foreach ($values as $key => $value) {
            $ok = $this->provider->save($key, $value, $lifetime) && $ok;
        }

        return $ok;
    }
}
