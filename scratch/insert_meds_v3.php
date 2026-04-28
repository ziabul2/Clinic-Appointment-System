<?php
require_once __DIR__ . '/../config/config.php';

$medicines = [
    ['brand_name' => 'Alben', 'generic_name' => 'Albendazole', 'dosage_form' => 'Tablet', 'strength' => '400mg', 'drug_class' => 'Anthelmintic'],
    ['brand_name' => 'Alcet', 'generic_name' => 'Levocetirizine Hydrochloride', 'dosage_form' => 'Tablet', 'strength' => '5mg', 'drug_class' => 'Antihistamine'],
    ['brand_name' => 'Aldoc', 'generic_name' => 'Methyldopa', 'dosage_form' => 'Tablet', 'strength' => '250mg', 'drug_class' => 'Antihypertensive'],
    ['brand_name' => 'Alfen', 'generic_name' => 'Diclofenac Sodium', 'dosage_form' => 'Tablet', 'strength' => '50mg', 'drug_class' => 'NSAID'],
    ['brand_name' => 'Almex', 'generic_name' => 'Albendazole', 'dosage_form' => 'Tablet', 'strength' => '400mg', 'drug_class' => 'Anthelmintic'],
    ['brand_name' => 'Almod', 'generic_name' => 'Amlodipine', 'dosage_form' => 'Tablet', 'strength' => '5mg', 'drug_class' => 'Calcium Channel Blocker'],
    ['brand_name' => 'Alpaz', 'generic_name' => 'Alprazolam', 'dosage_form' => 'Tablet', 'strength' => '0.25mg/0.5mg', 'drug_class' => 'Anxiolytic'],
    ['brand_name' => 'Altacef', 'generic_name' => 'Cefuroxime Axetil', 'dosage_form' => 'Tablet', 'strength' => '250mg/500mg', 'drug_class' => 'Cephalosporin Antibiotic'],
    ['brand_name' => 'Alu', 'generic_name' => 'Aluminium Hydroxide + Magnesium Hydroxide', 'dosage_form' => 'Suspension', 'strength' => '250mg + 250mg / 5ml', 'drug_class' => 'Antacid'],
    ['brand_name' => 'Alugel', 'generic_name' => 'Aluminium Hydroxide + Magnesium Hydroxide', 'dosage_form' => 'Tablet', 'strength' => '250mg + 250mg', 'drug_class' => 'Antacid'],
    ['brand_name' => 'Amaryl', 'generic_name' => 'Glimepiride', 'dosage_form' => 'Tablet', 'strength' => '1mg/2mg', 'drug_class' => 'Antidiabetic'],
    ['brand_name' => 'Ambe', 'generic_name' => 'Ambroxol Hydrochloride', 'dosage_form' => 'Syrup', 'strength' => '15mg / 5ml', 'drug_class' => 'Mucolytic'],
    ['brand_name' => 'Ambrox', 'generic_name' => 'Ambroxol Hydrochloride', 'dosage_form' => 'Syrup', 'strength' => '15mg / 5ml', 'drug_class' => 'Mucolytic'],
    ['brand_name' => 'Amicin', 'generic_name' => 'Amikacin', 'dosage_form' => 'Injection', 'strength' => '100mg/250mg/500mg', 'drug_class' => 'Aminoglycoside Antibiotic'],
    ['brand_name' => 'Amilo', 'generic_name' => 'Amiloride Hydrochloride + Hydrochlorothiazide', 'dosage_form' => 'Tablet', 'strength' => '5mg + 50mg', 'drug_class' => 'Diuretic'],
    ['brand_name' => 'Amlocet', 'generic_name' => 'Amlodipine + Cetirizine', 'dosage_form' => 'Tablet', 'strength' => '5mg + 10mg', 'drug_class' => 'Antihypertensive + Antihistamine'],
    ['brand_name' => 'Amlodin', 'generic_name' => 'Amlodipine', 'dosage_form' => 'Tablet', 'strength' => '5mg', 'drug_class' => 'Calcium Channel Blocker'],
    ['brand_name' => 'Amlogard', 'generic_name' => 'Amlodipine', 'dosage_form' => 'Tablet', 'strength' => '5mg', 'drug_class' => 'Calcium Channel Blocker'],
    ['brand_name' => 'Amlopin', 'generic_name' => 'Amlodipine', 'dosage_form' => 'Tablet', 'strength' => '5mg', 'drug_class' => 'Calcium Channel Blocker'],
    ['brand_name' => 'Amoclav', 'generic_name' => 'Amoxicillin + Clavulanic Acid', 'dosage_form' => 'Tablet', 'strength' => '625mg', 'drug_class' => 'Penicillin Antibiotic'],
    ['brand_name' => 'Amotil', 'generic_name' => 'Amoxicillin', 'dosage_form' => 'Capsule', 'strength' => '250mg/500mg', 'drug_class' => 'Penicillin Antibiotic'],
    ['brand_name' => 'Amox', 'generic_name' => 'Amoxicillin', 'dosage_form' => 'Capsule', 'strength' => '500mg', 'drug_class' => 'Penicillin Antibiotic'],
    ['brand_name' => 'Amoxiclav', 'generic_name' => 'Amoxicillin + Clavulanic Acid', 'dosage_form' => 'Tablet', 'strength' => '625mg', 'drug_class' => 'Penicillin Antibiotic'],
    ['brand_name' => 'Amoxin', 'generic_name' => 'Amoxicillin', 'dosage_form' => 'Capsule', 'strength' => '500mg', 'drug_class' => 'Penicillin Antibiotic'],
    ['brand_name' => 'Amoxit', 'generic_name' => 'Amoxicillin', 'dosage_form' => 'Capsule', 'strength' => '500mg', 'drug_class' => 'Penicillin Antibiotic'],
    ['brand_name' => 'Ampect', 'generic_name' => 'Ampicillin', 'dosage_form' => 'Capsule', 'strength' => '250mg/500mg', 'drug_class' => 'Penicillin Antibiotic'],
    ['brand_name' => 'Amphicin', 'generic_name' => 'Ampicillin', 'dosage_form' => 'Capsule', 'strength' => '500mg', 'drug_class' => 'Penicillin Antibiotic'],
    ['brand_name' => 'Amphicol', 'generic_name' => 'Chloramphenicol', 'dosage_form' => 'Capsule', 'strength' => '250mg', 'drug_class' => 'Broad-spectrum Antibiotic'],
    ['brand_name' => 'Amphidox', 'generic_name' => 'Doxycycline', 'dosage_form' => 'Capsule', 'strength' => '100mg', 'drug_class' => 'Tetracycline Antibiotic'],
    ['brand_name' => 'Ampicin', 'generic_name' => 'Ampicillin', 'dosage_form' => 'Capsule', 'strength' => '500mg', 'drug_class' => 'Penicillin Antibiotic'],
];

$stmt = $db->prepare("INSERT INTO medicine_master_data (brand_name, generic_name, dosage_form, strength, drug_class, slug) VALUES (:brand, :generic, :form, :strength, :class, :slug)");

foreach ($medicines as $med) {
    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $med['brand_name']));
    $check = $db->prepare("SELECT id FROM medicine_master_data WHERE brand_name = :bn LIMIT 1");
    $check->execute([':bn' => $med['brand_name']]);
    if ($check->rowCount() > 0) {
        echo "Skipping existing: " . $med['brand_name'] . "\n";
        continue;
    }
    $stmt->execute([
        ':brand' => $med['brand_name'],
        ':generic' => $med['generic_name'],
        ':form' => $med['dosage_form'],
        ':strength' => $med['strength'],
        ':class' => $med['drug_class'],
        ':slug' => $slug
    ]);
    echo "Inserted: " . $med['brand_name'] . "\n";
}
echo "Done.\n";
