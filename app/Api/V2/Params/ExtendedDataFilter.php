<?php

namespace TLCMap\Api\V2\Params;

use TLCMap\Api\V2\Errors\ApiException;

/**
 * The extended_data filter expression (spec section 7.8), parsed by a hand-written scanner.
 * Anything that does not parse is rejected with the character position where it failed;
 * nothing is ever discarded.
 */
class ExtendedDataFilter
{
    private const WORD_OPERATORS = ['textmatch', 'before', 'after'];

    /** @var string */
    private $expression;
    /** @var array */
    private $clauses = [];

    /** @var string[] the expression as characters, so positions are what a person sees */
    private $chars;
    /** @var int */
    private $n;

    private function __construct(string $expression)
    {
        $this->expression = $expression;
        $chars = preg_split('//u', $expression, -1, PREG_SPLIT_NO_EMPTY);
        $this->chars = $chars === false ? str_split($expression) : $chars;
        $this->n = count($this->chars);
    }

    public static function parse(string $expression, int $maxClauses): self
    {
        $filter = new self($expression);
        $filter->scan($maxClauses);
        return $filter;
    }

    /** @return array[] each ['field', 'op', 'value', 'date'] */
    public function clauses(): array
    {
        return $this->clauses;
    }

    public function toEcho(): string
    {
        return $this->expression;
    }

    private function reject(int $position, string $expected): void
    {
        throw ApiException::filterRejected($this->expression, $position, $expected);
    }

    private function isSpace(int $i): bool
    {
        return $i >= 0 && $i < $this->n && preg_match('/^\s$/u', $this->chars[$i]) === 1;
    }

    private function skipSpace(int $i): int
    {
        while ($i < $this->n && $this->isSpace($i)) {
            $i++;
        }
        return $i;
    }

    private function text(int $from, int $to): string
    {
        return implode('', array_slice($this->chars, $from, max(0, $to - $from)));
    }

    private function isQuote(int $i): bool
    {
        return $i < $this->n && ($this->chars[$i] === '"' || $this->chars[$i] === "'");
    }

    /** Reads a quoted string starting at $i; returns [inner text, position after the closing quote]. */
    private function readQuoted(int $i): array
    {
        $quote = $this->chars[$i];
        for ($j = $i + 1; $j < $this->n; $j++) {
            if ($this->chars[$j] === $quote) {
                return [$this->text($i + 1, $j), $j + 1];
            }
        }
        $this->reject($i, 'a closing quote');
    }

    /**
     * The operator at $i, normalised, or null. A word operator must be followed by whitespace, and
     * preceded by it unless $leftBounded says the caller has already checked the left side.
     */
    private function operatorAt(int $i, bool $leftBounded): ?string
    {
        if ($i >= $this->n) {
            return null;
        }
        $c = $this->chars[$i];
        if ($c === '=' || $c === '>' || $c === '<') {
            return $c;
        }
        if (!$leftBounded && !$this->isSpace($i - 1)) {
            return null;
        }
        foreach (self::WORD_OPERATORS as $word) {
            $len = strlen($word);
            if ($i + $len < $this->n
                && strtolower($this->text($i, $i + $len)) === $word
                && $this->isSpace($i + $len)) {
                return $word;
            }
        }
        return null;
    }

    /** Whether an upper-case AND, preceded by whitespace and followed by whitespace or the end, starts at $i. */
    private function andAt(int $i): bool
    {
        return $i + 3 <= $this->n
            && $this->text($i, $i + 3) === 'AND'
            && $this->isSpace($i - 1)
            && ($i + 3 === $this->n || $this->isSpace($i + 3));
    }

    private function scan(int $maxClauses): void
    {
        $i = $this->skipSpace(0);
        do {
            if ($i >= $this->n) {
                $this->reject($this->n, 'a clause');
            }
            if (count($this->clauses) >= $maxClauses) {
                $this->reject($i, "at most $maxClauses clauses");
            }
            $i = $this->clause($i);
        } while ($i !== null);
    }

    /** Parses one clause from $start; returns the start of the next clause, or null at the end. */
    private function clause(int $start): ?int
    {
        // Field.
        if ($this->isQuote($start)) {
            list($field, $i) = $this->readQuoted($start);
            if ($field === '') {
                $this->reject($start, 'a field name');
            }
            $i = $this->skipSpace($i);
            $op = $this->operatorAt($i, true);
            if ($op === null) {
                $this->reject($i, 'an operator');
            }
        } else {
            $op = null;
            for ($i = $start; $i < $this->n; $i++) {
                $op = $this->operatorAt($i, false);
                if ($op !== null) {
                    break;
                }
            }
            if ($op === null) {
                $this->reject($this->n, 'an operator');
            }
            $field = trim($this->text($start, $i));
            if ($field === '') {
                $this->reject($start, 'a field name');
            }
        }
        $i += strlen($op);

        // Value.
        $i = $this->skipSpace($i);
        $vstart = $i;
        if ($i >= $this->n) {
            $this->reject($this->n, 'a value');
        }
        $next = null;
        if ($this->isQuote($i)) {
            list($value, $i) = $this->readQuoted($i);
            if ($value === '') {
                $this->reject($vstart, 'a value');
            }
            $i = $this->skipSpace($i);
            if ($i < $this->n) {
                if (!$this->andAt($i)) {
                    $this->reject($i, 'AND or the end of the filter');
                }
                $next = $i + 3;
            }
        } else {
            for ($j = $i; $j < $this->n; $j++) {
                if ($this->andAt($j)) {
                    $next = $j + 3;
                    break;
                }
            }
            $value = trim($this->text($i, $next === null ? $this->n : $next - 3));
            if ($value === '') {
                $this->reject($vstart, 'a value');
            }
        }

        $date = null;
        if ($op === '>' || $op === '<') {
            if (preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $value) !== 1) {
                $this->reject($vstart, 'a number');
            }
        } elseif ($op === 'before' || $op === 'after') {
            try {
                $date = DateBound::parse('extended_data', $value);
            } catch (ApiException $e) {
                $this->reject($vstart, 'a date');
            }
        }

        $this->clauses[] = ['field' => $field, 'op' => $op, 'value' => $value, 'date' => $date];
        return $next === null ? null : $this->skipSpace($next);
    }
}
