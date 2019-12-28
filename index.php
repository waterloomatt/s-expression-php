<?php
/**
 * The main entry point of the program. You should run it from the command line and pass in a single argument.
 *
 * Examples:
 * $ php index.php 123
 * $ php index.php '(add 1 1)'
 * $ php index.php '(add 1 (multiply 1 2 3 4 5))'
 *
 * @author Matt Skelton
 */

require 'Autoloader.php';

/**
 * Register expression handlers. The order in which they are registered does not matter.
 * They should be in the form of $key => $value
 *  - $key should match a given function name from the input. Ex. `add`, `multiply`.
 *  - $value should be an object that implements the `Expression` interface.
 */
$handlers = [
    'add' => new Add(),
    'multiply' => new Multiply()
];

try {
    if ($argc !== 2) {
        throw new RuntimeException('Please provide 1 argument.');
    }

    echo (new Parser($argv[1], $handlers))
        ->tokenize()
        ->run();

} catch (Exception $e) {
    echo $e->getMessage();
}
