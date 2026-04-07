<?php
namespace ZfcDatagrid\DataSource\LaminasSelect;

use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;
use ZfcDatagrid\Column;
use ZfcDatagrid\Filter as DatagridFilter;
use function sprintf;

class Filter
{
    /**
     * Filter constructor.
     * @param Sql $sql
     * @param Select $select
     */
    public function __construct(private readonly Sql $sql, private readonly Select $select)
    {
    }

    /**
     * @return Sql
     */
    public function getSql(): Sql
    {
        return $this->sql;
    }

    /**
     * @return Select
     */
    public function getSelect(): Select
    {
        return $this->select;
    }

    /**
     * @param DatagridFilter $filter
     *
     * @return $this
     * @throws \Exception
     */
    public function applyFilter(DatagridFilter $filter): self
    {
        $select = $this->getSelect();

        $adapter = $this->getSql()->getAdapter();
        $qi      = (fn($name) => $adapter->getPlatform()->quoteIdentifier($name));

        $col = $filter->getColumn();
        if (! $col instanceof Column\Select) {
            throw new \Exception('This column cannot be filtered: ' . $col->getUniqueId());
        }

        $colString = $col->getSelectPart1();
        if ($col->getSelectPart2() != '') {
            $colString .= '.' . $col->getSelectPart2();
        }
        if ($col instanceof Column\Select && $col->hasFilterSelectExpression()) {
            $colString = sprintf($col->getFilterSelectExpression(), $colString);
        }
        $values = $filter->getValues();

        $wheres = [];
        foreach ($values as $value) {
            $where = new Where();

            $wheres[] = match ($filter->getOperator()) {
                DatagridFilter::LIKE => $where->like($colString, '%' . $value . '%'),
                DatagridFilter::LIKE_LEFT => $where->like($colString, '%' . $value),
                DatagridFilter::LIKE_RIGHT => $where->like($colString, $value . '%'),
                DatagridFilter::NOT_LIKE => $where->literal($qi($colString) . 'NOT LIKE ?', [
                    '%' . $value . '%',
                ]),
                DatagridFilter::NOT_LIKE_LEFT => $where->literal($qi($colString) . 'NOT LIKE ?', [
                    '%' . $value,
                ]),
                DatagridFilter::NOT_LIKE_RIGHT => $where->literal($qi($colString) . 'NOT LIKE ?', [
                    $value . '%',
                ]),
                DatagridFilter::EQUAL => $where->equalTo($colString, $value),
                DatagridFilter::NOT_EQUAL => $where->notEqualTo($colString, $value),
                DatagridFilter::GREATER_EQUAL => $where->greaterThanOrEqualTo($colString, $value),
                DatagridFilter::GREATER => $where->greaterThan($colString, $value),
                DatagridFilter::LESS_EQUAL => $where->lessThanOrEqualTo($colString, $value),
                DatagridFilter::LESS => $where->lessThan($colString, $value),
                DatagridFilter::BETWEEN => $where->between($colString, $values[0], $values[1]),
                default => throw new \InvalidArgumentException(
                    'This operator is currently not supported: ' . $filter->getOperator()
                ),
            };
        }

        if (! empty($wheres)) {
            $set = new PredicateSet($wheres, PredicateSet::OP_OR);
            $select->where->andPredicate($set);
        }

        return $this;
    }
}
