<?php

/**
 * Add will return the sum of the given arguments.
 *
 * @author Matt Skelton
 */
class Add implements Expression
{
    function run(...$arguments): string
    {
        return array_sum($arguments);
    }
}