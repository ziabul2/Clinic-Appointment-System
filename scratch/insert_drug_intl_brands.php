<?php
require_once __DIR__ . '/../config/config.php';

$meds = [
    ['brand' => 'Acepril', 'generic' => 'Lisinopril', 'form' => 'Tablet', 'use' => 'Antihypertensive (ACE inhibitor)'],
    ['brand' => 'Acetor', 'generic' => 'Captopril', 'form' => 'Tablet', 'use' => 'Antihypertensive (ACE inhibitor)'],
    ['brand' => 'Amlocard', 'generic' => 'Amlodipine', 'form' => 'Tablet', 'use' => 'Calcium Channel Blocker for hypertension'],
    ['brand' => 'Amlocard Plus', 'generic' => 'Amlodipine + Atenolol', 'form' => 'Tablet', 'use' => 'Combination therapy for hypertension'],
    ['brand' => 'Clopid', 'generic' => 'Clopidogrel', 'form' => 'Tablet', 'use' => 'Antiplatelet agent to prevent blood clots'],
    ['brand' => 'Nidocard Retard', 'generic' => 'Nitroglycerin', 'form' => 'Tablet (Extended Release)', 'use' => 'Treatment and prevention of angina pectoris'],
    ['brand' => 'E-Cap', 'generic' => 'Vitamin E', 'form' => 'Capsule', 'use' => 'Antioxidant, Vitamin E supplement'],
    ['brand' => 'D-Cap', 'generic' => 'Vitamin D3', 'form' => 'Capsule', 'use' => 'Vitamin D supplement for bone health'],
    ['brand' => 'OMG-3', 'generic' => 'Omega-3 Acid Ethyl Esters', 'form' => 'Capsule', 'use' => 'Lowers triglycerides and supports heart health'],
    ['brand' => 'Supravit-M', 'generic' => 'Multivitamin & Mineral', 'form' => 'Tablet', 'use' => 'Daily nutritional supplement'],
    ['brand' => 'Ratinol Forte', 'generic' => 'Vitamin A', 'form' => 'Capsule', 'use' => 'Vitamin A supplement'],
    ['brand' => 'Aclene', 'generic' => 'Adapalene', 'form' => 'Gel', 'use' => 'Topical treatment for acne'],
    ['brand' => 'Aclene Plus', 'generic' => 'Adapalene + Benzoyl peroxide', 'form' => 'Gel', 'use' => 'Combination topical treatment for acne'],
    ['brand' => 'Aqua Care', 'generic' => 'Urea', 'form' => 'Cream', 'use' => 'Treats dry/rough skin conditions'],
    ['brand' => 'Mycofree', 'generic' => 'Terbinafine', 'form' => 'Cream/Tablet', 'use' => 'Antifungal treatment'],
    ['brand' => 'Abiret', 'generic' => 'Abiraterone Acetate', 'form' => 'Tablet', 'use' => 'Treatment for prostate cancer'],
    ['brand' => 'Anacare', 'generic' => 'Anastrozole', 'form' => 'Tablet', 'use' => 'Treatment for breast cancer'],
    ['brand' => 'Anib', 'generic' => 'Afatinib', 'form' => 'Tablet', 'use' => 'Tyrosine kinase inhibitor for lung cancer'],
    ['brand' => 'Apit', 'generic' => 'Megestrol Acetate', 'form' => 'Tablet', 'use' => 'Treats appetite loss and weight loss in cancer/HIV'],
    ['brand' => 'Aprila', 'generic' => 'Apremilast', 'form' => 'Tablet', 'use' => 'Treats psoriatic arthritis and plaque psoriasis'],
    ['brand' => 'Apridex', 'generic' => 'Aprepitant', 'form' => 'Capsule', 'use' => 'Prevents nausea and vomiting from chemotherapy'],
    ['brand' => 'Cabanib', 'generic' => 'Cabozantinib', 'form' => 'Tablet', 'use' => 'Targeted therapy for kidney/liver cancer'],
    ['brand' => 'Dasinib', 'generic' => 'Dasatinib', 'form' => 'Tablet', 'use' => 'Targeted therapy for chronic myeloid leukemia'],
    ['brand' => 'Getinib', 'generic' => 'Gefitinib', 'form' => 'Tablet', 'use' => 'Targeted therapy for lung cancer'],
    ['brand' => 'Metanib', 'generic' => 'Imatinib', 'form' => 'Tablet', 'use' => 'Targeted therapy for leukemia and GIST'],
    ['brand' => 'Osinib', 'generic' => 'Osimertinib', 'form' => 'Tablet', 'use' => 'Targeted therapy for EGFR-mutated lung cancer'],
    ['brand' => 'Palbo', 'generic' => 'Palbociclib', 'form' => 'Capsule', 'use' => 'Targeted therapy for breast cancer'],
    ['brand' => 'Percanib', 'generic' => 'Neratinib', 'form' => 'Tablet', 'use' => 'HER2-targeted therapy for breast cancer'],
    ['brand' => 'AH', 'generic' => 'Albendazole', 'form' => 'Tablet', 'use' => 'Treatment for intestinal parasites (worms)'],
    ['brand' => 'Akno', 'generic' => 'Isotretinoin', 'form' => 'Capsule', 'use' => 'Treats severe cystic acne'],
    ['brand' => 'Alkamax', 'generic' => 'Potassium Citrate + Citric Acid', 'form' => 'Syrup', 'use' => 'Alkalinizing agent for kidney stones/UTI'],
    ['brand' => 'Alocap', 'generic' => 'Aloe Vera', 'form' => 'Capsule', 'use' => 'Herbal supplement for digestive health'],
    ['brand' => 'Amlabery', 'generic' => 'Sharbat Amla', 'form' => 'Syrup', 'use' => 'Herbal vitamin C and digestive tonic'],
    ['brand' => 'Biloba', 'generic' => 'Ginkgo Biloba', 'form' => 'Capsule', 'use' => 'Herbal supplement for memory and circulation'],
    ['brand' => 'Dicaltrol', 'generic' => 'Calcitriol', 'form' => 'Capsule', 'use' => 'Active form of Vitamin D for bone health'],
    ['brand' => 'Fenat', 'generic' => 'Ketotifen', 'form' => 'Tablet/Syrup', 'use' => 'Prevents asthma and allergy symptoms'],
    ['brand' => 'M-Kast', 'generic' => 'Montelukast', 'form' => 'Tablet', 'use' => 'Leukotriene receptor antagonist for asthma/allergies'],
];

$stmt = $db->prepare("INSERT INTO medicine_master_data (brand_name, generic_name, dosage_form, manufacturer, indication, type, slug, meta_data) 
                      VALUES (:brand, :generic, :form, 'Drug International Ltd', :indication, 'Allopathic', :slug, :meta)");

$count = 0;
foreach ($meds as $m) {
    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $m['brand']));
    
    // Check if exists
    $check = $db->prepare("SELECT id FROM medicine_master_data WHERE brand_name = :b AND manufacturer = 'Drug International Ltd' LIMIT 1");
    $check->execute([':b' => $m['brand']]);
    if ($check->rowCount() > 0) {
        echo "Skipping existing: {$m['brand']}\n";
        continue;
    }
    
    $stmt->execute([
        ':brand' => $m['brand'],
        ':generic' => $m['generic'],
        ':form' => $m['form'],
        ':indication' => $m['use'],
        ':slug' => $slug,
        ':meta' => json_encode(['source' => 'Drug International Product List'])
    ]);
    $count++;
    echo "Inserted: {$m['brand']}\n";
}

echo "\nTotal inserted: $count\n";
echo "Done.\n";
