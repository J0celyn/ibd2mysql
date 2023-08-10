<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql\sql;

use function in_array;

/**
 * This class uses information from an SDI file to recreate the definition of a MySQL table's column
 */
class Column
{
    public const STRING_CODES = [
        'char',
        'varbinary',
        'varchar',
        'tinyblob',
        'tinytext',
        'blob',
        'text',
        'mediumblob',
        'mediumtext',
        'longblob',
        'longtext',
    ];

    public const NO_QUOTE_DEFAULT = [
        'binary',
        'bit',
        'timestamp'
    ];

    protected bool $isNullable;
    protected bool $isZeroFill;
    protected bool $isUnsigned;
    protected bool $isAutoIncrement;
    protected bool $isVirtual;
    protected string $comment;
    protected int $collationId;
    protected string $generationExpression = '';
    protected ?string $defaultValue;
    protected bool $hasDefaultValue;

    /**
     * @param array<Charset> $charsets
     */
    public function __construct(protected string $name, protected string $type, protected array $charsets)
    {
    }

    public function __toString(): string
    {
        $sql = '`' . $this->getName() . '` ' . $this->getType();
        $sql .= ($this->isUnsigned() ? ' UNSIGNED' : '');
        $sql .= ($this->isZeroFill() ? ' ZEROFILL' : '');

        if ($this->getGenerationExpression() === '') {
            if (in_array($this->getBaseType(), self::STRING_CODES, true)) {
                $charset = $this->charsets[$this->getCollationId()]->getCharacterSetName();
                if ($charset !== 'binary') {
                    $sql .= ' CHARACTER SET ' . $charset;
                }
                $sql .= ' COLLATE ' . $this->charsets[$this->getCollationId()]->getCollationName();
            }

            $sql .= ' ' . ($this->isNullable() ? 'NULL' : 'NOT NULL');

            if ($this->isHasDefaultValue()) {
                $def_val = $this->getDefaultValue();
                if ($def_val === null) {
                    $sql .= ' DEFAULT NULL';
                } elseif ($def_val !== '') {
                    /* necessary to avoid errors when SQL modes NO_ZERO_DATE and NO_ZERO_IN_DATE are enabled
                    @see https://dev.mysql.com/doc/refman/8.1/en/sql-mode.html
                    */
                    if ($this->getBaseType() === 'date' && $def_val === '0000-00-00') {
                        $def_val = '0000-01-01';
                    }
                    if ($this->getBaseType() === 'datetime' && $def_val === '0000-00-00 00:00:00') {
                        $def_val = '0000-01-01 00:00:00';
                    }

                    if (!in_array($this->getBaseType(), self::NO_QUOTE_DEFAULT, true)) {
                        $sql .= sprintf(" DEFAULT '%s'", $def_val);
                    } elseif ($def_val !== '0x') {
                        $sql .= sprintf(' DEFAULT %s', $def_val);
                    }
                }
            }
            if ($this->isAutoIncrement()) {
                $sql .= ' AUTO_INCREMENT';
            }
        } else {
            if (in_array($this->getBaseType(), self::STRING_CODES, true)) {
                $sql .= ' COLLATE ' . $this->charsets[$this->getCollationId()]->getCollationName();
            }
            $sql .= sprintf(' AS (%s) %s', $this->getGenerationExpression(), ($this->isVirtual() ? 'VIRTUAL' : 'STORED'));
            $sql .= ' ' . ($this->isNullable() ? 'NULL' : 'NOT NULL');
        }
        if ($this->getComment() !== '') {
            $sql .= sprintf(" COMMENT '%s'", addslashes($this->getComment()));
        }

        return $sql;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isUnsigned(): bool
    {
        return $this->isUnsigned;
    }

    public function setIsUnsigned(bool $isUnsigned): void
    {
        $this->isUnsigned = $isUnsigned;
    }

    public function isZeroFill(): bool
    {
        return $this->isZeroFill;
    }

    public function setIsZeroFill(bool $isZeroFill): void
    {
        $this->isZeroFill = $isZeroFill;
    }

    public function getGenerationExpression(): string
    {
        return $this->generationExpression;
    }

    public function setGenerationExpression(string $generationExpression): void
    {
        $this->generationExpression = $generationExpression;
    }

    public function getBaseType(): string
    {
        return strtolower(explode('(', $this->type)[0]);
    }

    public function getCollationId(): int
    {
        return $this->collationId;
    }

    public function setCollationId(int $collationId): void
    {
        $this->collationId = $collationId;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function setIsNullable(bool $isNullable): void
    {
        $this->isNullable = $isNullable;
    }

    public function isHasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    public function setHasDefaultValue(bool $hasDefaultValue): void
    {
        $this->hasDefaultValue = $hasDefaultValue;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function isAutoIncrement(): bool
    {
        return $this->isAutoIncrement;
    }

    public function setIsAutoIncrement(bool $isAutoIncrement): void
    {
        $this->isAutoIncrement = $isAutoIncrement;
    }

    public function isVirtual(): bool
    {
        return $this->isVirtual;
    }

    public function setIsVirtual(bool $isVirtual): void
    {
        $this->isVirtual = $isVirtual;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function __debugInfo(): ?array
    {
        $t = get_object_vars($this);
        unset($t['charsets']);
        return $t;
    }
}