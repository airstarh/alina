<?php

namespace alina\Utils;

use DateInterval;

class DateTime extends \DateTime
{
    public function isLastDayOfMonth()
    {
        return $this->format('j') === $this->format('t');
    }

    public function setNextMonthFirstDay()
    {
        $year  = (int)$this->format('Y');
        $month = (int)$this->format('n');
        $this->setDate($year, $month, 1);
        $this->add(new DateInterval('P1M'));
    }

    public function setPreviousMonthFirstDay()
    {
        $year  = (int)$this->format('Y');
        $month = (int)$this->format('n');
        $this->setDate($year, $month, 1);
        $this->sub(new DateInterval('P1M'));
    }

    public function setLastDayOfMonth()
    {
        $year  = (int)$this->format('Y');
        $month = (int)$this->format('n');
        $day   = (int)$this->format('t');
        $this->setDate($year, $month, $day);
    }

    public function setPreviousMonthLastDay()
    {
        $this->setPreviousMonthFirstDay();
        $this->setLastDayOfMonth();
    }

    public function diffInMonths($date2)
    {
        $diff   = $this->diff($date2, true);
        $months = 0;
        $months += $diff->y * 12;
        $months += $diff->m;

        return $months;
    }

    public function diffInDays($date2)
    {
        $diff = $this->diff($date2, true);

        return $diff->format('%a');
    }

    /**
     *
     * @param DateTime $date2
     * @return bool
     */
    public function isSameDay($date2)
    {
        return $this->format(ALINA_DT_FORMAT_DB) === $date2->format(ALINA_DT_FORMAT_DB);
    }

    /**
     * Checks if the second date is in the same day of month.
     * @param DateTime $date2
     * @return bool
     * @throws \Exception
     */
    public function isSameDayOfMonth($date2)
    {
        $date2 = new static($date2->format(ALINA_DT_FORMAT_DB));

        return ($date2->isLastDayOfMonth() && $this->isLastDayOfMonth()) ||
            $this->format('j') === $date2->format('j');
    }

    /**
     * @param DateTime $date2
     * @return bool
     * @throws \Exception
     */
    public function isDiffWeek($date2)
    {
        $date2 = $this->leapYearAdj($date2);
        $diff  = $this->diff($date2, true);

        return ((int)$diff->days) % 7 == 0;
    }

    /**
     * @param DateTime $date2
     * @return bool
     * @throws \Exception
     */
    public function isDiffMonth($date2)
    {
        $date2 = $this->leapYearAdj($date2);
        if (!$this->isSameDayOfMonth($date2)) {
            return false;
        }

        return $this->diffInMonths($date2) >= 0;
    }

    /**
     * @param DateTime $date2
     * @return bool
     * @throws \Exception
     */
    public function isDiffQuarter($date2)
    {
        $date2 = $this->leapYearAdj($date2);
        if (!$this->isSameDayOfMonth($date2)) {
            return false;
        }
        $diffInMonths = $this->diffInMonths($date2);

        return $diffInMonths >= 0 && $diffInMonths % 3 == 0;
    }

    /**
     * @param DateTime $date2
     * @return bool
     * @throws \Exception
     */
    public function isDiffHalfYear($date2)
    {
        $date2 = $this->leapYearAdj($date2);
        if (!$this->isSameDayOfMonth($date2)) {
            return false;
        }
        $diffInMonths = $this->diffInMonths($date2);

        return $diffInMonths % 6 == 0;
    }

    /**
     * @param DateTime $date2
     * @return bool
     * @throws \Exception
     */
    public function isDiffYear($date2)
    {
        $date2 = $this->leapYearAdj($date2);
        if (!$this->isSameDayOfMonth($date2)) {
            return false;
        }
        $diffInMonths = $this->diffInMonths($date2);

        return $diffInMonths % 12 == 0;
    }

    /**
     * @param static $date
     * @return DateTime
     * @throws \Exception
     */
    private function leapYearAdj($date)
    {
        $dateDT      = new static($date->format(ALINA_DT_FORMAT_DB));
        $leapYearAdj = $dateDT->format('L') - $this->format('L');
        if ($leapYearAdj === 1) {
            $dateDT->add(new DateInterval("P1D"));
        }
        if ($leapYearAdj === -1) {
            $dateDT->sub(new DateInterval("P1D"));
        }

        return $dateDT;
    }

    ##################################################
    static public function toHumanDate($v)
    {
        return (new static())->setTimestamp((int)$v)->format(ALINA_DT_FORMAT_DB_D);
    }

    static public function toHumanDateTime($v)
    {
        return (new static())->setTimestamp((int)$v)->format(ALINA_DT_FORMAT_DB);
    }

    static public function dateToUnixTime($date)
    {
        $unixTimeStamp = strtotime($date);
        return $unixTimeStamp;
    }

    static public function utToDateTimeStartOfDay(int $ut)
    {
        $m = new static();
        $m->setTimestamp($ut);
        $m->setTime(0, 0);
        $res = $m->format(ALINA_DT_FORMAT_DB);
        return $res;
    }

    static public function utToDateTimeEndOfDay(int $ut)
    {
        $m = new static();
        $m->setTimestamp($ut);
        $m->setTime(23, 59);
        $res = $m->format(ALINA_DT_FORMAT_DB);
        return $res;
    }

    static public function utToUtDayStart(int $ut)
    {
        $d   = static::utToDateTimeStartOfDay($ut);
        $res = static::dateToUnixTime($d);
        return $res;
    }

    static public function utToUtDayEnd(int $ut)
    {
        $d   = static::utToDateTimeEndOfDay($ut);
        $res = static::dateToUnixTime($d);
        return $res;
    }

    static public function dateToHumanDayStart(string $date)
    {
        $m = new static($date);
        $m->setTime(0, 0, 0);
        $res = $m->format(ALINA_DT_FORMAT_DB);
        return $res;
    }

    static public function dateToHumanDayEnd(string $date)
    {
        $m = new static($date);
        $m->setTime(23, 59, 59);
        $res = $m->format(ALINA_DT_FORMAT_DB);
        return $res;
    }

    static public function dateToUtDayStart(string $date)
    {
        $d  = static::dateToHumanDayStart($date);
        $ut = static::dateToUnixTime($d);
        return $ut;
    }

    static public function dateToUtDayEnd(string $date)
    {
        $d  = static::dateToHumanDayEnd($date);
        $ut = static::dateToUnixTime($d);
        return $ut;
    }
    ##################################################
}
