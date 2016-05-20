<?php

/*
 * This file is part of the Active Collab Insight.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\Insight\Account;

use DateTime;

/**
 * @package ActiveCollab\Insight
 */
interface GoalsInterface
{
    /**
     * Return all goals that are reached.
     *
     * @return string[]
     */
    public function getGoalsReached();

    /**
     * Return number of goals reached.
     *
     * @return int
     */
    public function countGoalsReached();

    /**
     * Return a list of goals and when they were reached (key is goal name, and value is timestamp).
     *
     * @return array
     */
    public function getGoalsHistory();

    /**
     * Set $goal as reached.
     *
     * @param string   $goal_name
     * @param DateTime $date
     */
    public function setGoalAsReached($goal_name, DateTime $date = null);
}
