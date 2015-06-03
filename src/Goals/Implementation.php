<?php
  namespace ActiveCollab\Insight\Goals;

  use ActiveCollab\Insight\Utilities\Timestamp;
  use DateTime, DateTimeZone, InvalidArgumentException;
  use Redis, RedisCluster;

  trait Implementation
  {
    /**
     * Return all goals that are reached
     *
     * @return string[]
     */
    public function getGoalsReached()
    {
      if ($goals_reached_count = $this->countGoalsReached()) {
        $goals_reached = $this->getInsightRedisClient()->zrange($this->getGoalsKey(), 0, $goals_reached_count - 1);

        if (!empty($goals_reached)) {
          sort($goals_reached);
        }

        return $goals_reached;
      } else {
        return [];
      }
    }

    /**
     * Return number of goals reached
     *
     * @return int
     */
    public function countGoalsReached()
    {
      return $this->getInsightRedisClient()->zcard($this->getGoalsKey());
    }

    /**
     * Return a list of goals and when they were reached (key is goal name, and value is timestamp)
     *
     * @return DateTime[]
     */
    public function getGoalsHistory()
    {
      $result = [];

      if ($goals_reached_count = $this->countGoalsReached()) {
        $gmt = new DateTimeZone('GMT');

        foreach ($this->getInsightRedisClient()->zrange($this->getGoalsKey(), 0, $goals_reached_count - 1, true) as $goal_name => $timestamp) {
          $result[$goal_name] = (new DateTime('now', $gmt))->setTimestamp($timestamp);
        }
      }

      return $result;
    }

    /**
     * Set $goal_name as reached
     *
     * @param string   $goal_name
     * @param DateTime $date
     */
    public function setGoalAsReached($goal_name, DateTime $date = null)
    {
      if ($this->isValidGoalName($goal_name)) {
        $goals_key = $this->getGoalsKey();

        if ($this->getInsightRedisClient()->zrank($goals_key, $goal_name) === false) {
          $this->getInsightRedisClient()->zadd($goals_key, ($date instanceof DateTime ? $date->getTimestamp() : Timestamp::getCurrentTimestamp()), $goal_name);
        }
      } else {
        throw new InvalidArgumentException("Goal name '$goal_name' is not valid (letters, numbers, space and underscore are allowed)");
      }
    }

    /**
     * Return true if $goal_name is valid
     *
     * @param  string $goal_name
     * @return bool
     */
    public function isValidGoalName($goal_name)
    {
      if (is_string($goal_name)) {
        if ($goal_name = trim($goal_name)) {
          $len = mb_strlen($goal_name);

          if ($len > 0 && $len <= 50) {
            return (boolean) preg_match("/^([a-zA-Z0-9_\s]*)$/", $goal_name);
          }
        }
      }

      return false;
    }

    /**
     * @return string
     */
    public function getGoalsKey()
    {
      return $this->getRedisKey('goals');
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * @return Redis|RedisCluster
     */
    abstract protected function &getInsightRedisClient();

    /**
     * Return Redis key for the given account and subkey
     *
     * @param  string|array|null $sub
     * @return string
     */
    abstract public function getRedisKey($sub = null);
  }