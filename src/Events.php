<?php
  namespace ActiveCollab\Insight;

  use Psr\Log\LoggerInterface;

  /**
   * @package ActiveCollab\Insight
   */
  interface Events extends LoggerInterface
  {
    /**
     * Paginate log entries
     *
     * @param  int   $page
     * @param  int   $per_page
     * @return array
     */
    public function getEvents($page = 1, $per_page = 100);

    /**
     * Return number of events that are logged
     *
     * @return integer
     */
    public function countEvents();

    /**
     * Iterate over events, for newest to oldest
     *
     * Two arguments are sent to the callback:
     *
     * 1. $event - array with event details
     * 2. $iteration - current iteration #, starting from 1
     *
     * @param callable $callback
     */
    public function forEachEvent(callable $callback);
  }