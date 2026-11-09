<?php

namespace TLCMap\Api\V2\Serialise;

/** A vocabulary entry as the API returns it (spec §5.3, §5.6). */
class VocabularySerialiser
{
    /**
     * @param \stdClass $row a row of Vocabulary::grouped
     * @return array{value: string, record_count: int, spellings: array}
     */
    public static function entry(\stdClass $row): array
    {
        $spellings = [];
        foreach (json_decode($row->spellings, true) as $s) {
            $spellings[] = ['value' => $s['value'], 'record_count' => (int) $s['record_count']];
        }

        return [
            'value' => trim($spellings[0]['value'], " \t\r\n"),
            'record_count' => (int) $row->record_count,
            'spellings' => $spellings,
        ];
    }
}
