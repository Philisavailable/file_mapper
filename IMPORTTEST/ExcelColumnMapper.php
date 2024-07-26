<?php
require_once __DIR__ . '/ColumnMapper.php';
require_once __DIR__ . '/../src/SimpleXLSX.php';
require_once __DIR__ . '/../src/SimpleXLS.php';

use Shuchkin\SimpleXLSX;

class ExcelColumnMapper extends ColumnMapper
{

    private $filePath;
    private $isXlsx;

    public function __construct($filePath, $columnMapping, $supplierName, $isXlsx)
    {
        parent::__construct($columnMapping, $supplierName);
        $this->filePath = $filePath;
        $this->isXlsx = $isXlsx;
    }

    public function generateMappedExcel($outputFilePath)
    {
        if (!file_exists($this->filePath)) {
            throw new Exception("Fichier Excel absent");
        }

        $data = [];
        if ($this->isXlsx) {
            $xlsx = SimpleXLSX::parse($this->filePath);
            if (!$xlsx) {
                throw new Exception(SimpleXLSX::parseError());
            }
            $data = $xlsx->rows();
        } else {
            $xls = SimpleXLS::parse($this->filePath);
            if (!$xls) {
                throw new Exception(SimpleXLS::parseError());
            }
            $data = $xls->rows();
        }

        $mappedData = [];
        $mappedData[] = $this->predefinedFields;
        $mappedData[] = array_fill(0, count($this->predefinedFields), '');

        foreach (array_slice($data, 1) as $row) {
            if (!$this->isValidRow($row)) {
                continue;
            }

            $mappedRow = $this->mapRow($row);
            $mappedData[] = $mappedRow;
        }

        $outputFile = fopen($outputFilePath, 'w');
        foreach ($mappedData as $data) {
            fputcsv($outputFile, $data, ';');
        }
        fclose($outputFile);

        return $outputFilePath;
    }

    public function getLineCount()
    {
        $data = [];
        if ($this->isXlsx) {
            $xlsx = SimpleXLSX::parse($this->filePath);
            $data = $xlsx->rows();
        } else {
            $xls = SimpleXLS::parse($this->filePath);
            $data = $xls->rows();
        }

        return count($data);
    }
}
