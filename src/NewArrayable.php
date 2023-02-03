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
interface NewArrayable extends Arrayable
{
}
