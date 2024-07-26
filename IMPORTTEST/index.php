<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Column Mapper</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
</head>

<body>
    <h1>Column Mapper</h1>
    <?php
    require_once __DIR__ . '/CSVColumnMapper.php';
    require_once __DIR__ . '/ExcelColumnMapper.php';

    // insert supplier's name
    $supplier = "";
    $supplierName = strtolower($supplier);
    $mappingFilePath = $supplierName . '-mapping.json';


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
        $filePath = $_FILES['csvFile']['tmp_name'];
        $fileInfo = pathinfo($_FILES['csvFile']['name']);
        $separator = $_POST['column_separator'];

        $columnMapping = [];
        foreach ($_POST['colMapping'] as $field => $columnIndex) {
            if ($columnIndex != "") {
                $columnMapping[$field] = $columnIndex;
            }
        }

        if ($fileInfo['extension'] == "csv") {
            $importer = new CSVColumnMapper($filePath, $separator, $columnMapping, $supplierName);

            try {
                $outputFilePath = __DIR__ . $supplierName . '_mapped_csv_file.csv';
                $outputDirectory = $importer->generateMappedCsv($outputFilePath);
                $importer->saveMapping($columnMapping, $mappingFilePath);
                $lineCount = $importer->getLineCount();

                echo "Nouveau fichier CSV : $outputDirectory,<br> il contient $lineCount lignes";
            } catch (Exception $e) {
                echo "Erreur import : " . $e->getMessage();
            }
        } else {
            $isXslx = $fileInfo['extension'] == "xlsx" ? true : false;
            $importer = new ExcelColumnMapper($filePath, $columnMapping, $supplierName, $isXslx);


            try {
                $outputFilePath = __DIR__ . $supplierName . '_mapped_csv_file.csv';
                $outputDirectory = $importer->generateMappedExcel($outputFilePath);
                $importer->saveMapping($columnMapping, $mappingFilePath);
                $lineCount = $importer->getLineCount();



                echo "Nouveau fichier excel : $outputDirectory,<br> il contient $lineCount lignes";
            } catch (Exception $e) {
                echo "Erreur import : " . $e->getMessage();
            }
        }
    }
    ?>

    <div style="margin-top: 20px; margin-bottom: 20px;">
        <form id="csvForm" method="post" enctype="multipart/form-data" action="">
            <div class="form-group">
                <label for="csvFile">Choisir un fichier :</label>
                <input type="file" class="form-control-file" id="csvFile" name="csvFile" accept=".csv, .xls, .xlsx" required>
            </div>

            <div class="form-group">
                <label for="column_separator">Séparateur de colonnes:</label>
                <select id="column_separator" name="column_separator" class="form-control">
                    <option value=",">Virgule (,)</option>
                    <option value=";">Point-virgule (;)</option>
                    <option value="&#x09">Tabulation</option>
                </select>
            </div>

            <br>
            <div id="columnMapping" class="form-group">
            </div>

            <input type="submit" value="import">

        </form>
    </div>

    <script>
        function updateColumnMapping(headers, existingMapping = {}) {
            const columnMappingDiv = document.getElementById('columnMapping');
            columnMappingDiv.innerHTML = '';

            const fields = [
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

            const requiredFields = [
                'Référence fournisseur',
                'Fournisseur',
                'Nom du produit',
                'Description du produit',
                'Prix d\'achat',
                'Images'
            ];

            fields.forEach((field, index) => {
                const div = document.createElement('div');
                div.classList.add('form-fields');
                const selectedColumnIndex = existingMapping[field];
                const isRequired = requiredFields.includes(field) ? 'required' : '';
                const asterisk = requiredFields.includes(field) ? '<span style="color: red;">*</span>' : '';
                div.innerHTML = `
            <label for="field_${index}">${field} ${asterisk}</label>
            <i class="fa fa-arrow-right"></i>
            <select id="field_${index}" name="colMapping[${field}]" class="form-select form-input" ${isRequired}>
                <option value="" ${selectedColumnIndex === undefined ? 'selected' : ''}>Choisir colonne ${field}</option>
                ${headers.map((h, i) => `<option value="${i}" ${i == selectedColumnIndex ? 'selected' : ''}>${h}</option>`).join('')}
            </select>
            <br>
        `;
                columnMappingDiv.appendChild(div);
            });
        }

        function handleFileUpload() {
            const file = document.getElementById('csvFile').files[0];
            const mappingFilePath = "<?= $mappingFilePath ?>";
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    fetch(mappingFilePath)
                        .then(response => response.json())
                        .then(existingMapping => {
                            const separator = existingMapping['separator'] || document.getElementById('column_separator').value;
                            document.getElementById('column_separator').value = separator;
                            if (file.name.endsWith('.csv')) {
                                const text = event.target.result;
                                const lines = text.split('\n');
                                if (lines.length > 0) {
                                    const headers = lines[0].split(separator);
                                    updateColumnMapping(headers, existingMapping);
                                }
                            } else if (file.name.endsWith('.xls') || file.name.endsWith('.xlsx')) {
                                const data = new Uint8Array(event.target.result);
                                const workbook = XLSX.read(data, {
                                    type: 'array'
                                });
                                const firstSheetName = workbook.SheetNames[0];
                                const worksheet = workbook.Sheets[firstSheetName];
                                const headers = XLSX.utils.sheet_to_json(worksheet, {
                                    header: 1
                                })[0];
                                updateColumnMapping(headers, existingMapping);
                            }
                        })
                        .catch(() => {
                            const separator = document.getElementById('column_separator').value;
                            if (file.name.endsWith('.csv')) {
                                const text = event.target.result;
                                const lines = text.split('\n');
                                if (lines.length > 0) {
                                    const headers = lines[0].split(separator);
                                    updateColumnMapping(headers);
                                }
                            } else if (file.name.endsWith('.xls') || file.name.endsWith('.xlsx')) {
                                const data = new Uint8Array(event.target.result);
                                const workbook = XLSX.read(data, {
                                    type: 'array'
                                });
                                const firstSheetName = workbook.SheetNames[0];
                                const worksheet = workbook.Sheets[firstSheetName];
                                const headers = XLSX.utils.sheet_to_json(worksheet, {
                                    header: 1
                                })[0];
                                updateColumnMapping(headers);
                            }
                        });
                };
                if (file.name.endsWith('.csv')) {
                    reader.readAsText(file);
                } else if (file.name.endsWith('.xls') || file.name.endsWith('.xlsx')) {
                    reader.readAsArrayBuffer(file);
                }
            }
        }

        document.getElementById('csvFile').addEventListener('change', handleFileUpload);
        document.getElementById('column_separator').addEventListener('change', handleFileUpload);
    </script>
</body>

</html>