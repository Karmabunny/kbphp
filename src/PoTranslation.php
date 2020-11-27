<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This is a collection of PoMessages. Mostly it's just a container and
 * utility to convert to a JSON friendly format.
 */
class PoTranslation implements Arrayable
{
    /** @var string */
    public $lang;

    /** @var string */
    public $name;

    /** @var PoMessage[] */
    public $messages = [];

    /** @var array */
    public $plurals = [];

    public function __construct(string $lang) {
        $this->lang = $lang;
        $this->name = $lang;
    }

    /**
     * Convert messages to a JSON friendly hash map.
     * - msgid -> [string, ...]
     * - msgid_plural -> msgid
     *
     * With this structure, a gettext can convert ids to their appropriate
     * string types. Plural forms are mapped to ids and then converted to the
     * appropriate string types.
     *
     * @param array|null $fields ignored
     * @return array
     */
    public function toArray(array $fields = null): array
    {
        $tr = [];

        foreach ($this->messages as $message) {
            $tr[$message->id] = $message->strings;

            // Include plural->id mapping.
            if (!empty($message->plural)) {
                $tr[$message->plural] = $message->id;
            }
        }

        return [
            'lang' => $this->lang,
            'name' => $this->name,
            'plurals' => $this->plurals,
            'tr' => $tr,
        ];
    }
}
