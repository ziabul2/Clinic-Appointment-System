<?php
require_once __DIR__ . '/../config/config.php';

$medicines = [
    ['brand_name' => 'A-Cal', 'generic_name' => 'Calcium Carbonate', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'drug_class' => 'Calcium Supplement'],
    ['brand_name' => 'A-Cal DX', 'generic_name' => 'Calcium Carbonate + Vitamin D3', 'dosage_form' => 'Tablet', 'strength' => '500mg + 200IU', 'drug_class' => 'Calcium & Vitamin D Supplement'],
    ['brand_name' => 'A-Cold', 'generic_name' => 'Paracetamol + Phenylephrine Hydrochloride', 'dosage_form' => 'Syrup', 'strength' => '120mg + 5mg / 5ml', 'drug_class' => 'Antipyretic & Decongestant'],
    ['brand_name' => 'Abacavir', 'generic_name' => 'Abacavir', 'dosage_form' => 'Tablet', 'strength' => '300mg', 'drug_class' => 'Antiviral'],
    ['brand_name' => 'Abetis', 'generic_name' => 'Atorvastatin', 'dosage_form' => 'Tablet', 'strength' => '10mg', 'drug_class' => 'Statin (Lipid-lowering)'],
    ['brand_name' => 'Abilify', 'generic_name' => 'Aripiprazole', 'dosage_form' => 'Tablet', 'strength' => '10mg', 'drug_class' => 'Antipsychotic'],
    ['brand_name' => 'Abiraterone', 'generic_name' => 'Abiraterone Acetate', 'dosage_form' => 'Tablet', 'strength' => '250mg', 'drug_class' => 'Anticancer'],
    ['brand_name' => 'Abocal', 'generic_name' => 'Calcium Carbonate + Vitamin D3', 'dosage_form' => 'Tablet', 'strength' => '500mg + 200IU', 'drug_class' => 'Calcium & Vitamin D Supplement'],
    ['brand_name' => 'Abrol', 'generic_name' => 'Paracetamol', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'drug_class' => 'Analgesic'],
    ['brand_name' => 'Ace', 'generic_name' => 'Paracetamol', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'drug_class' => 'Analgesic'],
    ['brand_name' => 'Ace Plus', 'generic_name' => 'Paracetamol + Caffeine', 'dosage_form' => 'Tablet', 'strength' => '500mg + 65mg', 'drug_class' => 'Analgesic'],
    ['brand_name' => 'Ace Power', 'generic_name' => 'Paracetamol + Caffeine', 'dosage_form' => 'Tablet', 'strength' => '500mg + 65mg', 'drug_class' => 'Analgesic'],
    ['brand_name' => 'Ace XR', 'generic_name' => 'Paracetamol', 'dosage_form' => 'Tablet (Extended Release)', 'strength' => '650mg', 'drug_class' => 'Analgesic'],
    ['brand_name' => 'Aceclofenac', 'generic_name' => 'Aceclofenac', 'dosage_form' => 'Tablet', 'strength' => '100mg', 'drug_class' => 'NSAID'],
    ['brand_name' => 'Acetram', 'generic_name' => 'Tramadol Hydrochloride + Paracetamol', 'dosage_form' => 'Tablet', 'strength' => '37.5mg + 325mg', 'drug_class' => 'Opioid Analgesic Combination'],
    ['brand_name' => 'Acifix', 'generic_name' => 'Rabeprazole Sodium', 'dosage_form' => 'Tablet', 'strength' => '20mg', 'drug_class' => 'Proton Pump Inhibitor'],
    ['brand_name' => 'Acitretin', 'generic_name' => 'Acitretin', 'dosage_form' => 'Capsule', 'strength' => '25mg', 'drug_class' => 'Antipsoriatic'],
    ['brand_name' => 'Aclit', 'generic_name' => 'Pioglitazone', 'dosage_form' => 'Tablet', 'strength' => '30mg', 'drug_class' => 'Antidiabetic'],
    ['brand_name' => 'Aclofen', 'generic_name' => 'Aceclofenac', 'dosage_form' => 'Tablet', 'strength' => '100mg', 'drug_class' => 'NSAID'],
    ['brand_name' => 'Acmetrol', 'generic_name' => 'Metoprolol Tartrate', 'dosage_form' => 'Tablet', 'strength' => '50mg', 'drug_class' => 'Beta-Blocker'],
    ['brand_name' => 'Acmu', 'generic_name' => 'Mupirocin', 'dosage_form' => 'Ointment', 'strength' => '2%', 'drug_class' => 'Topical Antibiotic'],
    ['brand_name' => 'Acne-Benz', 'generic_name' => 'Benzoyl Peroxide', 'dosage_form' => 'Gel', 'strength' => '5%', 'drug_class' => 'Antiacne'],
    ['brand_name' => 'Acne-Clind', 'generic_name' => 'Clindamycin', 'dosage_form' => 'Gel', 'strength' => '1%', 'drug_class' => 'Topical Antibiotic'],
    ['brand_name' => 'Acor', 'generic_name' => 'Atorvastatin', 'dosage_form' => 'Tablet', 'strength' => '20mg', 'drug_class' => 'Statin (Lipid-lowering)'],
    ['brand_name' => 'Acord', 'generic_name' => 'Metformin Hydrochloride', 'dosage_form' => 'Tablet', 'strength' => '850mg', 'drug_class' => 'Antidiabetic'],
    ['brand_name' => 'Acorid', 'generic_name' => 'Metformin Hydrochloride', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'drug_class' => 'Antidiabetic'],
    ['brand_name' => 'Acorn', 'generic_name' => 'Metformin Hydrochloride', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'drug_class' => 'Antidiabetic'],
];

$stmt = $db->prepare("INSERT INTO medicine_master_data (brand_name, generic_name, dosage_form, strength, drug_class, slug) VALUES (:brand, :generic, :form, :strength, :class, :slug)");

foreach ($medicines as $med) {
    // Generate slug
    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $med['brand_name']));
    
    // Check if exists
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
