<?php

declare(strict_types=1);


namespace App\Writer;


use App\File\AbstractCsvFile;
use App\File\FileMode;

class CsvWriter extends AbstractCsvFile
{
    protected const SEPARATOR = ',';
    protected const ENCLOSURE = '"';
    protected const ESCAPE = '\\';

    public function __construct(string $filePath)
    {
        parent::__construct($filePath, FileMode::WRITE);
    }

    /**
     * @param array<int, array<string, string>> $data
     * @return void
     */
    public function write(array $data): void
    {
        $headers = $this->getHeaders($data);
        $this->writeLine($headers);

        foreach ($data as $row) {
            $this->writeLine($row);
        }
    }

    /**
     * @param array<int, array<string, string>> $data
     * @return array<int, string>
     */
    protected function getHeaders(array $data): array
    {
        $headers = [];
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }
        return $headers;
    }

    /**
     * @param array<mixed, string> $data
     * @return void
     */
    protected function writeLine(array $data): void
    {
        fputcsv($this->handle, $data, self::SEPARATOR, self::ENCLOSURE, self::ESCAPE);
    }
}
