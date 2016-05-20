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

namespace ActiveCollab\Insight\AccountInsight\Metric;

use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DateValue\DateTimeValue;
use InvalidArgumentException;

/**
 * @package ActiveCollab\Insight\AccountInsight\Metric
 */
class Events extends Metric implements EventsInterface
{
    /**
     * @var string
     */
    private $events_table;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->events_table = $this->insight->getTableName('events');
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $what, DateTimeValue $when = null, array $context = [])
    {
        $this->connection->insert($this->events_table, [
            'account_id' => $this->account->getAccountId(),
            'name' => $what,
            'created_at' => $when ?? new DateTimeValue(),
            'context' => json_encode($context),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function get(int $page = 1, int $per_page = 100)
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page value needs to be 1 or more');
        }

        if ($page < 1) {
            throw new InvalidArgumentException('Events per page value needs to be 1 or more');
        }

        $offset = ($page - 1) * $per_page;

        return $this->castResult($this->connection->execute("SELECT id, name, created_at, context FROM $this->events_table WHERE account_id = ? ORDER BY created_at DESC, id DESC LIMIT {$offset}, $per_page", $this->account->getAccountId()));
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->castResult($this->connection->execute("SELECT id, name, created_at, context FROM $this->events_table WHERE account_id = ? ORDER BY created_at DESC, id DESC", $this->account->getAccountId()));
    }

    /**
     * @var ValueCasterInterface
     */
    private $value_caster;

    /**
     * @param  ResultInterface|null  $result
     * @return ResultInterface|array
     */
    private function castResult(ResultInterface $result = null)
    {
        if (empty($this->value_caster)) {
            $this->value_caster = new ValueCaster(['context' => ValueCasterInterface::CAST_JSON]);
        }

        if ($result) {
            return $result->setValueCaster($this->value_caster);
        } else {
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->connection->count($this->events_table, ['account_id = ?', $this->account->getAccountId()]);
    }
}
