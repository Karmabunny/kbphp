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
 * @package karmabunny\kb
 */
abstract class CountryNames
{

    /**
     * alpha-2 => alpha-3
     */
    public const COUNTRY_CODES = [
        'AF' => 'AFG',
        'AX' => 'ALA',
        'AL' => 'ALB',
        'DZ' => 'DZA',
        'AS' => 'ASM',
        'AD' => 'AND',
        'AO' => 'AGO',
        'AI' => 'AIA',
        'AQ' => 'ATA',
        'AG' => 'ATG',
        'AR' => 'ARG',
        'AM' => 'ARM',
        'AW' => 'ABW',
        'AU' => 'AUS',
        'AT' => 'AUT',
        'AZ' => 'AZE',
        'BS' => 'BHS',
        'BH' => 'BHR',
        'BD' => 'BGD',
        'BB' => 'BRB',
        'BY' => 'BLR',
        'BE' => 'BEL',
        'BZ' => 'BLZ',
        'BJ' => 'BEN',
        'BM' => 'BMU',
        'BT' => 'BTN',
        'BO' => 'BOL',
        'BA' => 'BIH',
        'BW' => 'BWA',
        'BV' => 'BVT',
        'BR' => 'BRA',
        'IO' => 'IOT',
        'BN' => 'BRN',
        'BG' => 'BGR',
        'BF' => 'BFA',
        'BI' => 'BDI',
        'KH' => 'KHM',
        'CM' => 'CMR',
        'CA' => 'CAN',
        'CV' => 'CPV',
        'BQ' => 'BES',
        'KY' => 'CYM',
        'CF' => 'CAF',
        'TD' => 'TCD',
        'CL' => 'CHL',
        'CN' => 'CHN',
        'CX' => 'CXR',
        'CC' => 'CCK',
        'CO' => 'COL',
        'KM' => 'COM',
        'CG' => 'COG',
        'CK' => 'COK',
        'CR' => 'CRI',
        'CI' => 'CIV',
        'HR' => 'HRV',
        'CU' => 'CUB',
        'CW' => 'CUW',
        'CY' => 'CYP',
        'CZ' => 'CZE',
        'DK' => 'DNK',
        'DJ' => 'DJI',
        'DM' => 'DMA',
        'DO' => 'DOM',
        'CD' => 'COD',
        'EC' => 'ECU',
        'EG' => 'EGY',
        'SV' => 'SLV',
        'GQ' => 'GNQ',
        'ER' => 'ERI',
        'EE' => 'EST',
        'ET' => 'ETH',
        'FK' => 'FLK',
        'FO' => 'FRO',
        'FJ' => 'FJI',
        'FI' => 'FIN',
        'FR' => 'FRA',
        'GF' => 'GUF',
        'PF' => 'PYF',
        'TF' => 'ATF',
        'GA' => 'GAB',
        'GM' => 'GMB',
        'GE' => 'GEO',
        'DE' => 'DEU',
        'GH' => 'GHA',
        'GI' => 'GIB',
        'GR' => 'GRC',
        'GL' => 'GRL',
        'GD' => 'GRD',
        'GP' => 'GLP',
        'GU' => 'GUM',
        'GT' => 'GTM',
        'GG' => 'GGY',
        'GN' => 'GIN',
        'GW' => 'GNB',
        'GY' => 'GUY',
        'HT' => 'HTI',
        'HM' => 'HMD',
        'HN' => 'HND',
        'HK' => 'HKG',
        'HU' => 'HUN',
        'IS' => 'ISL',
        'IN' => 'IND',
        'ID' => 'IDN',
        'IR' => 'IRN',
        'IQ' => 'IRQ',
        'IE' => 'IRL',
        'IM' => 'IMN',
        'IL' => 'ISR',
        'IT' => 'ITA',
        'JM' => 'JAM',
        'JP' => 'JPN',
        'JE' => 'JEY',
        'JO' => 'JOR',
        'KZ' => 'KAZ',
        'KE' => 'KEN',
        'KI' => 'KIR',
        'KW' => 'KWT',
        'KG' => 'KGZ',
        'LA' => 'LAO',
        'LV' => 'LVA',
        'LB' => 'LBN',
        'LS' => 'LSO',
        'LR' => 'LBR',
        'LY' => 'LBY',
        'LI' => 'LIE',
        'LT' => 'LTU',
        'LU' => 'LUX',
        'MO' => 'MAC',
        'MK' => 'MKD',
        'MG' => 'MDG',
        'MW' => 'MWI',
        'MY' => 'MYS',
        'MV' => 'MDV',
        'ML' => 'MLI',
        'MT' => 'MLT',
        'MH' => 'MHL',
        'MQ' => 'MTQ',
        'MR' => 'MRT',
        'MU' => 'MUS',
        'YT' => 'MYT',
        'MX' => 'MEX',
        'FM' => 'FSM',
        'MD' => 'MDA',
        'MC' => 'MCO',
        'MN' => 'MNG',
        'ME' => 'MNE',
        'MS' => 'MSR',
        'MA' => 'MAR',
        'MZ' => 'MOZ',
        'MM' => 'MMR',
        'NA' => 'NAM',
        'NR' => 'NRU',
        'NP' => 'NPL',
        'NL' => 'NLD',
        'NC' => 'NCL',
        'NZ' => 'NZL',
        'NI' => 'NIC',
        'NE' => 'NER',
        'NG' => 'NGA',
        'NU' => 'NIU',
        'NF' => 'NFK',
        'KP' => 'PRK',
        'MP' => 'MNP',
        'NO' => 'NOR',
        'OM' => 'OMN',
        'PK' => 'PAK',
        'PW' => 'PLW',
        'PS' => 'PSE',
        'PA' => 'PAN',
        'PG' => 'PNG',
        'PY' => 'PRY',
        'PE' => 'PER',
        'PH' => 'PHL',
        'PN' => 'PCN',
        'PL' => 'POL',
        'PT' => 'PRT',
        'PR' => 'PRI',
        'QA' => 'QAT',
        'RE' => 'REU',
        'RO' => 'ROU',
        'RU' => 'RUS',
        'RW' => 'RWA',
        'BL' => 'BLM',
        'SH' => 'SHN',
        'KN' => 'KNA',
        'LC' => 'LCA',
        'MF' => 'MAF',
        'PM' => 'SPM',
        'VC' => 'VCT',
        'WS' => 'WSM',
        'SM' => 'SMR',
        'ST' => 'STP',
        'SA' => 'SAU',
        'SN' => 'SEN',
        'RS' => 'SRB',
        'SC' => 'SYC',
        'SL' => 'SLE',
        'SG' => 'SGP',
        'SX' => 'SXM',
        'SK' => 'SVK',
        'SI' => 'SVN',
        'SB' => 'SLB',
        'SO' => 'SOM',
        'ZA' => 'ZAF',
        'GS' => 'SGS',
        'KR' => 'KOR',
        'SS' => 'SSD',
        'ES' => 'ESP',
        'LK' => 'LKA',
        'SD' => 'SDN',
        'SR' => 'SUR',
        'SJ' => 'SJM',
        'SZ' => 'SWZ',
        'SE' => 'SWE',
        'CH' => 'CHE',
        'SY' => 'SYR',
        'TW' => 'TWN',
        'TJ' => 'TJK',
        'TZ' => 'TZA',
        'TH' => 'THA',
        'TL' => 'TLS',
        'TG' => 'TGO',
        'TK' => 'TKL',
        'TO' => 'TON',
        'TT' => 'TTO',
        'TN' => 'TUN',
        'TR' => 'TUR',
        'TM' => 'TKM',
        'TC' => 'TCA',
        'TV' => 'TUV',
        'UG' => 'UGA',
        'UA' => 'UKR',
        'AE' => 'ARE',
        'GB' => 'GBR',
        'US' => 'USA',
        'UM' => 'UMI',
        'UY' => 'URY',
        'UZ' => 'UZB',
        'VU' => 'VUT',
        'VA' => 'VAT',
        'VE' => 'VEN',
        'VN' => 'VNM',
        'VG' => 'VGB',
        'VI' => 'VIR',
        'WF' => 'WLF',
        'EH' => 'ESH',
        'YE' => 'YEM',
        'ZM' => 'ZMB',
        'ZW' => 'ZWE',
    ];


    /**
     *
     * @return string[]
     */
    public static function getAlpha2(): array
    {
        return array_keys(self::COUNTRY_CODES);
    }


    /**
     *
     * @return string[]
     */
    public static function getAlpha3(): array
    {
        return array_values(self::COUNTRY_CODES);
    }


    /**
     * Convert an alpha-2 to alpha-3.
     *
     * @param string $code alpha-2
     * @return string|null alpha-3
     */
    public static function getAlpha3From2(string $code)
    {
        if (strlen(trim($code)) == 3) return $code;
        return self::COUNTRY_CODES[$code] ?? null;
    }


    /**
     * Convert an alpha-3 to alpha-2.
     *
     * @param string $code alpha-3
     * @return string|null alpha-2
     */
    public static function getAlpha2From3(string $code)
    {
        if (strlen(trim($code)) == 2) return $code;

        static $alpha3_codes;

        if (!$alpha3_codes) {
            $alpha3_codes = array_flip(self::COUNTRY_CODES);
        }

        return $alpha3_codes[$code] ?? null;
    }


    /**
     * Get the country code for a given country name.
     *
     * @param string $country_name
     * @param string $language default 'en'
     * @return string|null
     */
    public static function getCountryCode(string $country_name, string $language = 'en')
    {
        if (strlen($language) != 2) return null;
        $language = strtoupper($language);
        $country_name = strtoupper($country_name);

        // Just on a whim, try the first 3 characters.
        $code = substr($country_name, 0, 3);

        // Except Romania.
        // They migrated to ROU but php-intl will still accept it.
        if ($code === 'ROM') return 'ROU';

        // If the names match, it must be right!
        $locale = Locale::getDisplayRegion('-' . $code, $language);
        if (strcasecmp($country_name, $locale) == 0) return $code;

        // Okay, try them all.
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
     * @param string $country_code Alpha-2 or alpha-3
     * @param string $language default 'en'
     * @return string|null
     */
    public static function getCountryName(string $country_code, string $language = 'en')
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
        if (strlen($language) != 2) return [];
        $language = strtoupper($language);

        $names = [];
        foreach (static::COUNTRY_CODES as $code) {
            $names[$code] = Locale::getDisplayRegion('-' . $code, $language);
        }
        return $names;
    }
}
