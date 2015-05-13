<?php
  namespace ActiveCollab\Insight;

  use DateTime;

  /**
   * @package ActiveCollab\Insight
   */
  interface DataSetTimeline
  {
    /**
     * Set timeline values for the given date
     *
     * @param DateTime $date
     * @param int      $additions
     * @param int      $unarchives
     * @param int      $archives
     * @param int      $deletions
     */
    public function timelineSetChangesForDate(DateTime $date, $additions, $unarchives, $archives, $deletions);

    /**
     * Increment number of additions for the given date
     *
     * @param DateTime $date
     */
    public function timelineLogAddition(DateTime $date);

    /**
     * Increment number of unarchives for the given date
     *
     * @param DateTime $date
     */
    public function timelineLogUnarchive(DateTime $date);

    /**
     * Increment number of archives for the given date
     *
     * @param DateTime $date
     */
    public function timelineLogArchive(DateTime $date);

    /**
     * Increment number of deletions for the given date
     *
     * @param DateTime $date
     */
    public function timelineLogDeletion(DateTime $date);
  }