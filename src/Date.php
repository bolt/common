<?php

namespace Bolt\Common;

class Date
{
    /**
     * Compare the timestamps of two different DateTime objects.
     */
    public static function datesDiffer(?\DateTime $dateTime1, ?\DateTime $dateTime2): bool
    {
        if ($dateTime1 !== null) {
            if ($dateTime2 !== null) {
                return $dateTime1->getTimestamp() !== $dateTime2->getTimestamp();
            }
            // $dateTime2 null while $dateTime1 is not
            return true;
        }
        // $dateTime1 is null, so if $dateTime2 is NOT null they differ
        return $dateTime2 !== null;
    }
}
