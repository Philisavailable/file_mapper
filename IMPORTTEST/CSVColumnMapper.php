<?php
require_once __DIR__ . '/ColumnMapper.php';

class CSVColumnMapper extends ColumnMapper
{

    private $filePath;
    private $separator;

    public function __construct($filePath, $separator, $columnMapping, $supplierName)
    {
        parent::__construct($columnMapping, $supplierName);
        $this->filePath = $filePath;
        $this->separator = $separator;
    }

    public function generateMappedCsv($outputFilePath)
    {
        if (!file_exists($this->filePath)) {
            throw new Exception("CSV absent");
        }

        $csvFile = new SplFileObject($this->filePath, 'r');
        $csvFile->setFlags(
            SplFileObject::READ_CSV |
                SplFileObject::READ_AHEAD |
                SplFileObject::SKIP_EMPTY |
                SplFileObject::DROP_NEW_LINE
        );

        $headers = $csvFile->fgetcsv($this->separator);

        $mappedData = [];
        $mappedData[] = $this->predefinedFields;
        $mappedData[] = array_fill(0, count($this->predefinedFields), '');

        while (!$csvFile->eof()) {
            $row = $csvFile->fgetcsv($this->separator);

            if (!$this->isValidRow($row)) {
                continue;
            }

            $mappedRow = $this->mapRow($row);
            $mappedData[] = $mappedRow;
        }

        $outputFile = new SplFileObject($outputFilePath, 'w');
        foreach ($mappedData as $data) {
            $outputFile->fputcsv($data, ";");
        }

        return $outputFilePath;
    }

    public function getLineCount()
    {
        $csvFile = new SplFileObject($this->filePath, 'r');
        $csvFile->seek(PHP_INT_MAX);
        return $csvFile->key() + 1;
    }

    public function saveMapping($mapping, $filePath)
    {
        $mapping['separator'] = $this->separator;
        parent::saveMapping($mapping, $filePath);
    }
}
