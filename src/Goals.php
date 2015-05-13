<?php
  namespace ActiveCollab\Insight;

  use DateTime;

  /**
   * @package ActiveCollab\Insight
   */
  interface Goals
  {
    /**
     * Return all goals that are reached
     *
     * @return string[]
     */
    public function getGoalsReached();

    /**
     * Return number of goals reached
     *
     * @return int
     */
    public function countGoalsReached();

    /**
     * Return a list of goals and when they were reached (key is goal name, and value is timestamp)
     *
     * @return array
     */
    public function getGoalsHistory();

    /**
     * Set $goal as reached
     *
     * @param string   $goal_name
     * @param DateTime $date
     */
    public function setGoalAsReached($goal_name, DateTime $date = null);
  }