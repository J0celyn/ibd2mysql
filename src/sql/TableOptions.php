<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql\sql;

/**
 * This class uses information from an SDI file to recreate the definition of a MySQL table's options
 */
class TableOptions
{
    protected ?int $avgRowLength = null;
    protected ?int $keyBlockSize = null;
    protected ?bool $statsAutoRecalc = null;
    protected ?int $statsSamplePages = null;
    protected ?string $encryption = null;

    public function __construct(string $dd_options)
    {
        $options = [];
        $tmp = explode(';', $dd_options);
        foreach ($tmp as $variable_value) {
            $variable_value = trim($variable_value);
            if ($variable_value === '') {
                continue;
            }
            $tmp2 = explode('=', $variable_value);
            $options[$tmp2[0]] = $tmp2[1];
        }
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'avg_row_length':
                    $this->setAvgRowLength($value);
                    break;

                case 'encrypt_type':
                    $this->setEncryption($value);
                    break;

                case 'key_block_size':
                    $this->setKeyBlockSize($value);
                    break;

                /*                case 'keys_disabled':
                                    $this->setKeysDisabled($value);
                                    break;

                                case 'pack_record':
                                    $this->setPackRecord($value);
                                    break;

                                case 'row_type':
                                    $this->setRowType($value);
                                    break;
                */

                case 'stats_auto_recalc':
                    $this->setStatsAutoRecalc($value);
                    break;

                case 'stats_sample_pages':
                    $this->setStatsSamplePages($value);
                    break;

                default:
                    break;
            }
        }
    }

    private function setEncryption(?string $value): void
    {
        $this->encryption = $value;
    }

    private function setKeyBlockSize(?int $value): void
    {
        $this->keyBlockSize = $value;
    }

    private function setStatsAutoRecalc(?bool $value): void
    {
        $this->statsAutoRecalc = $value;
    }

    private function setStatsSamplePages(?int $value): void
    {
        $this->statsSamplePages = $value;
    }

    public function getKeyBlockSize(): ?int
    {
        return $this->keyBlockSize;
    }

    public function __toString(): string
    {
        $options = [];
        if ($this->getAvgRowLength() !== 0) {
            $options[] = sprintf('AVG_ROW_LENGTH=%d', $this->getAvgRowLength());
        }
        if ($this->getStatsAutoRecalc() !== null) {
            $options[] = sprintf('STATS_AUTO_RECALC=%d', $this->getStatsAutoRecalc());
        }
        if ($this->getStatsSamplePages() > 0) {
            $options[] = sprintf('STATS_SAMPLE_PAGES=%d', $this->getStatsSamplePages());
        }
        $options[] = ($this->getEncryption() === null) ? '' : sprintf("ENCRYPTION='%s'", $this->getEncryption());

        return implode(' ', $options);
    }

    public function getAvgRowLength(): ?int
    {
        return $this->avgRowLength;
    }

    public function setAvgRowLength(?int $value): void
    {
        $this->avgRowLength = $value;
    }

    public function getStatsAutoRecalc(): ?bool
    {
        return $this->statsAutoRecalc;
    }

    public function getStatsSamplePages(): ?int
    {
        return $this->statsSamplePages;
    }

    public function getEncryption(): ?string
    {
        return $this->encryption;
    }
}