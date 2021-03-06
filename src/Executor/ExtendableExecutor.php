<?php

namespace RulerZ\Executor;

/**
 * An executor which can be extended (currently, only by registering new operators).
 */
interface ExtendableExecutor extends Executor
{
    /**
     * Registers new operators.
     *
     * @param array $operators A list of new operators of the form 'name' => callable
     */
    public function registerOperators(array $operators);
}
