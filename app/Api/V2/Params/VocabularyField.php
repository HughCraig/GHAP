<?php

namespace TLCMap\Api\V2\Params;

use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Query\RecordQuery;

/**
 * The field a vocabulary groups (A2 spec §5.1), and the column it reads. The column names come only
 * from COLUMNS, never from the request.
 */
class VocabularyField
{
    public const FIELDS = ['feature_term', 'state', 'lga', 'parish', 'record_type'];

    /** Each field's column over the aliases of RecordQuery::build. */
    private const COLUMNS = [
        'feature_term' => 'i.feature_term',
        'state' => 'i.state',
        'lga' => 'i.lga',
        'parish' => 'i.parish',
        'record_type' => 'rt.type',
    ];

    /** @var string */
    public $name;
    /** @var string trusted SQL */
    public $column;

    private function __construct(string $name)
    {
        $this->name = $name;
        $this->column = self::COLUMNS[$name];
    }

    /** Reads the required `field`. */
    public static function parse(ParameterSet $p): self
    {
        if (!$p->has('field')) {
            $accepts = implode(', ', self::FIELDS);
            throw ApiException::invalidParameter(
                'field',
                'required',
                $accepts,
                "The parameter 'field' is required and must be one of: $accepts."
            );
        }

        return new self($p->enum('field', self::FIELDS, null));
    }

    public static function named(string $name): self
    {
        if (!isset(self::COLUMNS[$name])) {
            throw new \InvalidArgumentException("Unknown vocabulary field $name");
        }

        return new self($name);
    }

    /** The comparison key of the column (A2 spec §5.3). */
    public function key(): string
    {
        return RecordQuery::vocabularyKey($this->column);
    }

    /** True when the column holds something other than null, empty or whitespace. */
    public function hasValue(): string
    {
        return "nullif(btrim({$this->column}, E' \\t\\r\\n'), '') IS NOT NULL";
    }
}
