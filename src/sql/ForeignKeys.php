<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql\sql;
use function count;

/**
 * This class uses information from an SDI file to recreate the definition of a MySQL table's foreign keys
 */
class ForeignKeys
{
    public const FILENAME = '__foreign_keys.sql';

    /**
     * @param string $table
     * @param array $sdi_foreign_keys
     * @param array<Column> $columns
     */
    public function __construct(protected string $table, protected array $sdi_foreign_keys, protected array $columns) { }

    public function __toString(): string
    {
        $fkeys = [];

        $rules = [
            1 => 'NO ACTION',
            2 => 'RESTRICT',
            3 => 'CASCADE',
            4 => 'SET NULL',
            5 => 'SET DEFAULT',
        ];

        foreach ($this->sdi_foreign_keys as $sdi_foreign_key) {
            $columns = [];
            $referenced_columns = [];
            foreach ($sdi_foreign_key['elements'] as $elt) {
                $columns[] = $this->columns[$elt['column_opx']]->getName();
                $referenced_columns[] = $elt['referenced_column_name'];
            }

            $fkeys[] = sprintf('ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s ON UPDATE %s',
                $sdi_foreign_key['name'], implode('`, `', $columns), $sdi_foreign_key['referenced_table_name'], implode('`, `', $referenced_columns),
                $rules[$sdi_foreign_key['delete_rule']], $rules[$sdi_foreign_key['update_rule']]
            );

        }

        if (count($fkeys) === 0) {
            return '';
        }

        return sprintf("ALTER TABLE `%s`\n\t%s;", $this->table, implode(",\n\t", $fkeys));
    }
}