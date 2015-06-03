<?php
  namespace ActiveCollab\Insight\Utilities;

  /**
   * @package ActiveCollab\Insight\Utilities
   */
  trait Keyspace
  {
    /**
     * Return Redis key for the given account and subkey
     *
     * @param  string|array|null $sub
     * @return string
     */
    public function getRedisKey($sub = null)
    {
      $result = $this->getInsightRedisNamespace() . ':acc:' . $this->getInsightAccountId();

      if (is_string($sub) && $sub) {
        $result .= substr($sub, 0, 1) == ':' ? $sub : ':' . $sub;
      } else if (is_array($sub) && count($sub)) {
        $result .= ':' . implode(':', $sub);
      }

      return trim($result, ':');
    }

    /**
     * Return namespace that is used to prefix Insight database entries
     *
     * @return string
     */
    protected function getInsightRedisNamespace()
    {
      return '{ins}';
    }

    // ---------------------------------------------------
    //  Expctations
    // ---------------------------------------------------

    /**
     * @return int
     */
    abstract public function getInsightAccountId();
  }