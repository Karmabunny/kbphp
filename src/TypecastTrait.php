<?php
namespace karmabunny\kb;

/**
 * Update trait for typecasting values.
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
    public static function getTypecast(): Typecast
    {
        static $casts = [];
        $casts[static::class] ??= new Typecast(static::class);
        return $casts[static::class];
    }


    /** @inheritdoc */
    public function update(iterable $config): void
    {
        $typecast = static::getTypecast();

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
