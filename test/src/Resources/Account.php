<?php
  namespace ActiveCollab\Insight\Test;

  use ActiveCollab\Insight\Account as AccountInsight;
  use ActiveCollab\Insight\Account\Implementation as AccountInsightImplementation;

  /**
   * @package ActiveCollab\Insight\Test
   */
  class Account implements AccountInsight
  {
    use AccountInsightImplementation;

    /**
     * @var int
     */
    private $id = 1;

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
      if ($id) {
        $this->id = $id;
      }
    }

    /**
     * @return int
     */
    public function getInsightAccountId()
    {
      return $this->id;
    }
  }