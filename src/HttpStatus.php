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
abstract class HttpStatus
{

    // Success //

    /** Good. */
    const OK = 200;

    /** Created a new resource. */
    const CREATED = 201;

    /** Accepted for processing, but not complete. */
    const ACCEPTED = 202;

    /** No body. */
    const NO_CONTENT = 204;


    // Redirection //

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

    /** Conflict between resources or state. */
    const CONFLICT = 409;

    /** The resource is no longer available. */
    const GONE = 410;

    /** The content type isn't supported. */
    const UNSUPPORTED_MEDIA_TYPE = 415;

    /** Yep. */
    const TEAPOT = 418;

    /** Unwilling to process a request that might be replayed. */
    const TOO_EARLY = 425;

    /** Rate-limit reached. */
    const TOO_MANY_REQUESTS = 429;


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
}
