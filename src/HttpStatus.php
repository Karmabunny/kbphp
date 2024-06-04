<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Bunch of status codes.
 *
 * @package karmabunny\kb
 */
class HttpStatus
{

    /**  */
    const CONTINUE = 100;

    /**  */
    const SWITCHING_PROTOCOLS = 101;


    // Success //

    /** Good. */
    const OK = 200;

    /** Created a new resource. */
    const CREATED = 201;

    /** Accepted for processing, but not complete. */
    const ACCEPTED = 202;

    /** No body. */
    const NO_CONTENT = 204;

    /** No content + reset (please). */
    const RESET_CONTENT = 205;

    /** Only part of the result due to a range header. */
    const PARTIAL_CONTENT = 206;


    // Redirection //

    /** Permanently moved. Use this for GET -> GET. */
    const MULTIPLE_CHOICES = 300;

    /** Permanently moved. Use this for GET -> GET. */
    const MOVED_PERMANENT = 301;

    /** Temporarily moved. Use this for GET -> GET. */
    const FOUND = 302;

    /** Temporarily moved. Use this for POST/PUT/DELETE -> GET. */
    const SEE_OTHER = 303;

    /** Not modified since 'If-Modified-Since'. */
    const NOT_MODIFIED = 304;

    /** Temporarily moved. Use this for POST -> POST and the like. */
    const TEMPORARY_REDIRECT = 307;

    /** Permanently moved. Use this for POST -> POST and the like. */
    const PERMANENT_REDIRECT = 308;


    // Client Errors //

    /** Generic client error. */
    const BAD_REQUEST = 400;

    /** Not permitted, the user is not yet authenticated. */
    const UNAUTHORIZED = 401;

    /** Not ratified into a standard, but use this for whatever as long as it's money related. */
    const PAYMENT_REQUIRED = 402;

    /** Not permitted, the user _IS_ authenticated. */
    const FORBIDDEN = 403;

    /** Resource is missing. */
    const NOT_FOUND = 404;

    /** Method isn't support for the given resource. */
    const METHOD_NOT_ALLOWED = 405;

    /** Content doesn't match the requested accept header. */
    const NOT_ACCEPTABLE = 406;

    /** The client must authenticate with the proxy first. */
    const PROXY_AUTHENTICATION_REQUIRED = 407;

    /** The client did not produce a request before the server gave up. */
    const REQUEST_TIMEOUT = 408;

    /** Conflict between resources or state. */
    const CONFLICT = 409;

    /** The resource is no longer available. */
    const GONE = 410;

    /**  */
    const LENGTH_REQUIRED = 411;

    /**  */
    const PRECONDITION_FAILED = 412;

    /**  */
    const PAYLOAD_TOO_LARGE = 413;

    /**  */
    const URI_TOO_LONG = 414;

    /** The content type isn't supported. */
    const UNSUPPORTED_MEDIA_TYPE = 415;

    /** The server cannot supply the requested range header. */
    const RANGE_NOT_SATISFIABLE = 416;

    /**  */
    const EXPECTATION_FAILED = 417;

    /** Yep. */
    const TEAPOT = 418;

    /**  */
    const MISDIRECTED_REQUEST = 421;

    /**  */
    const UNPROCESSABLE_CONTENT = 422;

    /** Unwilling to process a request that might be replayed. */
    const TOO_EARLY = 425;

    /**  */
    const PRECONDITION_REQUIRED = 428;

    /** Rate-limit reached. */
    const TOO_MANY_REQUESTS = 429;

    /**  */
    const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;

    /**  */
    const UNAVAILABLE_FOR_LEGAL_REASONS = 451;


    // Server Errors //

    /** Generic everything is bad. */
    const INTERNAL_SERVER_ERROR = 500;

    /** Method or resource is not recognized, but may be later. */
    const NOT_IMPLEMENTED = 501;

    /** Response not received from upstream. */
    const BAD_GATEWAY = 502;

    /** Server can't handle it. */
    const SERVICE_UNAVAILABLE = 503;

    /** The upstream didn't respond in time. */
    const GATEWAY_TIMEOUT = 504;


    /**
     * Status code strings.
     */
    const STRINGS = [
        self::CONTINUE => 'Continue',
        self::SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::OK => 'OK',
        self::CREATED => 'Created',
        self::ACCEPTED => 'Accepted',
        self::NO_CONTENT => 'No Content',
        self::RESET_CONTENT => 'Reset Content',
        self::PARTIAL_CONTENT => 'Partial Content',
        self::MULTIPLE_CHOICES => 'Multiple Choices',
        self::MOVED_PERMANENT => 'Permanently Moved',
        self::FOUND => 'Found',
        self::SEE_OTHER => 'See Other',
        self::NOT_MODIFIED => 'Not Modified',
        self::TEMPORARY_REDIRECT => 'Redirect (Temporary)',
        self::PERMANENT_REDIRECT => 'Redirect (Permanent)',
        self::BAD_REQUEST => 'Bad Request',
        self::UNAUTHORIZED => 'Unauthorized',
        self::PAYMENT_REQUIRED => 'Payment Required',
        self::FORBIDDEN => 'Forbidden',
        self::NOT_FOUND => 'Not Found',
        self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
        self::NOT_ACCEPTABLE => 'Not Acceptable',
        self::PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
        self::REQUEST_TIMEOUT => 'Request Timeout',
        self::CONFLICT => 'Conflict',
        self::GONE => 'Gone',
        self::LENGTH_REQUIRED => 'Length Required',
        self::PRECONDITION_FAILED => 'Precondition Failed',
        self::PAYLOAD_TOO_LARGE => 'Payload Too Large',
        self::URI_TOO_LONG => 'URI Too Long',
        self::UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
        self::RANGE_NOT_SATISFIABLE => 'Range Not Satisfiable',
        self::EXPECTATION_FAILED => 'Expectation Failed',
        self::TEAPOT => 'I\'m A Teapot',
        self::MISDIRECTED_REQUEST => 'Misdirected Request',
        self::UNPROCESSABLE_CONTENT => 'Unprocessable Content',
        self::TOO_EARLY => 'Too Early',
        self::PRECONDITION_REQUIRED => 'Precondition Required',
        self::TOO_MANY_REQUESTS => 'Too Many Requests',
        self::REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
        self::UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
        self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::NOT_IMPLEMENTED => 'Not Implemented',
        self::BAD_GATEWAY => 'Bad Gateway',
        self::SERVICE_UNAVAILABLE => 'Service Unavailable',
        self::GATEWAY_TIMEOUT => 'Gateway Timeout',
    ];


    /**
     * Convert a code to a string.
     *
     * @param int $code
     * @return string
     */
    public static function toString(int $code): string
    {
        return self::STRINGS[$code] ?? 'Unknown Error';
    }


    /**
     * Get the full status string.
     *
     * @param int $code
     * @param float $version
     * @return string
     */
    public static function getStatus(int $code, float $version = 1.1): string
    {
        $string = self::toString($code);
        $version = sprintf('%.1f', $version);
        return "HTTP/{$version} {$code} {$string}";
    }
}
