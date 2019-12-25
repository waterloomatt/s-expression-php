<?php

/**
 * Interface Expression.
 *
 * @author Matt Skelton
 */
interface Expression
{
    function run(...$arguments): string;
}
