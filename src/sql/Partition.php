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
class Partition
{
    /**
     * @var array<Partition> $subpartitions
     */
    protected array $subpartitions;

    public function __construct(protected string $name, protected int $typeId, ?int $subtypeId, protected string $values,
                                protected string $engine, protected string $comment, protected bool $explicitSubpartitions, array $sdi_subpartitions)
    {
        $this->subpartitions = [];
        foreach ($sdi_subpartitions as $sdi_subpartition) {
            $this->subpartitions[] = new Partition($sdi_subpartition['name'], $subtypeId, null, $sdi_subpartition['description_utf8'],
                $sdi_subpartition['engine'], $sdi_subpartition['comment'], $explicitSubpartitions, $sdi_subpartition['subpartitions']);
        }
    }

    public function __toString(): string
    {
        return $this->getText();
    }

    protected function getText(bool $isPartition = true): string
    {
        $sql = '';
        $word = ($isPartition ? 'PARTITION' : 'SUBPARTITION');
        switch ($this->typeId) {
            case Partitioning::TYPES['HASH']:
            case Partitioning::TYPES['LINEAR_HASH']:
            case Partitioning::TYPES['KEY']:
                $sql = sprintf('%s %s', $word, $this->name);
                break;

            case Partitioning::TYPES['LIST']:
                $sql = sprintf('%s %s VALUES IN (%s)', $word, $this->name, $this->values);
                break;

            case Partitioning::TYPES['RANGE']:
                $value = ($this->values === 'MAXVALUE' ? 'MAXVALUE' : '(' . $this->values . ')');
                $sql = sprintf('%s %s VALUES LESS THAN %s', $word, $this->name, $value);
                break;
        }
        $subpartitions = [];
        if ($this->explicitSubpartitions && count($this->subpartitions) !== 0) {
            foreach ($this->subpartitions as $subpartition) {
                $subpartitions[] = $subpartition->getText(false);
            }
            $sql .= "\n\t(\n\t\t" . implode(",\n\t\t", $subpartitions) . "\n\t)";
        } else {
            $sql .= sprintf(' ENGINE=%s', $this->engine);
        }
        return $sql;
    }

    public function getSubpartitions(): array
    {
        return $this->subpartitions;
    }
}