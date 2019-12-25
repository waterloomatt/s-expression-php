<?php

/**
 * Add will return the product of the given arguments.
 *
 * @author Matt Skelton
 */
class Multiply implements Expression
{
    function run(...$arguments): string
    {
        return array_product($arguments);
    }
}