<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare (strict_types = 1);

namespace ActiveCollab\Insight;

use ActiveCollab\DateValue\DateValueInterface;
use ActiveCollab\Insight\AccountInsight\AccountInsightInterface;
use Carbon\Carbon;

/**
 * @package ActiveCollab\Insight
 */
interface InsightInterface
{
    /**
     * Create a daily data snapshot. Should be triggered every day.
     *
     * @param DateValueInterface|Carbon $day
     */
    public function dailySnapshot(DateValueInterface $day);

    /**
     * Create a weekly data snapshot. Should be triggered on Sunday or Monday.
     *
     * @param DateValueInterface|Carbon $day
     */
    public function weeklySnapshot(DateValueInterface $day);

    /**
     * Create a monthly snapshot. Should be triggered on the first day of the month.
     *
     * @param DateValueInterface|Carbon $day
     */
    public function monthlySnapshot(DateValueInterface $day);

    /**
     * Create a yearly snapshot. Should be triggered on Jan 1st of each year.
     *
     * @param DateValueInterface|Carbon $day
     */
    public function yearlySnapshot(DateValueInterface $day);

    /**
     * Return account insight instance for the given account.
     *
     * @param  int                     $account_id
     * @return AccountInsightInterface
     */
    public function account(int $account_id);

    /**
     * Return table prefix.
     *
     * @return string
     */
    public function getTablePrefix(): string;

    /**
     * Set table prefix.
     *
     * @param  string $value
     * @return $this
     */
    public function &setTablePrefix(string $value);

    /**
     * Return prefixed table name and make sure that table exists.
     *
     * @param  string $table_name
     * @return string
     */
    public function getTableName($table_name): string;
}
