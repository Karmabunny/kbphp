<?php
/**
* @link      https://github.com/Karmabunny
* @copyright Copyright (c) 2022 Karmabunny
*/

namespace karmabunny\kb;

/**
 * A set of helper to create virtual helpers.
 *
 * Use this in combination with {@see UpdateVirtualTrait}. These helpers create
 * methods that marshall array data into a configurable class. This helps
 * ensure the types are correct and object are not parsed twice.
 *
 * Example:
 * ```
 * public User $user;
 * public Site[] $sites;
 *
 * public function virtual()
 * {
 *    return [
 *      'user' => $this->setVirtual('user', User::class),
 *      'sites' => $this->setVirtualArray('sites', Site::class),
 *    ];
 * }
 * ```
 *
 * @package karmabunny\kb
 */
trait VirtualHelperTrait
{

    /**
     * Create a virtual setter for a single object.
     *
     * @param string $key
     * @param string $class
     * @return callable (value) => void
     */
    protected function setVirtual(string $key, string $class)
    {
        return function($value) use ($key, $class) {
            if (empty($value)) {
                $this->$key = null;
            }
            else {
                $this->$key = Configure::configure([$class => $value]);
            }
        };
    }


    /**
     * Create a virtual setter for an array of objects.
     *
     * @param string $key
     * @param string $class
     * @return callable (array) => void
     */
    protected function setVirtualArray(string $key, string $class)
    {
        return function($values) use ($key, $class) {
            $values = $values ?? [];

            foreach ($values as $value) {
                $this->$key[] = Configure::configure([$class => $value]);
            }
        };
    }
}
