<?php

declare(strict_types=1);

namespace App\Reader;

use App\File\AbstractCsvFile;
use App\File\FileMode;
use Iterator;
use RuntimeException;

/**
 * @phpstan-implements Iterator<array<string, string>>
 */
class CsvReader extends AbstractCsvFile implements Iterator
{
    /**
     * @var array<string>
     */
    protected array $headers;

    /**
     * @var array<?string>
     */
    protected array $currentRow;

    protected int $key = 0;

    public function __construct(string $filePath)
    {
        parent::__construct($filePath, FileMode::READ);
    }

    /**
     * @return ?array<string, string>
     */
    public function current(): ?array
    {
        if (0 === $this->key) {
            $this->readLine();
        }
        if (null === $this->currentRow[0]) {
            return null;
        }

        $data = array_combine($this->headers, $this->currentRow);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid CSV file. Error on line '.($this->key() + 1));
        }

        return $data;
    }

    public function next(): void
    {
        $this->readLine();
        ++$this->key;
    }

    public function key(): int
    {
        return $this->key;
    }

    public function getCurrentLine(): int
    {
        return $this->key + 1;
    }

    public function valid(): bool
    {
        return !feof($this->handle);
    }

    public function rewind(): void
    {
        rewind($this->handle);
        $this->key = 0;
        $tmpHeaders = fgetcsv($this->handle, 0, self::SEPARATOR, self::ENCLOSURE, self::ESCAPE);
        if (!is_array($tmpHeaders)) {
            throw new RuntimeException('Invalid CSV file. Error in Line 1');
        }
        $this->headers = $tmpHeaders;
    }

    private function readLine(): void
    {
        $read = fgetcsv($this->handle, 0, self::SEPARATOR, self::ENCLOSURE, self::ESCAPE);
        if (false === $read) {
            $this->currentRow = [];
        } else {
            $this->currentRow = $read;
        }
    }
}
