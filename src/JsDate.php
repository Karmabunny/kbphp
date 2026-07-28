<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */
namespace karmabunny\kb;

use DateTimeImmutable;

/**
 * A JavaScript date expression.
 *
 * A relative expression in days from the current date.
 *
 * This useful for building dynamic dates within configurations that are cacheable.
 *
 * @package karmabunny\kb
 */
class JsDate implements JsExpression
{

    /** @var string */
    public $date;


    /**
     * @param string $date
     */
    public function __construct(string $date = 'now')
    {
        $this->date = $date;
    }


    /** @inheritdoc */
    public function render(): string
    {
        if (!Time::hasRelativeKeywords($this->date)) {
            return "new Date(\"{$this->date}\")";
        }

        $now = new DateTimeImmutable('now');
        $date = $now->modify($this->date);

        $diff = $now->diff($date);
        $milliseconds = Time::getIntervalTotal($diff, 'milliseconds');
        $milliseconds = abs(floor($milliseconds));

        if ($milliseconds == 0) {
            return "new Date()";
        }

        $sign = $diff->invert ? '-' : '+';
        return "new Date(+new Date {$sign} {$milliseconds})";
    }


    /** @inheritdoc */
    public function __toString()
    {
        return $this->render();
    }
}
