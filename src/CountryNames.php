<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Locale;

/**
 * Country names, using the intl extension.
 *
 * @todo add support for alpha3 codes
 */
abstract class CountryNames
{

    public const COUNTRY_CODES = [
        'AF', 'AX', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AG', 'AR', 'AM', 'AW', 'AU', 'AT', 'AZ',
        'BS', 'BH', 'BD', 'BB', 'BY', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BA', 'BW', 'BV', 'BR',
        'IO', 'BN', 'BG', 'BF', 'BI', 'KH', 'CM', 'CA', 'CV', 'KY', 'CF', 'TD', 'CL', 'CN', 'CX', 'CC',
        'CO', 'KM', 'CG', 'CD', 'CK', 'CR', 'CI', 'HR', 'CU', 'CW', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO',
        'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'ET', 'FK', 'FO', 'FJ', 'FI', 'FR', 'GF', 'PF', 'TF', 'GA',
        'GM', 'GE', 'DE', 'GH', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT', 'GG', 'GN', 'GW', 'GY', 'HT',
        'HM', 'VA', 'HN', 'HK', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IM', 'IL', 'IT', 'JM', 'JP',
        'JE', 'JO', 'KZ', 'KE', 'KI', 'KP', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY', 'LI',
        'LT', 'LU', 'MO', 'MK', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX',
        'FM', 'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NL', 'NC', 'NZ', 'NI',
        'NE', 'NG', 'NU', 'NF', 'MP', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA', 'PG', 'PY', 'PE', 'PH', 'PN',
        'PL', 'PT', 'PR', 'QA', 'RE', 'RO', 'RU', 'RW', 'BL', 'SH', 'KN', 'LC', 'MF', 'PM', 'VC', 'WS',
        'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SX', 'SK', 'SI', 'SB', 'SO', 'ZA', 'GS', 'SS',
        'ES', 'LK', 'SD', 'SR', 'SJ', 'SZ', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG', 'TK',
        'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA', 'AE', 'GB', 'US', 'UM', 'UY', 'UZ', 'VU',
        'VE', 'VN', 'VG', 'VI', 'WF', 'EH', 'YE', 'ZM', 'ZW',
    ];

    /**
     * Get the country code for a given country name.
     *
     * @param string $country_name
     * @param string $language default 'en'
     * @return string
     */
    public static function getCountryCode(string $country_name, string $language = 'en'): string
    {
        if (strlen($language) != 2) return null;
        $language = strtoupper($language);

        foreach (static::COUNTRY_CODES as $code) {
            $locale = Locale::getDisplayRegion('-' . $code, $language);

            if (strcasecmp($country_name, $locale) == 0) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Get the country name for a given country code.
     *
     * @param string $country_code
     * @param string $language default 'en'
     * @return string
     */
    public static function getCountryName(string $country_code, string $language = 'en'): string
    {
        if (strlen($language) != 2) return null;
        $language = strtoupper($language);

        return Locale::getDisplayRegion('-' . $country_code, $language);
    }


    /**
     * Get a list of all the country names.
     *
     * @param string $language default 'en'
     * @return string[]
     */
    public static function getCountryNameList(string $language = 'en'): array
    {
        if (strlen($language) != 2) return null;
        $language = strtoupper($language);

        $names = [];
        foreach (static::COUNTRY_CODES as $code) {
            $names[$code] = Locale::getDisplayRegion('-' . $code, $language);
        }
        return $names;
    }
}
