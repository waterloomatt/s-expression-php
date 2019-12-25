<?php

/**
 * Parser is responsible for parsing the given input and calling the appropriate expression handlers.
 *
 * @author Matt Skelton
 */
class Parser
{
    /**
     * @var string represents the exception thrown when the program encounters a syntax error.
     */
    const EXCEPTION_SYNTAX = 'syntax_error';

    /**
     * @var string represents the exception thrown when a handler is invoked that hasn't been registered.
     */
    const EXCEPTION_HANDLER_NOT_REGISTERED = 'handler_not_registered';

    /**
     * @var string represents the exception thrown when the handler invoked is not an instance of `Expression`.
     */
    const EXCEPTION_HANDLER_NOT_EXPRESSION = 'handler_not_expression';

    /**
     * @var string the input to run.
     */
    protected $_input = '';

    /**
     * @var array a nested list of tokens that are extracted from the input.
     */
    protected $_tokens = [];

    /**
     * @var array a list of registered Expression handlers that may be called.
     */
    protected $_handlers = [];

    /**
     * @var string the regular expression to use to group/match the input.
     */
    protected $_regex = '/\(|\)|[^\s()]+/';

    /**
     * @var array a list of available exception messages.
     */
    protected $_exceptionMessages = [
        self::EXCEPTION_SYNTAX => 'There is a syntax error in the given expression.',
        self::EXCEPTION_HANDLER_NOT_REGISTERED => 'Expression handler `%s` must be registered.',
        self::EXCEPTION_HANDLER_NOT_EXPRESSION => 'Expression handler `%s` must implement the `Expression` interface.',
    ];

    /**
     * Parser constructor.
     * @param string $input
     * @param array $handlers
     */
    public function __construct(string $input, array $handlers)
    {
        $this->_input = $input;
        $this->_handlers = $handlers;
    }

    /**
     * Tokenize will split the input into a nested set of tokens.
     * @return $this
     */
    public function tokenize()
    {
        preg_match_all($this->_regex, $this->_input, $matches, PREG_OFFSET_CAPTURE);

        $stack = [];
        $tokens = [];
        $parenthesesCounter = 0;

        foreach ($matches[0] as $match) {

            $token = $match[0];

            switch ($token) {

                case '(':
                    $stack[] = $tokens;
                    $tokens = [];

                    $parenthesesCounter++;
                    break;

                case ')':
                    $prior = array_pop($stack);

                    if (!empty($prior)) {

                        if (empty($tokens)) {
                            throw new RuntimeException($this->_exceptionMessages[self::EXCEPTION_SYNTAX]);
                        }

                        $tokens = array_merge($prior, [$tokens]);
                    }

                    $parenthesesCounter--;
                    break;

                default:
                    $tokens[] = is_numeric($token)
                        ? (int)$token
                        : (string)$token;
                    break;
            }
        }

        // Simple check that left/right parentheses counts are equal.
        if ($parenthesesCounter !== 0) {
            throw new RuntimeException($this->_exceptionMessages[self::EXCEPTION_SYNTAX]);
        }

        // The first token should always be a valid handler or an integer.
        if (!in_array($tokens[0], array_keys($this->_handlers)) && !is_int($tokens[0])) {
            throw new RuntimeException($this->_exceptionMessages[self::EXCEPTION_SYNTAX]);
        }

        $this->_tokens = $tokens;

        return $this;
    }

    /**
     * Run will recursively drill down the token chain until it finds a handler and a list of arguments.
     * It will then instantiate the handler and call the `run` method, passing it a list of arguments.
     * @param array $tokens
     * @return array|string
     * @throws Exception
     */
    public function run($tokens = null)
    {
        if ($tokens === null) {
            $tokens = $this->_tokens;
        }

        if (is_array($tokens)) {

            if (count($tokens) === 1 && is_int($tokens[0])) {
                return $tokens[0];
            }

            $className = $this->run($tokens[0]);

            if (!array_key_exists($className, $this->_handlers)) {
                throw new RuntimeException(sprintf($this->_exceptionMessages[self::EXCEPTION_HANDLER_NOT_REGISTERED], $className));
            }

            $handler = $this->_handlers[$className];

            if (!$handler instanceof Expression) {
                throw new RuntimeException(sprintf($this->_exceptionMessages[self::EXCEPTION_HANDLER_NOT_EXPRESSION], get_class($handler)));
            }

            $arguments = [];
            foreach (array_slice($tokens, 1) as $token) {
                $arguments[] = $this->run($token);
            }

            return $handler->run(...$arguments);
        }

        return $tokens;
    }
}