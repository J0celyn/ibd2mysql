<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql\sql;
use function count;

/**
 * This class uses information from an SDI file to recreate the structure of a MySQL table (columns, indexes, partitions, foreign keys)
 */
class Table
{
    public const ROW_FORMATS = [
        1 => 'FIXED',
        2 => 'DYNAMIC',
        3 => 'COMPRESSED',
        4 => 'REDUNDANT',
        5 => 'COMPACT',
        6 => 'PAGED'
    ];

    protected string $name;

    /**
     * @var array<Column>
     */
    protected array $columns;
    protected Indexes $indexes;
    protected Partitioning $partitioning;
    protected ForeignKeys $foreignKeys;
    protected TableOptions $options;
    protected array $orderedCols;
    protected string $rowFormat;
    protected ?int $autoincValue;
    protected string $engine;
    protected int $collationId;

    public function __construct(array $sdi_dd_object, protected array $charsets)
    {
        $this->name = $sdi_dd_object['name'];
        $this->columns = $this->getColumns($sdi_dd_object['columns']);
        $this->indexes = new Indexes($sdi_dd_object['indexes'], $this->orderedCols, $this->charsets);
        $this->foreignKeys = new ForeignKeys($this->name, $sdi_dd_object['foreign_keys'], $this->orderedCols);
        $this->partitioning = new Partitioning($sdi_dd_object['partition_type'], $sdi_dd_object['partition_expression_utf8'], $sdi_dd_object['default_partitioning'],
            $sdi_dd_object['subpartition_type'], $sdi_dd_object['subpartition_expression_utf8'], $sdi_dd_object['default_subpartitioning'],
            $sdi_dd_object['partitions']);
        $this->options = new TableOptions($sdi_dd_object['options']);
        $this->rowFormat = self::ROW_FORMATS[$sdi_dd_object['row_format']];
        $this->engine = $sdi_dd_object['engine'];
        $this->parsePrivateData($sdi_dd_object['se_private_data']);
        $this->setCollationId($sdi_dd_object['collation_id']);

    }

    protected function getColumns(array $sdi_columns): array
    {
        $sql_cols = [];
        $this->orderedCols = [];

        foreach ($sdi_columns as $col) {
            if ($col['hidden'] === 2) {
                continue;
            }

            $column = new Column($col['name'], $col['column_type_utf8'], $this->charsets);
            $column->setIsNullable($col['is_nullable']);
            $column->setIsZeroFill($col['is_zerofill']);
            $column->setIsUnsigned($col['is_unsigned']);
            $column->setIsAutoIncrement($col['is_auto_increment']);
            $column->setIsVirtual($col['is_virtual']);
            $column->setHasDefaultValue(!$col['has_no_default']);
            $column->setDefaultValue($col['default_value_null'] ? null : $col['default_value_utf8']);
            $column->setComment($col['comment']);
            $column->setGenerationExpression($col['generation_expression_utf8']);
            $column->setCollationId($col['collation_id']);

            $this->orderedCols[$col['ordinal_position'] - 1] = $column;

            $sql_cols[$col['ordinal_position']] = $column->__toString();
        }
        ksort($sql_cols);

        return $sql_cols;
    }

    public function __toString(): string
    {
        $sql_data = array_merge($this->columns, $this->indexes->getSQL());
        $sql_table = sprintf("CREATE TABLE `%s`(\n\t%s\n) ENGINE=%s ROW_FORMAT=%s DEFAULT CHARSET=%s COLLATE=%s %s %s", $this->getName(),
            implode(",\n\t", $sql_data), $this->getEngine(), $this->rowFormat, $this->charsets[$this->getCollationId()]->getCharacterSetName(),
            $this->charsets[$this->getCollationId()]->getCollationName(), ($this->autoincValue !== null) ? 'AUTO_INCREMENT=' . $this->autoincValue : '', $this->options);

        $sql_partitions = $this->getPartitioning()->__toString();
        if ($sql_partitions !== '') {
            $sql_table .= "\n" . $sql_partitions;
        }
        $sql_table .= ';';
        return $sql_table;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }

    public function getCollationId(): int
    {
        return $this->collationId;
    }

    public function setCollationId(int $id): void
    {
        $this->collationId = $id;
    }

    public function getPartitioning(): Partitioning
    {
        return $this->partitioning;
    }

    public function parsePrivateData(string $sdi_private_data): void
    {
        $tmp = explode(';', $sdi_private_data);
        $tmp2 = [];
        foreach ($tmp as $elem) {
            $elems = explode('=', $elem);
            if (count($elems) < 2) {
                continue;
            }
            $tmp2[$elems[0]] = $elems[1];
        }
        $this->autoincValue = $tmp2['autoinc'] ?? null;
    }

    public function getForeignKeys(): ForeignKeys
    {
        return $this->foreignKeys;
    }

    public function __debugInfo(): ?array
    {
        $t = get_object_vars($this);
        unset($t['charsets']);
        return $t;
    }
}