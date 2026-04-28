<?php
require_once 'config/config.php';
set_time_limit(0);
ini_set('memory_limit', '1024M');

$baseDir = 'Assorted Medicine Dataset of Bangladesh/';

function getCsvData($filename) {
    global $baseDir;
    $path = $baseDir . $filename;
    if (!file_exists($path)) return [];
    $rows = [];
    if (($handle = fopen($path, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 10000, ",");
        while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
            if (count($headers) == count($data)) {
                $rows[] = array_combine($headers, $data);
            }
        }
        fclose($handle);
    }
    return $rows;
}

try {
    echo "Starting import...\n";

    // 1. Load Generic Data
    echo "Loading generic.csv...\n";
    $genericsRaw = getCsvData('generic.csv');
    $genericsMap = [];
    foreach ($genericsRaw as $g) {
        $name = trim($g['generic name'] ?? '');
        if ($name) {
            $genericsMap[strtolower($name)] = $g;
        }
    }

    // 2. Load and Insert Medicines from medicine.csv
    echo "Loading medicine.csv...\n";
    $medicinesRaw = getCsvData('medicine.csv');
    $stmt = $db->prepare("INSERT INTO medicine_master_data (brand_name, generic_name, type, slug, dosage_form, strength, manufacturer, package_container, package_size, drug_class, indication, indication_description, therapeutic_class_description, pharmacology_description, dosage_description, administration_description, interaction_description, contraindications_description, side_effects_description, pregnancy_and_lactation_description, precautions_description, pediatric_usage_description, overdose_effects_description, storage_conditions_description, monograph_link) 
        VALUES (:bn, :gn, :type, :slug, :df, :strength, :man, :pc, :ps, :dc, :ind, :ind_desc, :tc_desc, :ph_desc, :dos_desc, :adm_desc, :int_desc, :con_desc, :se_desc, :pl_desc, :pre_desc, :ped_desc, :od_desc, :sc_desc, :link)");

    foreach ($medicinesRaw as $m) {
        $gn = trim($m['generic'] ?? '');
        $gData = $genericsMap[strtolower($gn)] ?? [];
        
        $stmt->execute([
            'bn' => $m['brand name'] ?? null,
            'gn' => $gn,
            'type' => $m['type'] ?? null,
            'slug' => $m['slug'] ?? null,
            'df' => $m['dosage form'] ?? null,
            'strength' => $m['strength'] ?? null,
            'man' => $m['manufacturer'] ?? null,
            'pc' => $m['package container'] ?? null,
            'ps' => $m['Package Size'] ?? null,
            'dc' => $gData['drug class'] ?? null,
            'ind' => $gData['indication'] ?? null,
            'ind_desc' => $gData['indication description'] ?? null,
            'tc_desc' => $gData['therapeutic class description'] ?? null,
            'ph_desc' => $gData['pharmacology description'] ?? null,
            'dos_desc' => $gData['dosage description'] ?? null,
            'adm_desc' => $gData['administration description'] ?? null,
            'int_desc' => $gData['interaction description'] ?? null,
            'con_desc' => $gData['contraindications description'] ?? null,
            'se_desc' => $gData['side effects description'] ?? null,
            'pl_desc' => $gData['pregnancy and lactation description'] ?? null,
            'pre_desc' => $gData['precautions description'] ?? null,
            'ped_desc' => $gData['pediatric usage description'] ?? null,
            'od_desc' => $gData['overdose effects description'] ?? null,
            'sc_desc' => $gData['storage conditions description'] ?? null,
            'link' => $gData['monograph link'] ?? null
        ]);
    }
    echo "Inserted " . count($medicinesRaw) . " rows from medicine.csv\n";

    // 3. Load and Insert from the long-named file
    $longFileName = 'Medicinal Products in Bangladesh A Dataset of Generic and Brand Names, Dosages, and Manufacturers.csv';
    echo "Loading $longFileName...\n";
    $extraRaw = getCsvData($longFileName);
    foreach ($extraRaw as $m) {
        $gn = trim($m['genericName'] ?? '');
        $gData = $genericsMap[strtolower($gn)] ?? [];
        
        // Avoid duplicates if brand name already exists? 
        // User said "dont skip any data", so I'll just insert everything.
        
        $stmt->execute([
            'bn' => $m['brandName'] ?? null,
            'gn' => $gn,
            'type' => null,
            'slug' => null,
            'df' => $m['dosageType'] ?? null,
            'strength' => $m['strength'] ?? null,
            'man' => $m['manufacturer'] ?? null,
            'pc' => $m['packageMark'] ?? null,
            'ps' => null,
            'dc' => $gData['drug class'] ?? null,
            'ind' => $gData['indication'] ?? null,
            'ind_desc' => $gData['indication description'] ?? null,
            'tc_desc' => $gData['therapeutic class description'] ?? null,
            'ph_desc' => $gData['pharmacology description'] ?? null,
            'dos_desc' => $gData['dosage description'] ?? null,
            'adm_desc' => $gData['administration description'] ?? null,
            'int_desc' => $gData['interaction description'] ?? null,
            'con_desc' => $gData['contraindications description'] ?? null,
            'se_desc' => $gData['side effects description'] ?? null,
            'pl_desc' => $gData['pregnancy and lactation description'] ?? null,
            'pre_desc' => $gData['precautions description'] ?? null,
            'ped_desc' => $gData['pediatric usage description'] ?? null,
            'od_desc' => $gData['overdose effects description'] ?? null,
            'sc_desc' => $gData['storage conditions description'] ?? null,
            'link' => $gData['monograph link'] ?? null
        ]);
    }
    echo "Inserted " . count($extraRaw) . " rows from extra file.\n";

    // 4. Insert other metadata as separate rows (since they don't map to a specific brand-generic pair easily in a flat table)
    // Actually, dosage form, drug class, indication, manufacturer are usually lookup tables.
    // But user asked for ONE table for ALL data.
    
    $metaStmt = $db->prepare("INSERT INTO medicine_master_data (type, meta_data) VALUES (:type, :meta)");
    
    echo "Loading dosage form.csv...\n";
    foreach (getCsvData('dosage form.csv') as $row) $metaStmt->execute(['type'=>'DOSAGE_FORM_META', 'meta'=>json_encode($row)]);
    
    echo "Loading drug class.csv...\n";
    foreach (getCsvData('drug class.csv') as $row) $metaStmt->execute(['type'=>'DRUG_CLASS_META', 'meta'=>json_encode($row)]);
    
    echo "Loading indication.csv...\n";
    foreach (getCsvData('indication.csv') as $row) $metaStmt->execute(['type'=>'INDICATION_META', 'meta'=>json_encode($row)]);
    
    echo "Loading manufacturer.csv...\n";
    foreach (getCsvData('manufacturer.csv') as $row) $metaStmt->execute(['type'=>'MANUFACTURER_META', 'meta'=>json_encode($row)]);

    echo "Import complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
