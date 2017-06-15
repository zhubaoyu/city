<?php
namespace city;

trait Time
{
    public static function toDateTimeString($time)
    {
        return date('Y-m-d H:i:s', $time);
    }
}
