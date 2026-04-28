<?php
require_once __DIR__ . '/../config/config.php';

$medicines = [
    ['brand_name' => 'Adalat', 'generic_name' => 'Nifedipine', 'dosage_form' => 'Tablet', 'strength' => '10mg', 'drug_class' => 'Calcium Channel Blocker'],
    ['brand_name' => 'Adal XR', 'generic_name' => 'Nifedipine', 'dosage_form' => 'Tablet (Extended Release)', 'strength' => '30mg', 'drug_class' => 'Calcium Channel Blocker'],
    ['brand_name' => 'Adcef', 'generic_name' => 'Cefdinir', 'dosage_form' => 'Capsule', 'strength' => '300mg', 'drug_class' => 'Cephalosporin Antibiotic'],
    ['brand_name' => 'Adryl', 'generic_name' => 'Diphenhydramine Hydrochloride', 'dosage_form' => 'Syrup', 'strength' => '12.5mg / 5ml', 'drug_class' => 'Antihistamine'],
    ['brand_name' => 'Advil', 'generic_name' => 'Ibuprofen', 'dosage_form' => 'Tablet', 'strength' => '200mg', 'drug_class' => 'NSAID'],
    ['brand_name' => 'Aerobin', 'generic_name' => 'Theophylline', 'dosage_form' => 'Tablet', 'strength' => '400mg', 'drug_class' => 'Bronchodilator'],
    ['brand_name' => 'Aerocort', 'generic_name' => 'Beclomethasone + Salbutamol', 'dosage_form' => 'Inhaler', 'strength' => '50mcg + 100mcg', 'drug_class' => 'Antiasthmatic Combination'],
    ['brand_name' => 'Aerovent', 'generic_name' => 'Ipratropium Bromide', 'dosage_form' => 'Inhaler', 'strength' => '20mcg', 'drug_class' => 'Anticholinergic Bronchodilator'],
    ['brand_name' => 'Afdal', 'generic_name' => 'Alfuzosin', 'dosage_form' => 'Tablet', 'strength' => '10mg', 'drug_class' => 'Alpha-blocker'],
    ['brand_name' => 'Afix', 'generic_name' => 'Cefixime', 'dosage_form' => 'Capsule', 'strength' => '200mg/400mg', 'drug_class' => 'Cephalosporin Antibiotic'],
    ['brand_name' => 'Aflo', 'generic_name' => 'Alfuzosin', 'dosage_form' => 'Tablet', 'strength' => '10mg', 'drug_class' => 'Alpha-blocker'],
    ['brand_name' => 'Aflox', 'generic_name' => 'Lomefloxacin', 'dosage_form' => 'Tablet', 'strength' => '400mg', 'drug_class' => 'Quinolone Antibiotic'],
    ['brand_name' => 'Afton', 'generic_name' => 'Alogliptin', 'dosage_form' => 'Tablet', 'strength' => '25mg', 'drug_class' => 'DPP-4 Inhibitor'],
    ['brand_name' => 'Aggron', 'generic_name' => 'Clopidogrel + Aspirin', 'dosage_form' => 'Tablet', 'strength' => '75mg + 75mg', 'drug_class' => 'Antiplatelet'],
    ['brand_name' => 'Agicin', 'generic_name' => 'Azithromycin', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'drug_class' => 'Macrolide Antibiotic'],
    ['brand_name' => 'Agit', 'generic_name' => 'Azithromycin', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'drug_class' => 'Macrolide Antibiotic'],
    ['brand_name' => 'Agiz', 'generic_name' => 'Azithromycin', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'drug_class' => 'Macrolide Antibiotic'],
    ['brand_name' => 'Aglow', 'generic_name' => 'Glimepiride', 'dosage_form' => 'Tablet', 'strength' => '1mg/2mg', 'drug_class' => 'Sulfonylurea Antidiabetic'],
    ['brand_name' => 'Airway', 'generic_name' => 'Montelukast', 'dosage_form' => 'Tablet', 'strength' => '10mg', 'drug_class' => 'Leukotriene Receptor Antagonist'],
    ['brand_name' => 'Ajanta', 'generic_name' => 'Sildenafil', 'dosage_form' => 'Tablet', 'strength' => '50mg/100mg', 'drug_class' => 'PDE5 Inhibitor'],
    ['brand_name' => 'Al-D', 'generic_name' => 'Alfacalcidol', 'dosage_form' => 'Capsule', 'strength' => '0.25mcg', 'drug_class' => 'Vitamin D Analogue'],
    ['brand_name' => 'Alacrol', 'generic_name' => 'Sodium Cromoglicate', 'dosage_form' => 'Eye Drops', 'strength' => '2%', 'drug_class' => 'Mast Cell Stabilizer'],
    ['brand_name' => 'Alacrop', 'generic_name' => 'Sodium Cromoglicate', 'dosage_form' => 'Eye Drops', 'strength' => '2%', 'drug_class' => 'Mast Cell Stabilizer'],
    ['brand_name' => 'Alamax', 'generic_name' => 'Almotriptan', 'dosage_form' => 'Tablet', 'strength' => '12.5mg', 'drug_class' => 'Antimigraine'],
    ['brand_name' => 'Alaryl', 'generic_name' => 'Alcaftadine', 'dosage_form' => 'Eye Drops', 'strength' => '0.25%', 'drug_class' => 'Antihistamine Eye Drop'],
    ['brand_name' => 'Alasta', 'generic_name' => 'Alcaftadine', 'dosage_form' => 'Eye Drops', 'strength' => '0.25%', 'drug_class' => 'Antihistamine Eye Drop'],
    ['brand_name' => 'Alatrol', 'generic_name' => 'Cetirizine Hydrochloride', 'dosage_form' => 'Tablet', 'strength' => '10mg', 'drug_class' => 'Antihistamine'],
    ['brand_name' => 'Alaxa', 'generic_name' => 'Bisacodyl', 'dosage_form' => 'Tablet', 'strength' => '5mg', 'drug_class' => 'Laxative'],
    ['brand_name' => 'Alazol', 'generic_name' => 'Albendazole', 'dosage_form' => 'Tablet', 'strength' => '400mg', 'drug_class' => 'Anthelmintic'],
    ['brand_name' => 'Albazole', 'generic_name' => 'Albendazole', 'dosage_form' => 'Tablet', 'strength' => '400mg', 'drug_class' => 'Anthelmintic'],
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
