<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2023 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This object implements new arrayable behaviours (when using ArrayableTrait).
 *
 * Long ago when the ArrayableTrait was first written, the `fields()` method
 * wasn't thought through very well. But now much code is reliant on this
 * behaviour and will very likely break if we force a change how `fields()` is
 * interpreted.
 *
 * The challenge is that a major version bump requires all dependent code to
 * upgrade at the same time. Instead, implementing this interface means we can
 * mix-and-match behaviours without breaking existing code.
 *
 * ```
 * // Old behaviour:
 * // This returns all public fields _in addition_ to the virtual fields.
 * public function fields(): array
 * {
 *    return ['virtual' => fn() => 'ok'];
 * }
 *
 * // New behaviour:
 * // This returns _only_ the fields declared in this array. If they are false they are _not_ included.
 * public function fields(): array
 * {
 *   return [
 *     'id' => true,
 *     'code' => false,
 *     'virtual' => fn() => 'ok',
 *   ];
 * }
 * ```
 *
 * The upgrade path is simple. Implement this interface AFTER modifying the
 * `fields()` method like below. This will also future-proof objects still
 * using the old behaviour.
 *
 * ```
 * public function fields(): array
 * {
 *   // New arrayable includes all public fields by default.
 *   $fields = parent::fields();
 *   // Whatever additions/modifications you want.
 *   $fields['code'] = false;
 *   $fields['virtual'] = fn() => 'ok';
 *   return $field;
 * }
 * ```
 *
 * This is clear and explicit about what fields are included and not included.
 * The old behaviour will be removed entirely in a future version.
 *
 * @see ArrayableTrait
 * @package karmabunny\kb
 */
interface ArrayableFields extends Arrayable
{

    /**
     * Convert this object to an array.
     *
     * Specify a 'filter' to restrict the fields included in the output. This can only
     *
     * Specify an 'extra' to include additional fields, including those not
     * included in `fields()` and any virtual fields defined in `extraFields()`.
     *
     * @param string[]|null $filter
     * @param string[]|null $extra
     * @return array
     */
    public function toArray(?array $filter = null, ?array $extra = null): array;


    /**
     * These are fields for use in `toArray()`.
     *
     * By default this will include all public fields of the object.
     *
     * Given `'name' => true` a field will appear in the array output. Given
     * `false|null|0|unset|...` the field will not be present.
     *
     * Define a pair like `'name' => callback` create a 'virtual' field. This
     * can override existing properties or create whole new fields.
     *
     * Note, if not implementing the ArrayableFields interface, this method will
     * only define 'virtual' fields. That said, inheriting the parent fields
     * is safe and will future-proof the code.
     *
     * ```
     * // Inheriting fields from the parent:
     * $fields = parent::fields();
     * $fields['hide_this_field'] = false;
     * unset($fields['this_also_works']);
     * return $fields;
     *
     * // Declaring explicit fields, ignoring inherited:
     * return [
     *   'real_field' => true,
     *   'and_this_field' => true,
     * ];
     *
     * // Adding virtual fields:
     * $fields = parent::fields();
     * $fields['virtual_thing'] = [$this, 'getMyVirtualThing'];
     * $fields['inline_thing'] = function() {
     *    return 'hey look: ' . time();
     * };
     * return $fields;
     * ```
     *
     * @see ArrayableFields
     * @see ArrayableTrait
     * @return (callable|bool)[]
     */
    public function fields(): array;


    /**
     * Define extra fields that you _might_ want included in the `toArray()`
     * output.
     *
     * These are virtual fields that are not included by default like the
     * ones defined in `fields()`. Instead, use the second parameter of
     * the `toArray(null, ['name'])` method to specify which extra fields
     * you want in the output.
     *
     * A virtual field is defined as a pair like `'name' => callback`.
     *
     * @return callable[]
     */
    public function extraFields(): array;
}
