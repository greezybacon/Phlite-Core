<?php

namespace Phlite\Cli;

/**
 * A terminfo compiler and interpreter for the terminfo library available
 * on most Unix distributions. A new instance can be created via the
 * ::forTerminal() method which will auto inspect and lazily compile the
 * terminfo capabilities for the current terminal from the operating system.
 *
 * This implementation currently relies on `infocmp` to provide access to
 * the terminfo capability strings. No support is currently available for
 * Windows.
 *
 * The capabilities can be accessed via simple property and method access,
 * or by utilizing the ::template() method. For instance, on an ANSI terminal
 * such as xterm, one might observe:
 *
 * >>> $TI = Terminfo::forTerminal();
 * >>> $TI->setaf(Terminfo::DARKRED);
 * "\x1b[31m"
 * >>> $TI->sgr0
 * "\x1b(B"
 * >>> $TI->template('{setaf:RED}Hello, world!{sgr0}')
 * "\x1b[91mHello, world!\x1b(B"
 */
class TermInfo {

    var $name;
    var $description;

    // TODO: Provide defaults as empty strings to provide gracefull fallbacks
    //       to noops while also crashing for invalid tput commands
    var $caps = array();

    // ANSI colors
    const BLACK     = 0;
    const DARKRED   = 1;
    const DARKGREEN = 2;
    const BROWN     = 3;
    const DARKYELLOW = 3;
    const DARKBLUE  = 4;
    const DARKMAGENTA = 5;
    const DARKCYAN  = 6;
    const GRAY      = 7;
    const DARKGRAY  = 8;
    const RED       = 9;
    const GREEN     = 10;
    const YELLOW    = 11;
    const BLUE      = 12;
    const MAGENTA   = 13;
    const CYAN      = 14;
    const WHITE     = 15;

    static protected $terminfos = array();

    private function __construct($caps, $name='dumb') {
        $this->caps = $caps;
        $this->name = $name;
    }

    static function dumb() {
        return new static(array(
            'bol' => "\r",
        ));
    }

    /**
     * Create a Terminfo instance for the declared TERM name or the term
     * currently set up in the environment. The result is cached, so multiple
     * calls with the same $TERM will return the same Terminfo instance.
     */
    static function forTerminal($TERM=false) {
        // TODO: Support varying TERM string
        $TERM = $TERM ?: '';

        // Cache the result for future calls with the same $TERM
        if (isset(self::$terminfos[$TERM]))
            return self::$terminfos[$TERM];

        // Attempt to read from `infocmp`
        if (!($info = popen("infocmp -I $TERM", 'r')))
            // Provide some fallback for ANSI, or Windows terminals
            return static::dumb();

        do {
            $L = fgets($info);
            if ($L[0] != '#')
                @list($name, $description) = explode('|', $L, 2);
        }
        while (!isset($name));

        $matches = array();
        if (!preg_match_all('`\s+(\w+)(?:([=#])([^,]+))?,`', fread($info, 4096), $matches, PREG_SET_ORDER))
            return;

        $caps = array();
        $esc = chr(27);
        foreach ($matches as $C) {
            if (isset($C[3])) {
                // Unescape escape sequences
                $val = preg_replace_callback('`\^\w|\\\\E`',
                function($m) use ($esc) {
                    if ($m[0][0] == '^')
                        return chr(ord($m[0][1]) - 64);
                    elseif ($m[0][1] == 'E')
                        return $esc;
                }, $C[3]);
                if ($C[2] == '#')
                    $val = (int) $val;
                $caps[$C[1]] = $val;
            }
            else {
                $caps[$C[1]] = true;
            }
        }

        // TODO: Attempt to determine the current size of the terminal $(tput cols|lines)
        if (isset($_ENV['LINES'])) {
            // bash and zsh
            $caps['lines'] = $_ENV['LINES'];
            $caps['cols'] = $_ENV['COLS'];
        }

        return self::$terminfos[$TERM] = new static($caps, $name);
    }

    function __isset($cap) {
        return isset($this->caps[$cap]);
    }

    function __get($cap) {
        return @$this->caps[$cap] ?: '';
    }

    function __call($cap, $args) {
        if (!isset($this->caps[$cap])) {
            // Graceful degrade for now
            return '';
            throw new \InvalidArgumentException('No such capability');
        }

        if (!is_callable($this->caps[$cap])) {
            $this->caps[$cap] = $this->compile_terminfo($this->caps[$cap]);
            if (!is_callable($this->caps[$cap]))
                throw new \InvalidArgumentException('Capability does not take arguments');
        }
        return $this->caps[$cap]($args);
    }

    /**
     * Replace terminal commands in a template string:
     * <{setaf:RED}Hello World{sgr0}>
     *
     * Parameters are supported in the templates if the capability name is
     * followed by a colon (:) along with comma-separated parameters. Any
     * constants defined in the Terminfo class (like the colors) are also
     * supported and will be automatically dereferenced.
     *
     * Open and close brace can be escaped by doubling (`{{`).
     */
    function template($string) {
        $self = $this;
        $class = new \ReflectionClass($this);
        $consts = $class->getConstants();
        $templated = preg_replace_callback('`(?<!{)\{(\w+)(?::([^\}]+))?\}`',
        function($m) use ($self, $consts) {
            if (isset($m[2])) {
                $args = explode(',', $m[2]);
                foreach ($args as $i=>$A) {
                    $A = trim($A);
                    if (is_numeric($A))
                        $args[$i] = (int) $A;
                    elseif (isset($consts[$A]))
                        $args[$i] = $consts[$A];
                }
                return $self->__call($m[1], $args);
            }
            elseif (isset($m[1]))
                return $self->__get($m[1]);
            else
                // Return first char of doubled braces
                return $m[0][0];
        }, $string);

        return str_replace(array('{{','}}'), array('{','}'), $templated);
    }

    /**
     * Compiles a terminfo string to a PHP callable function. The functions
     * arguments are interpreted according to the terminfo string, and the
     * result of the function is the bytes which should be sent to the
     * terminal.
     *
     * This compiler utilizes somewhat of a lambda calculus pattern with
     * nested anonymous functions.
     */
    protected function compile_terminfo($expr) {
        // `man terminfo` for details on the %X sequences

        static $simple = null;
        if ($simple === null)
            $simple = array(
            // %%   outputs `%'
            '%' => function($S) { return '%'; },
            // %c print pop() like %c in printf
            'c' => function(&$S) { return chr(array_pop($S)); },
            'd' => function(&$S) { return array_pop($S); },
            // %+ %- %* %/ %m
            // arithmetic (%m is mod): push(pop() op pop())
            '+' => function(&$S) { $S[] = array_pop($S) + array_pop($S); },
            '-' => function(&$S) { $_ = array_pop($S); $S[] = array_pop($S) - $_; },
            '*' => function(&$S) { $S[] = array_pop($S) * array_pop($S); },
            '/' => function(&$S) { $_ = array_pop($S); $S[] = array_pop($S) / $_; },
            'm' => function(&$S) { $_ = array_pop($S); $S[] = array_pop($S) % $_; },
            // %& %| %^
            // bit operations (AND, OR and exclusive-OR): push(pop() op pop())
            '&' => function(&$S) { $S[] = array_pop($S) & array_pop($S); },
            '|' => function(&$S) { $S[] = array_pop($S) | array_pop($S); },
            '^' => function(&$S) { $S[] = array_pop($S) ^ array_pop($S); },
            // %= %> %<
            // logical operations: push(pop() op pop())
            // Flip the comparision b/c pops will reverse the operands
            '<' => function(&$S) { $S[] = array_pop($S) > array_pop($S); },
            '>' => function(&$S) { $S[] = array_pop($S) < array_pop($S); },
            '=' => function(&$S) { $S[] = array_pop($S) == array_pop($S); },
            // %A, %O
            // logical AND and OR operations (for conditionals)
            'A' => function(&$S) { $S[] = array_pop($S) && array_pop($S); },
            'O' => function(&$S) { $S[] = array_pop($S) || array_pop($S); },
            // %! %~
            // unary operations (logical and bit complement): push(op pop())
            '!' => function(&$S) { $S[] = !array_pop($S); },
            '~' => function(&$S) { $S[] = ~array_pop($S); },
            // %? expr %t thenpart %e elsepart %;
            // This forms an if-then-else.  The %e elsepart is optional.
            // Usually the %?  expr  part  pushes a value  onto the stack,
            // and %t pops it from the stack, testing if it is nonzero (true).
            // If it is zero (false), control passes to the %e (else) part.
            'l' => function(&$S) { $S[] = strlen(array_pop($S)); },
            // The interpretation is really difficult:
            // %i — add 1 to first two parameters (for ANSI terminals)
            // What's a "parameter"?
            'i' => function(&$S) { $S['A'][0]++; $S['A'][1]++; },
        );

        $blocks = array();
        $push = function($f) use (&$blocks) {
            $blocks[count($blocks) - 1][] = $f;
        };

        $pop_block = function() use (&$blocks) {
            $block = $blocks[count($blocks) - 1];
            $blocks = array_slice($blocks, 0, -1);
            return function(&$stack) use ($block) {
                $output = '';
                foreach ($block as $C) {
                    $output .= $T = $C($stack);
                }
                return $output;
            };
        };

        $push_block = function() use (&$blocks) {
            $blocks[] = array();
        };

        // Prologue
        $push_block();

        $func = $pos = 0;
        $len = strlen($expr);
        $buffer = '';
        while ($pos < $len) {
            if ('%' != ($T = $expr[$pos++])) {
                // Not a `%` sequence, add to output buffer
                $buffer .= $T;
            }
            else {
                if ($buffer) {
                    $push(function($S) use ($buffer) { return $buffer; });
                    $buffer = '';
                }
                $T = $expr[$pos++];
                if (isset($simple[$T])) {
                    $push($simple[$T]);
                }
                else switch ($T) {
                case 'p':
                    // %p[1-9]
                    // push i'th parameter
                    $n = ((int) $expr[$pos++]) - 1;
                    if (substr($expr, $pos, 2) == '%d') {
                        // Optimization for `%p1%d`
                        $push(function($S) use ($n) { $S['A'][$n]; });
                        $pos += 2;
                    }
                    else
                        $push(function(&$S) use ($n) { $S[] = $S['A'][$n]; });
                    break;
                case '{':
                    // %{nn}
                    // integer constant nn
                    $n = '';
                    while ('}' != ($X = $expr[$pos++]))
                        $n .= $X;
                    $push(function(&$S) use ($n) { $S[] = (int) $n; });
                    break;
                case 'P':
                    // %P[a-z]
                    // set dynamic variable [a-z] to pop()
                    $char = $expr[$pos++];
                    $push(function(&$S) use ($char) { $S['V'][$char] = array_pop($S); });
                    break;
                case 'g':
                    // get dynamic variable [a-z] and push it
                    // %g[a-z]
                    $char = $expr[$pos++];
                    $push(function(&$S) use ($char) { $S[] = $S['V'][$char]; });
                    break;
                case ';':
                    // End of if-then-else
                    // Create and utilize an inline-if style, where
                    // iif := function(condition, if_true(), if_false())
                    // Then, work right to left to create a closure chain using
                    // this IIF function. Consider this code, where <x>
                    // represents arbitrary code:
                    // >>> %? <A> %t <B> %e <C> %;
                    // => IIF(<A>, <B>, <C>)
                    // >>> %? <A> %t <B> %e <C> %t <D> %e <E> %;
                    // => IIF(<A>, <B>, IIF(<C>, <D>, <E>))
                    //
                    // If block count is odd, then there is no trailing %e
                    if (count($blocks) % 2 == 1)
                        $else = function($S) {};
                    else
                        $else = $pop_block();
                    while (count($blocks) > 1) {
                        $true = $pop_block();
                        $cond = $pop_block();
                        $else = function(&$S) use ($cond, $true, $else) {
                            $T = $cond($S);
                            if (array_pop($S)) {
                                return $true($S);
                            }
                            else {
                                return $else($S);
                            }
                        };
                    }
                    $push($else);
                    break;
                case '?':
                    // continue to process the expression and %t
                case 't':
                    // if (true) part of if-then-else. The previous part,
                    // the top-of-stack now, is the expression.
                case 'e':
                    // the ELSE part of an if-then-else. This could either
                    // be an expression or a block. It depends if it is
                    // followed by another %t
                    // Place the TRUE part as the top-of-stack
                    $push_block();
                    break;
                default:
                    // The default is assumed to be a valid printf token using
                    // the top-of-stack
                    $rest = "%$T";
                    while (false === strpos('doXxs', $T)) {
                        $T = $expr[$pos++];
                        $rest .= $T;
                    }
                    $push(function(&$S) use ($rest) {
                        return sprintf($rest, array_pop($S));
                    });
                    break;
                }
                $func = true;
            }
        }
        if (!$func)
            return $expr;

        if ($buffer)
            $push(function($S) use ($buffer) { return $buffer; });

        // All arguments are passed as an array (from __call)
        $code = $pop_block();
        if (count($blocks))
            throw new \RuntimeException(
                'Terminfo capability compiler error. Ended with nested blocks: '
                . count($blocks)
            );

        return function($A) use ($code) {
            $stack = array('V' => array(), 'A' => $A);
            return $code($stack);
        };
    }
}
