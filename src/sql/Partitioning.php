<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql\sql;
use function count;

/**
 * This class uses information from an SDI file to recreate the definition of a MySQL table's partitions and subpartitions
 */
class Partitioning
{
    public const TYPES = [
        'NO_PARTITION' => 0,
        'HASH' => 1,
        'KEY' => 3,
        'LINEAR_HASH' => 4,
        'RANGE' => 7,
        'LIST' => 8,
    ];

    public const METHODS = [
        self::TYPES['HASH'] => 'HASH',
        self::TYPES['KEY'] => 'KEY',
        self::TYPES['LINEAR_HASH'] => 'LINEAR HASH',
        self::TYPES['RANGE'] => 'RANGE',
        self::TYPES['LIST'] => 'LIST',
    ];

    // values used by the fields 'default_partitioning' and 'default_subpartitioning' in the SDI files
    public const DEFINITION = [
        'IMPLICIT' => 3,
        'EXPLICIT' => 1,
    ];

    /**
     * @var array<Partition> $partitions
     */
    protected array $partitions = [];
    protected bool $explicitPartitions;
    protected bool $explicitSubpartitions;
    public function __construct(protected int    $type,
                                protected string $expression,
                                          int    $partitions_definition,
                                protected int    $subtype,
                                protected string $subExpression,
                                          int    $subpartitions_definition,
                                protected array  $sdi_partitions)
    {
        $this->explicitPartitions = ($partitions_definition === self::DEFINITION['EXPLICIT']);
        $this->explicitSubpartitions = ($subpartitions_definition === self::DEFINITION['EXPLICIT']);

        if ($type !== self::TYPES['NO_PARTITION']) {
            foreach ($this->sdi_partitions as $sdi_partition) {
                $this->partitions[] = new Partition($sdi_partition['name'], $type, $subtype, $sdi_partition['description_utf8'],
                    $sdi_partition['engine'], $sdi_partition['comment'], ($subpartitions_definition === self::DEFINITION['EXPLICIT']),
                    $sdi_partition['subpartitions'] ?? []);
            }
        }
    }

    public function __toString(): string
    {
        if ($this->type === self::TYPES['NO_PARTITION']) {
            return '';
        }

        $sql = $this->getPartitioning(true, $this->type, $this->expression);
        if (!$this->explicitPartitions) {
                $sql .= sprintf(' PARTITIONS %d', count($this->partitions));
        }

        if ($this->subtype !== self::TYPES['NO_PARTITION']) {
            $subpartitioning = $this->getPartitioning(false, $this->subtype, $this->subExpression);
            if ($subpartitioning !== '') {
                $sql .= "\n" . $subpartitioning;
            }
            if (!$this->explicitSubpartitions) {
                $sql .= sprintf(' SUBPARTITIONS %d', count($this->partitions[0]->getSubpartitions()));
            }
        }

        if ($this->explicitPartitions && count($this->partitions) !== 0) {
            $partitions = [];
            foreach ($this->partitions as $partition) {
                $partitions[] = $partition->__toString();
            }
            $sql .= sprintf("\n(\n\t%s\n)", implode(",\n\t", $partitions));
        }
        return trim($sql);
    }

    protected function getPartitioning(bool $isPartition, int $type, string $expression): string
    {
        if ($type === self::TYPES['NO_PARTITION']) {
            return '';
        }
        return sprintf('%s BY %s(%s)', ($isPartition ? 'PARTITION' : 'SUBPARTITION'), self::METHODS[$type], $expression);
    }
}