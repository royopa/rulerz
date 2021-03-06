<?php

namespace RulerZ\Visitor;

use Hoa\Ruler\Model as AST;

use RulerZ\Exception\OperatorNotFoundException;

/**
 * Base class for sql-related visitors.
 */
abstract class SqlVisitor extends GenericVisitor
{
    /**
     * Allow star operator.
     *
     * @var bool
     */
    protected $allowStarOperator = true;

    /**
     * Constructor.
     *
     * @param bool $allowStarOperator Whether to allow the star operator or not (ie: implicit support of unknown operators).
     */
    public function __construct($allowStarOperator = true)
    {
        $this->allowStarOperator = (bool) $allowStarOperator;

        $this->defineBuiltInOperators();
    }

    /**
     * {@inheritDoc}
     */
    public function visitScalar(AST\Bag\Scalar $element, &$handle = null, $eldnah = null)
    {
        $value = $element->getValue();

        return is_numeric($value) ? $value : sprintf("'%s'", $value);
    }

    /**
     * {@inheritDoc}
     */
    public function visitArray(AST\Bag\RulerArray $element, &$handle = null, $eldnah = null)
    {
        $array = parent::visitArray($element, $handle, $eldnah);

        return sprintf('(%s)', implode(', ', $array));
    }

    /**
     * {@inheritDoc}
     */
    public function visitAccess(AST\Bag\Context $element, &$handle = null, $eldnah = null)
    {
        return $element->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function visitOperator(AST\Operator $element, &$handle = null, $eldnah = null)
    {
        try {
            $xcallable = $this->getOperator($element->getName());
        } catch (OperatorNotFoundException $e) {
            if (!$this->allowStarOperator) {
                throw $e;
            }

            $xcallable = $this->getStarOperator($element);
        }

        $arguments = array_map(function ($argument) use (&$handle, $eldnah) {
            return $argument->accept($this, $handle, $eldnah);
        }, $element->getArguments());

        return $xcallable->distributeArguments($arguments);
    }

    /**
     * Return a "*" or "catch all" operator.
     *
     * @param Visitor\Element $element The node representing the operator.
     *
     * @return \Hoa\Core\Consistency\Xcallable
     */
    protected function getStarOperator(AST\Operator $element)
    {
        return xcallable(function () use ($element) {
            return sprintf('%s(%s)', $element->getName(), implode(', ', func_get_args()));
        });
    }

    /**
     * Define the built-in operators.
     */
    protected function defineBuiltInOperators()
    {
        $this->setOperator('and',  function ($a, $b) { return sprintf('(%s AND %s)', $a, $b); });
        $this->setOperator('or',   function ($a, $b) { return sprintf('(%s OR %s)', $a, $b); });
        $this->setOperator('not',  function ($a) {     return sprintf('NOT (%s)', $a); });
        $this->setOperator('=',    function ($a, $b) { return sprintf('%s = %s', $a, $b); });
        $this->setOperator('!=',   function ($a, $b) { return sprintf('%s != %s', $a, $b); });
        $this->setOperator('>',    function ($a, $b) { return sprintf('%s > %s', $a,  $b); });
        $this->setOperator('>=',   function ($a, $b) { return sprintf('%s >= %s', $a,  $b); });
        $this->setOperator('<',    function ($a, $b) { return sprintf('%s < %s', $a,  $b); });
        $this->setOperator('<=',   function ($a, $b) { return sprintf('%s <= %s', $a,  $b); });
        $this->setOperator('in',   function ($a, $b) { return sprintf('%s IN %s', $a, $b[0] === '(' ? $b : '('.$b.')'); });
        $this->setOperator('like', function ($a, $b) { return sprintf('%s LIKE %s', $a, $b); });
    }
}
