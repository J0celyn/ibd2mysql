<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql;
use DateInterval;
use DateTime;

/**
 * utility class that provides a simple timer to measure and display the time taken when running any of the 3 options of the script
 */
class Timer
{
    public const MULTIPLIERS = [
        'y' => [
            'multi' => 365 * 24 * 60 * 60,
            'long' => 'year(s)',
            'short' => 'y'
        ],
        'm' => [
            'multi' => 30 * 24 * 60 * 60,
            'long' => 'month(s)',
            'short' => 'm'
        ],
        'd' => [
            'multi' => 24 * 60 * 60,
            'long' => 'day(s)',
            'short' => 'd'
        ],
        'h' => [
            'multi' => 60 * 60,
            'long' => 'hour(s)',
            'short' => 'h'
        ],
        'i' => [
            'multi' => 60,
            'long' => 'minute(s)',
            'short' => 'mn'
        ],
        's' => [
            'multi' => 1,
            'long' => 'second(s)',
            'short' => 's'
        ],
        'f' => [
            'div' => true,
            'multi' => 0.000001,
            'long' => 'microsecond(s)',
            'short' => 'µs'
        ],
    ];

    protected DateTime $start;
    protected DateTime $end;

    public static function formatFromSeconds(float $seconds, bool $isShort = true): string
    {
        if ($isShort) {
            $format_key = 'short';
            $separator = ' ';
        } else {
            $format_key = 'long';
            $separator = ', ';
        }
        $result = [];
        foreach (self::MULTIPLIERS as $elem) {
            $div = floor($seconds / $elem['multi']);
            if ($div > 0) {
                if ($isShort) {
                    $format = '%d%s';
                } else {
                    $format = '%d %s';
                }
                $result[] = sprintf($format, $div, $elem[$format_key]);
                $seconds -= $div * $elem['multi'];
            }
        }
        return implode($separator, $result);
    }

    public function start(): void
    {
        $this->start = new DateTime();
    }

    public function stop(): void
    {
        $this->end = new DateTime();
    }

    public function toSeconds(DateInterval $diff): float
    {
        $seconds = 0;
        foreach (self::MULTIPLIERS as $key => $elem) {
            $seconds += $diff->{$key} * self::MULTIPLIERS[$key]['multi'];
        }
        return $seconds;
    }

    public function format(bool $isShort = true): string
    {
        $diff = $this->start->diff($this->end);

        if ($isShort) {
            $format_key = 'short';
            $separator = ' ';
        } else {
            $format_key = 'long';
            $separator = ', ';
        }

        $result = [];

        foreach (self::MULTIPLIERS as $key => $elem) {
            if ($diff->{$key} > 0) {
                if (isset($elem['div'])) {
                    $value = $diff->{$key} / $elem['multi'];
                } else {
                    $value = $diff->{$key};
                }
                if ($isShort) {
                    $format = '%d%s';
                } else {
                    $format = '%d %s';
                }
                $result[] = sprintf($format, $value, $elem[$format_key]);
            }
        }
        return implode($separator, $result);
    }
}