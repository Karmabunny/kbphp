<?php
namespace karmabunny\kb;

/**
 * Update trait for typecasting values.
 *
 * This will convert between scalar types and apply any {@see Cast} attributes.
 *
 * @package karmabunny\kb
 */
trait TypecastTrait
{

    /**
     * The typecast instance for this class type.
     *
     * @return Typecast
     */
    public function getTypecast(): Typecast
    {
        static $instance;
        return $instance ??= new Typecast($this);
    }


    /** @inheritdoc */
    public function update(iterable $config): void
    {
        $typecast = $this->getTypecast();

        foreach ($config as $key => $item) {
            if (!property_exists($this, $key)) {
                continue;
            }

            if ($typecast->cast($key, $item)) {
                $this->$key = $item;
            }
        }
    }
}
