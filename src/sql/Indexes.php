<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql\sql;
use function count;
use function in_array;

/**
 * This class uses information from an SDI file to recreate the definition of a MySQL table's indexes
 */
class Indexes
{
    public const INDEXES = [
        'PRIMARY' => 1,
        'UNIQUE' => 2,
        'MULTIPLE' => 3,
        'FULLTEXT' => 4,
        'SPATIAL' => 5,
    ];

    public const MAX_LIMIT = 2 ** 32 - 1;

    /**
     * @param array $sdi_indexes
     * @param array<Column> $ordered_cols
     * @param array<Charset> $charsets
     */
    public function __construct(protected array $sdi_indexes, protected array $ordered_cols, protected array $charsets) { }

    public function getSQL(): array
    {
        $sql_indexes = [];
        foreach ($this->sdi_indexes as $idx) {
            if ($idx['hidden'] === true) {
                continue;
            }
            $elts = $idx['elements'];
            if (count($elts) === 0) {
                continue;
            }
            $idx_name = $idx['name'];
            $cols = [];
            foreach ($elts as $elt) {
                // index columns whose length equals MAX_LIMIT are ignored
                if ($elt['length'] !== self::MAX_LIMIT) {
                    $column = $this->ordered_cols[$elt['column_opx']];
                    if (in_array($column->getBaseType(), Column::STRING_CODES, true)) {
                        // In the SDI file, index length is expressed in number of bytes. It must be converted into number of characters
                        $length = sprintf('(%d)', ceil($elt['length'] / $this->charsets[$column->getCollationId()]->getCharacterSetMaxLength()));
                    } else {
                        $length = '';
                    }
                    $cols[] = sprintf('`%s`%s', $this->ordered_cols[$elt['column_opx']]->getName(), $length);
                }
            }
            switch ($idx['type']) {
                case self::INDEXES['PRIMARY']:
                    $sql_indexes[] = sprintf('PRIMARY KEY (%s)', implode(', ', $cols));
                    break;

                case self::INDEXES['UNIQUE']:
                    $sql_indexes[] = sprintf('UNIQUE INDEX `%s` (%s)', $idx_name, implode(', ', $cols));
                    break;

                case self::INDEXES['MULTIPLE']:
                    $sql_indexes[] = sprintf('INDEX `%s` (%s)', $idx_name, implode(', ', $cols));
                    break;

                case self::INDEXES['FULLTEXT']:
                    $sql_indexes[] = sprintf('FULLTEXT `%s` (%s)', $idx_name, implode(', ', $cols));
                    break;

                case self::INDEXES['SPATIAL']:
                    $sql_indexes[] = sprintf('SPATIAL INDEX `%s` (%s)', $idx_name, implode(', ', $cols));
                    break;
            }
        }
        return $sql_indexes;
    }

    public function __debugInfo(): ?array
    {
        $t = get_object_vars($this);
        unset($t['charsets']);
        return $t;
    }
}