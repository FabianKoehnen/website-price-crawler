<?php

declare(strict_types=1);

namespace App\File;

class AbstractCsvFile extends AbstractFile
{
    protected const SEPARATOR = ';';
    protected const ENCLOSURE = '"';
    protected const ESCAPE = '\\';
}
