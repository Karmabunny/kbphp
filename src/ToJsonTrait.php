<?php
namespace karmabunny\kb;

use ReturnTypeWillChange;

/**
 * JSON builders for models.
 *
 * This builds on the 'Arrayable' interface.
 */
trait ToJsonTrait
{
    use ArrayableTrait;


    /**
     * Specify field names for the JSON model here.
     *
     * @return array
     */
    public function jsonFields(): array
    {
        return [];
    }


    /**
     * Convert the model to a JSON array.
     *
     * Optionally specify a list of 'extra' fields to include on top of the
     * base fields described in {@see jsonFields()}.
     *
     * @param string[] $extras
     * @return array
     */
    public function toJson(?array $extras = null): array
    {
        // Insert the base fields.
        $fields = $this->jsonFields();
        $data = $this->toArray($fields);

        // Add extra fields.
        if ($extras) {
            $fields = $this->fields();

            foreach ($extras as $field) {
                $item = $fields[$field] ?? null;

                // It's a virtual field, process that.
                if ($item) {
                    // Call it.
                    if (is_callable($item)) {
                        $item = $item();
                    }

                    // Prevent recursion.
                    if (
                        $item === $this
                        or (is_array($item) and ($item[0] ?? null) === $this)
                    ) {
                        continue;
                    }

                    $data[$field] = $item;
                    continue;
                }

                // Maybe it's a regular field.
                if (isset($this->$field)) {
                    $data[$field] = $this->$field;
                    continue;
                }
            }
        }

        return $data;
    }


    /**
     * A default JSON serialiser. No 'extras' I'm afraid.
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toJson();
    }
}
