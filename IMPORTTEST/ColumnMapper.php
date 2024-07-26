<?php

class ColumnMapper
{

    protected $columnMapping;
    protected $supplierName;
    protected $predefinedFields = [
        'Référence fournisseur',
        'Fournisseur',
        'Nom du produit',
        'Description du produit',
        'Prix d\'achat',
        'Images',
        'Catégories',
        'Coloris',
        'Tailles',
        'Références déclinaisons',
        'Poids',
        'Dimensions',
        'Marque',
        'Sur devis uniquement',
        'Toujours en stock',
        'Prix manuel',
        'Colisage',
        'Délai livraison',
        'Pays fabrication (code ISO)'
    ];

    public function __construct($columnMapping, $supplierName)
    {
        $this->columnMapping = $columnMapping;
        $this->supplierName = strtolower($supplierName);
    }

    protected function mapRow($row)
    {
        $mappedRow = [];

        foreach ($this->predefinedFields as $field) {
            if (isset($this->columnMapping[$field]) && isset($row[$this->columnMapping[$field]])) {
                $mappedRow[] = $row[$this->columnMapping[$field]];
            } elseif (in_array($field, ['Sur devis uniquement', 'Toujours en stock', 'Prix manuel'])) {
                $mappedRow[] = "0";
            } else {
                $mappedRow[] = "";
            }
        }

        return $mappedRow;
    }

    protected function isValidRow($row)
    {
        return $row !== false && is_array($row) && (
            count(array_filter($row, function ($value) {
                return trim($value) !== '';
            })) > 0 &&
            count(array_filter($row, function ($value) {
                return strtolower(trim($value)) === strtolower($this->supplierName);
            })) > 0
        );
    }

    public function saveMapping($mapping, $filePath)
    {
        file_put_contents($filePath, json_encode($mapping));
    }
}
