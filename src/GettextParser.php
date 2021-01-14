<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * GettextParser
 *
 * This will read and parse a gettext PO file.
 *
 * From this we construct a set of PoMessage files.
 *
 * If metadata is present (empty msgid) we use that for reading in the
 * plural rules for consumption by Intl.PluralRules.
 *
 * Note though this is a non-standard metadata that gettext doesn't
 * traditionally use. Gettext actually does something quite ugly:
 * => "Plural-Forms: nplurals=3; plural=n==1 ? 0 : n==2 ? 1 : 2;\n"
 *
 * See: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/PluralRules/select
 * See: https://en.wikipedia.org/wiki/Gettext#Plural_form
 *
 * @todo Make messages + translations serializable.
 * @todo Refactor to support string parsing.
 * @todo Use Loggable instead of error_log.
 *
 */
class GettextParser
{
    /**
     * The parsing regex pattern for PO files.
     * Something like: type[num] "string"
     */
    const MSG_RE = '/(?:(msgid|msgid_plural|msgstr)(?:\[(\d+)\])?\s+)?"([^"]*)"/';


    /**
     * For parsing gettext metadata.
     */
    const META_RE = '/([^:]+):\s+([^;]+);\\\\n/';


    /**
     * The actual language code isn't often what people know it as, more often
     * people know it by the country of origin. For example, JA is Japanese
     * from Japan (JP).
     */
    const META_COUNTRY_CODE = '/X-(?:Country-Code|Display-Name)/i';


    /**
     * These are our own rules for parsing plural types to play nice with
     * Intl.PluralRules.
     */
    const META_INTL_PLURALS = '/X-Intl-(Zero|One|Two|Few|Many|Other)/i';


    /**
     * Open, read, and parse a PO file.
     *
     * @param string $path
     * @param string $lang
     * @param bool $strict Optionally throw
     * @return PoTranslation
     */
    public static function read(string $path, string $lang, $strict = false): PoTranslation
    {
        $po = new PoTranslation($lang);
        $file = fopen($path, "r");

        $ids = [];

        while (!feof($file)) {

            // The "msgid" always comes first.
            $token = self::getline($file, 'msgid');
            if (!$token) {
                self::report($strict, 'Expected token "msgid".');
                continue;
            }

            $id = $token['value'];

            // Read extra (multiline) strings.
            for (;;) {
                $token = self::getline($file, 'multiline', true);
                if (!$token) break;
                $id .= $token['value'];
            }

            // Plurals are optional.
            $token = self::getline($file, 'msgid_plural', true);
            $plural = "";

            if ($token) {
                $plural = $token['value'];

                // Read extra (multiline) strings.
                for (;;) {
                    $token = self::getline($file, 'multiline', true);
                    if (!$token) break;

                    $plural .= $token['value'];
                }
            }

            // Get all the strings.
            $strings = [];

            for (;;) {
                $token = self::getline($file, 'msgstr', true);
                if (!$token) break;

                $str = $token['value'];
                $index = $token['index'];

                // Plurals _must_ be followed by an indexed msgstr.
                // TODO This required php 7.2 (PREG_UNMATCHED_AS_NULL)
                if ($index === null && !empty($plural)) {
                    self::report($strict,
                        '"msgid_plural" must be followed by "msgstr[]".');
                    break;
                }

                // Read extra (multiline) strings.
                for (;;) {
                    $token = self::getline($file, 'multiline', true);
                    if (!$token) break;

                    $str .= $token['value'];
                }

                // After all that we should have a string, right?
                if (!empty($str)) {
                    $index = $index ?: 0;

                    // Duplicate indexes are ordered by declaration.
                    while (isset($strings[$index])) $index++;
                    $strings[$index] = $str;
                }
            }

            // We must have _at least_ one string to build an entry.
            if (empty($strings)) {
                self::report($strict, 'There must be at least one "msgstr".');
                continue;
            }

            // Sort and convert to a indexed array.
            ksort($strings);
            $strings = array_merge($strings);

            // Test for duplicates.
            if (array_search($id, $ids) !== false) {
                self::report($strict, "Duplicate translation msgid \"{$id}\".");
                continue;
            }

            // Tack it on.
            $ids[] = $id;
            array_push($po->messages, new PoMessage($id, $plural, $strings));
        }

        fclose($file);

        $metadata = null;

        // Find metadata. This is the fist empty msgid.
        foreach ($po->messages as $i => $message) {
            if (!empty($message->id)) continue;
            if (empty($metadata)) {
                $metadata = $message->strings[0];
            }

            // Also clean out any empty messages.
            unset($po->messages[$i]);
        }

        if (!empty($metadata)) {

            // Match the metadata structure as a set of key-values.
            $matches = [];
            $count = preg_match_all(self::META_RE, $metadata, $matches, PREG_SET_ORDER);

            if ($count > 0) {
                foreach ($matches as $match) {
                    list($_, $key, $value) = $match;

                    // Friendly display name.
                    if (preg_match(self::META_COUNTRY_CODE, $key) !== 0) {
                        $po->name = $value;
                        continue;
                    }

                    $plurals = [];

                    // Plurals.
                    if (preg_match(self::META_INTL_PLURALS, $key, $plurals) !== 0) {
                        $plural_name = trim(strtolower($plurals[1]));
                        $po->plurals[$plural_name] = intval($value);
                        continue;
                    }
                }
            }
        }

        return $po;
    }


    /**
     * Read/parse a single line from the file.
     *
     * Skips over:
     * - empty
     * - a comment
     *
     * Returns false if the line is:
     * - non-matching (see MSG_RE)
     * - token isn't a $type (if provided)
     *
     * TODO Should this log/throw if the line is not matching.
     *
     * @param resource $file
     * @param string $type one of (msgid, msgid_plural, msgstr, multiline)
     * @param bool $peek rewind if the $type doesn't match
     * @return array|false
     */
    protected static function getline($file, $type = null, $peek = false)
    {
        for (;;) {
            // Read.
            $line = fgets($file);
            if ($line === false) return false;

            $size = strlen($line);
            $line = trim($line);

            // Skip over these.
            if (empty($line)) continue;
            if ($line[0] === "#") continue;

            // echo $peek ? "PEEK " : "READ ";
            // echo $type ?: "multiline";
            // echo " " . $line . "\n";

            $matches = [];

            // Parse.
            // TODO How do we find unmatched groups without PREG_UNMATCHED_AS_NULL?
            if (preg_match(self::MSG_RE, $line, $matches) !== 1) return false;

            $read_type = $matches[1] ?: 'multiline';
            $read_index = $matches[2];
            $read_value = $matches[3];

            // Skip un-matching types.
            if ($type !== null && $type !== $read_type) {
                // if ($peek) echo "REWIND ${size} \n";

                // rewind before we quit - if we're just peeking.
                if ($peek) fseek($file, -1 * $size, SEEK_CUR);

                return false;
            }

            if ($read_index !== null) {
                $read_index = intval($read_index);
            }

            return [
                'type' => $read_type,
                'value' => $read_value,
                'index' => $read_index,
            ];
        }
    }


    /**
     * Report or throw, depending on the strict-ness.
     */
    protected static function report(bool $strict, string $message)
    {
        error_log($message);

        if ($strict) throw new GettextParserError($message);
    }
}
