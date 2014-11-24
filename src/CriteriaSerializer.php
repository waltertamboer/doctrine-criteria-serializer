<?php

namespace Doctrine\Common\Collections;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use RuntimeException;

/**
 * A helper class to serialize and unsersialize Criteria instances.
 *
 * @author Walter Tamboer <walter@tamboer.nl>
 */
class CriteriaSerializer extends ExpressionVisitor
{
    /**
     * Serializes a the given criteria to a PHP serialized format.
     *
     * @param Criteria $criteria The criteria to serialize.
     * @return string
     */
    public function serialize(Criteria $criteria)
    {
        $structure = array(
            'whereExpression' => $this->dispatch($criteria->getWhereExpression()),
            'firstResult' => $criteria->getFirstResult(),
            'maxResults' => $criteria->getMaxResults(),
            'orderings' => $criteria->getOrderings(),
        );

        return serialize($structure);
    }

    /**
     * Unserializes the given string.
     *
     * @param string $data The data to unserialize.
     * @return Criteria
     */
    public function unserialize($data)
    {
        $structure = unserialize($data);

        $criteria = Criteria::create();
        $criteria->setFirstResult($structure['firstResult']);
        $criteria->setMaxResults($structure['maxResults']);
        $criteria->orderBy((array)$structure['orderings']);

        $this->buildExpressions($criteria, $structure['whereExpression']);

        return $criteria;
    }

    /**
     * Builds the expressions for the given criteria.
     *
     * @param Criteria $criteria The criteria to build expressions for.
     * @param array $structure The structure with expressions.
     * @throws RuntimeException
     */
    private function buildExpressions(Criteria $criteria, $structure)
    {
        if (array_key_exists('type', $structure)) {
            foreach ($structure['expressions'] as $expression) {
                $expr = $this->buildExpression($criteria, $expression);
                if (!$expr) {
                    continue;
                }

                switch ($structure['type']) {
                    case CompositeExpression::TYPE_AND:
                        $criteria->andWhere($expr);
                        break;

                    case CompositeExpression::TYPE_OR:
                        $criteria->orWhere($expr);
                        break;

                    default:
                        throw new RuntimeException('Invalid expression type: ' . $structure['type']);
                }
            }
        } else {
            $expr = $this->buildExpression($criteria, $structure);

            $criteria->where($expr);
        }
    }

    private function buildExpression(Criteria $criteria, $expression)
    {
        if (array_key_exists('type', $expression)) {
            $this->buildExpressions($criteria, $expression);
            return;
        }

        if (is_object($expression['value'])) {
            $value = $this->buildExpression($criteria, $expression['value']);
        } else {
            $value = new Value($expression['value']);
        }

        return new Comparison($expression['field'], $expression['operator'], $value);
    }

    /**
     * @inheritDoc
     */
    public function walkComparison(Comparison $comparison)
    {
        $value = $this->dispatch($comparison->getValue());

        return array(
            'field' => $comparison->getField(),
            'value' => $value,
            'operator' => $comparison->getOperator(),
        );
    }

    /**
     * @inheritDoc
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = array();

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        return array(
            'type' => $expr->getType(),
            'expressions' => $expressionList,
        );
    }

    /**
     * @inheritDoc
     */
    public function walkValue(Value $value)
    {
        if (is_object($value->getValue())) {
            $result = $this->dispatch($value->getValue());
        } else {
            $result = $value->getValue();
        }

        return $result;
    }
}
