<?php
require_once __DIR__ . '/../config/config.php';

$meds = [
    ['generic' => 'Folic acid', 'brand' => 'Folic acid', 'manufacturer' => 'Riyadh pharma', 'origin' => 'KSA'],
    ['generic' => 'Loratadine', 'brand' => 'Lorinase', 'manufacturer' => 'Spimaco', 'origin' => 'KSA'],
    ['generic' => 'Diclofenac potassium', 'brand' => 'Rapidus', 'manufacturer' => 'Tabuk', 'origin' => 'KSA'],
    ['generic' => 'Quetiapine', 'brand' => 'Adazio', 'manufacturer' => 'Riyadh pharma', 'origin' => 'KSA'],
    ['generic' => 'Omeprazole', 'brand' => 'Gasec', 'manufacturer' => 'Batterjee pharma', 'origin' => 'KSA'],
    ['generic' => 'Mebeverine hydrochloride', 'brand' => 'Meva', 'manufacturer' => 'Jamjoom pharma', 'origin' => 'KSA'],
    ['generic' => 'Levofloxacin', 'brand' => 'Levoflox', 'manufacturer' => 'National Pharmaceutical Industries', 'origin' => 'Oman'],
    ['generic' => 'Clarithromycin', 'brand' => 'Clarixin', 'manufacturer' => 'Pharma International Company', 'origin' => 'Jordan'],
    ['generic' => 'Loratadine', 'brand' => 'LoraS', 'manufacturer' => 'Dammam Pharma', 'origin' => 'KSA'],
    ['generic' => 'Ranitidine hydrochloride', 'brand' => 'Zydac', 'manufacturer' => 'Jamjoom pharma', 'origin' => 'KSA'],
    ['generic' => 'Prednisolone', 'brand' => 'Predo', 'manufacturer' => 'Jazeera pharma', 'origin' => 'KSA'],
    ['generic' => 'Azithromycin', 'brand' => 'Zocin', 'manufacturer' => 'The Arab Pharmaceutical Industries', 'origin' => 'Jordan'],
    ['generic' => 'Telmisartan', 'brand' => 'Nizortan', 'manufacturer' => 'Tabuk pharma', 'origin' => 'KSA'],
    ['generic' => 'Ferric hydroxide polymaltose', 'brand' => 'Ferose-F', 'manufacturer' => 'Spimaco', 'origin' => 'KSA'],
    ['generic' => 'Amoxicillin', 'brand' => 'Megamox', 'manufacturer' => 'Jazeera pharma', 'origin' => 'KSA'],
    ['generic' => 'Oseltamivir', 'brand' => 'Oselta', 'manufacturer' => 'Jamjoom pharma', 'origin' => 'KSA'],
    ['generic' => 'Cetirizine', 'brand' => 'Artiz', 'manufacturer' => 'Tabuk', 'origin' => 'KSA'],
    ['generic' => 'Amoxicillin and clavulanic acid', 'brand' => 'Amoclan', 'manufacturer' => 'Hikma Pharma', 'origin' => 'Jordan'],
    ['generic' => 'Ibuprofen', 'brand' => 'Sapofen', 'manufacturer' => 'Spimaco', 'origin' => 'KSA'],
    ['generic' => 'Chlorzoxazone and Paracetamol', 'brand' => 'Relaxon', 'manufacturer' => 'Jamjoom pharma', 'origin' => 'KSA'],
    ['generic' => 'Metronidazole', 'brand' => 'Riazole', 'manufacturer' => 'Riyadh pharma', 'origin' => 'KSA'],
    ['generic' => 'Azithromycin', 'brand' => 'Azionce', 'manufacturer' => 'Jamjoom pharma', 'origin' => 'KSA'],
    ['generic' => 'Ranitidine hydrochloride', 'brand' => 'Ranid', 'manufacturer' => 'Tabuk', 'origin' => 'KSA'],
    ['generic' => 'Amoxicillin', 'brand' => 'Moxal', 'manufacturer' => 'Julphar', 'origin' => 'UAE'],
    ['generic' => 'Acetylsalicylic acid', 'brand' => 'Disprin', 'manufacturer' => 'Riyadh pharma', 'origin' => 'KSA'],
    ['generic' => 'Paracetamol', 'brand' => 'Fevadol', 'manufacturer' => 'Spimaco', 'origin' => 'KSA'],
    ['generic' => 'Lansoprazole', 'brand' => 'Takepron 15', 'manufacturer' => 'The Arab Pharmaceutical Industries', 'origin' => 'Jordan'],
    ['generic' => 'Loratadine', 'brand' => 'Lorine', 'manufacturer' => 'Spimaco', 'origin' => 'KSA'],
    ['generic' => 'Domperidone', 'brand' => 'Dompy', 'manufacturer' => 'Jamjoom pharma', 'origin' => 'KSA'],
    ['generic' => 'Metronidazole', 'brand' => 'flazol', 'manufacturer' => 'Tabuk pharmaceuticals', 'origin' => 'KSA'],
    ['generic' => 'Glyburide and Metformin', 'brand' => 'Glucovance', 'manufacturer' => 'Riyadh pharma', 'origin' => 'KSA'],
    ['generic' => 'Ibuprofen', 'brand' => 'Profinal', 'manufacturer' => 'Julphar', 'origin' => 'UAE'],
];

$stmt = $db->prepare("INSERT INTO medicine_master_data (brand_name, generic_name, manufacturer, dosage_form, type, slug, meta_data) 
                      VALUES (:brand, :generic, :manufacturer, 'Tablet/Capsule', 'International', :slug, :meta)");

$count = 0;
foreach ($meds as $m) {
    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $m['brand']));
    
    // Check if exists
    $check = $db->prepare("SELECT id FROM medicine_master_data WHERE brand_name = :b AND generic_name = :g LIMIT 1");
    $check->execute([':b' => $m['brand'], ':g' => $m['generic']]);
    if ($check->rowCount() > 0) {
        echo "Skipping existing: {$m['brand']}\n";
        continue;
    }
    
    $stmt->execute([
        ':brand' => $m['brand'],
        ':generic' => $m['generic'],
        ':manufacturer' => $m['manufacturer'],
        ':slug' => $slug,
        ':meta' => json_encode(['origin' => $m['origin'], 'source' => 'International Drug List'])
    ]);
    $count++;
    echo "Inserted: {$m['brand']} ({$m['origin']})\n";
}

echo "\nTotal inserted: $count\n";
echo "Done.\n";
