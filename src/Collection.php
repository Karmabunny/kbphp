<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use ReturnTypeWillChange;
use Serializable;
use Traversable;

/**
 * A collection is a defined model for an object.
 *
 * This is primarily to encourage stronger typing for blobs of data moving
 * within a system.
 *
 * Collection can be created from arrays with `new MyCollection([...])`. After
 * which they are safe to move around.
 *
 * Being objects, you can safely create functionality that relies on the data
 * inside them with methods or inheritance.
 *
 * Some useful utilities for collections:
 * - DocValidator
 * - RulesValidator
 * - NotSerializable
 *
 * Collections also provide:
 * - array access
 * - serialization
 * - iteration
 *
 * @package karmabunny\kb
 */
abstract class Collection extends DataObject implements
        ArrayAccess,
        IteratorAggregate,
        Serializable,
        JsonSerializable,
        Arrayable,
        Copyable,
        DirtyObjectInterface
{

    use ArrayAccessTrait;
    use ArrayableTrait;
    use SerializeTrait;
    use DirtyPropertiesTrait;


    /** @inheritdoc */
    public function getIterator(): Traversable
    {
        // @phpstan-ignore-next-line : docs say 'array or object'
        return new ArrayIterator($this);
    }


    /**
     * @deprecated Use clone instead.
     * @return static
     */
    public function copy()
    {
        $class = static::class;
        return new $class($this);
    }


    /** @inheritdoc */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
