<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql\sql;

class Charset
{
    public function __construct(protected int $collation_id, protected string $collation_name, protected string $character_set_name, protected int $character_set_max_length)
    {
    }

    public function getCollationName(): string
    {
        return $this->collation_name;
    }

    public function getCharacterSetName(): string
    {
        return $this->character_set_name;
    }

    public function getCharacterSetMaxLength(): int
    {
        return $this->character_set_max_length;
    }
}