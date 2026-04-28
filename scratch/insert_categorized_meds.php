<?php
require_once __DIR__ . '/../config/config.php';

$rawMeds = [
    [
        'category' => 'Pain & Fever Relief',
        'items' => [
            ['brand' => 'Napa', 'generic' => 'Paracetamol', 'strengths' => '500 mg, 665 mg, 120 mg/5ml, 80 mg/ml', 'forms' => 'Tablet, Suspension, Suppository, Paediatric Drops', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'General pain relief, fever reduction.'],
            ['brand' => 'Ace', 'generic' => 'Paracetamol', 'strengths' => '500 mg, 665 mg, 120 mg/5ml', 'forms' => 'Tablet, Suspension', 'company' => 'Aristopharma Ltd.', 'use' => 'Pain relief, fever reduction.'],
            ['brand' => 'Xel-Z', 'generic' => 'Paracetamol', 'strengths' => '500 mg, 665 mg (ER)', 'forms' => 'Tablet', 'company' => 'Beximco Pharmaceuticals Ltd.', 'use' => 'Pain relief, fever reduction.'],
            ['brand' => 'Flexi', 'generic' => 'Ibuprofen', 'strengths' => '200 mg, 400 mg', 'forms' => 'Tablet, Suspension', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'NSAID for pain, inflammation, and fever.'],
            ['brand' => 'Ketorolac', 'generic' => 'Ketorolac Tromethamine', 'strengths' => '10 mg', 'forms' => 'Tablet, Injection', 'company' => 'Beximco, Incepta, Square', 'use' => 'Short-term management of moderately severe acute pain.'],
        ]
    ],
    [
        'category' => 'Gastric & Digestive Health',
        'items' => [
            ['brand' => 'Esomep', 'generic' => 'Esomeprazole', 'strengths' => '20 mg, 40 mg', 'forms' => 'Tablet, Capsule, IV Injection', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Reduces stomach acid, treats GERD, ulcers, and heartburn.'],
            ['brand' => 'Nexum', 'generic' => 'Esomeprazole', 'strengths' => '20 mg, 40 mg', 'forms' => 'Tablet, Capsule, IV Injection', 'company' => 'Beximco Pharmaceuticals Ltd.', 'use' => 'Reduces stomach acid, treats GERD, ulcers, and heartburn.'],
            ['brand' => 'Losectil', 'generic' => 'Omeprazole', 'strengths' => '20 mg, 40 mg', 'forms' => 'Capsule, IV Injection', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Reduces stomach acid, treats indigestion, heartburn, and ulcers.'],
            ['brand' => 'Pantonix', 'generic' => 'Pantoprazole', 'strengths' => '20 mg, 40 mg', 'forms' => 'Tablet, IV Injection', 'company' => 'Incepta Pharmaceuticals Ltd.', 'use' => 'Reduces stomach acid, treats heartburn, GERD, and ulcers.'],
            ['brand' => 'Rabep', 'generic' => 'Rabeprazole', 'strengths' => '10 mg, 20 mg', 'forms' => 'Tablet', 'company' => 'ACI Limited', 'use' => 'Reduces stomach acid, treats GERD and acid reflux.'],
            ['brand' => 'Seclo', 'generic' => 'Omeprazole', 'strengths' => '20 mg, 40 mg', 'forms' => 'Capsule', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Reduces stomach acid, treats indigestion, heartburn, and ulcers.'],
            ['brand' => 'Omep', 'generic' => 'Omeprazole', 'strengths' => '20 mg, 40 mg', 'forms' => 'Capsule', 'company' => 'Opsonin Pharma Ltd.', 'use' => 'Reduces stomach acid, treats indigestion, heartburn, and ulcers.'],
            ['brand' => 'Fimotac', 'generic' => 'Famotidine', 'strengths' => '20 mg, 40 mg', 'forms' => 'Tablet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'H2 blocker, reduces stomach acid for ulcers and heartburn.'],
            ['brand' => 'Motigut', 'generic' => 'Domperidone', 'strengths' => '10 mg', 'forms' => 'Tablet, Suspension, Suppository', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Treats nausea, vomiting, and promotes stomach emptying.'],
            ['brand' => 'Domiren', 'generic' => 'Domperidone', 'strengths' => '10 mg', 'forms' => 'Tablet, Suspension', 'company' => 'Incepta Pharmaceuticals Ltd.', 'use' => 'Treats nausea, vomiting, and indigestion.'],
        ]
    ],
    [
        'category' => 'Anti-Infectives',
        'items' => [
            ['brand' => 'Suprax', 'generic' => 'Cefixime', 'strengths' => '200 mg, 400 mg', 'forms' => 'Tablet, Suspension', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Treats various bacterial infections (respiratory, urinary).'],
            ['brand' => 'Zimax', 'generic' => 'Azithromycin', 'strengths' => '250 mg, 500 mg', 'forms' => 'Tablet, Suspension', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Broad-spectrum antibiotic for bacterial infections.'],
            ['brand' => 'Axet', 'generic' => 'Cefuroxime Axetil', 'strengths' => '250 mg, 500 mg', 'forms' => 'Tablet, Suspension', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Treats respiratory tract, ear, and skin infections.'],
            ['brand' => 'Tridosil', 'generic' => 'Azithromycin', 'strengths' => '250 mg, 500 mg', 'forms' => 'Tablet, Suspension', 'company' => 'Incepta Pharmaceuticals Ltd.', 'use' => 'Broad-spectrum antibiotic for various bacterial infections.'],
            ['brand' => 'Tazocin', 'generic' => 'Piperacillin + Tazobactam', 'strengths' => '2.25 gm, 4.5 gm', 'forms' => 'IV Injection', 'company' => 'Beximco Pharmaceuticals Ltd.', 'use' => 'Potent antibiotic for severe hospital infections.'],
            ['brand' => 'Ciprocin', 'generic' => 'Ciprofloxacin', 'strengths' => '250 mg, 500 mg', 'forms' => 'Tablet, IV Infusion, Eye/Ear Drops', 'company' => 'Beximco Pharmaceuticals Ltd.', 'use' => 'Quinolone antibiotic for various infections.'],
            ['brand' => 'Moxaclav', 'generic' => 'Amoxicillin + Clavulanic Acid', 'strengths' => '375 mg, 625 mg', 'forms' => 'Tablet, Suspension', 'company' => 'Incepta Pharmaceuticals Ltd.', 'use' => 'Potent antibiotic for wide range of bacterial infections.'],
            ['brand' => 'Ominax', 'generic' => 'Cefixime', 'strengths' => '200 mg, 400 mg', 'forms' => 'Tablet, Suspension', 'company' => 'Renata PLC', 'use' => 'Treats various bacterial infections.'],
            ['brand' => 'Flucon', 'generic' => 'Fluconazole', 'strengths' => '150 mg', 'forms' => 'Capsule', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Antifungal for various fungal infections.'],
        ]
    ],
    [
        'category' => 'Antihistamines',
        'items' => [
            ['brand' => 'Fexo', 'generic' => 'Fexofenadine', 'strengths' => '60 mg, 120 mg, 180 mg', 'forms' => 'Tablet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Non-drowsy antihistamine for hay fever and hives.'],
            ['brand' => 'Alatrol', 'generic' => 'Loratadine', 'strengths' => '10 mg', 'forms' => 'Tablet, Syrup', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Non-drowsy antihistamine for allergy symptoms.'],
            ['brand' => 'Reliver', 'generic' => 'Cetirizine', 'strengths' => '10 mg', 'forms' => 'Tablet, Syrup', 'company' => 'Incepta Pharmaceuticals Ltd.', 'use' => 'Antihistamine for allergies.'],
            ['brand' => 'Deslor', 'generic' => 'Desloratadine', 'strengths' => '5 mg', 'forms' => 'Tablet, Syrup', 'company' => 'Beximco Pharmaceuticals Ltd.', 'use' => 'Non-drowsy antihistamine for seasonal allergies.'],
        ]
    ],
    [
        'category' => 'Respiratory Health',
        'items' => [
            ['brand' => 'Montair', 'generic' => 'Montelukast', 'strengths' => '4 mg, 5 mg, 10 mg', 'forms' => 'Tablet, Chewable Tablet, Sachet', 'company' => 'Incepta Pharmaceuticals Ltd.', 'use' => 'Prevents asthma attacks and treats allergic rhinitis.'],
            ['brand' => 'Moxus', 'generic' => 'Montelukast', 'strengths' => '4 mg, 5 mg, 10 mg', 'forms' => 'Tablet, Chewable Tablet, Sachet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Prevents asthma attacks and treats allergic rhinitis.'],
            ['brand' => 'Monas', 'generic' => 'Montelukast', 'strengths' => '4 mg, 5 mg, 10 mg', 'forms' => 'Tablet, Chewable Tablet, Sachet', 'company' => 'Renata PLC', 'use' => 'Prevents asthma attacks and treats allergic rhinitis.'],
            ['brand' => 'Bromex', 'generic' => 'Bromhexine', 'strengths' => '8 mg', 'forms' => 'Tablet, Syrup', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Mucolytic, helps to thin and clear mucus from airways.'],
            ['brand' => 'Tusca', 'generic' => 'Dextromethorphan + Pseudoephedrine + Triprolidine', 'strengths' => 'Syrup formulation', 'forms' => 'Syrup', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Cough and cold relief.'],
        ]
    ],
    [
        'category' => 'Cardiovascular Health',
        'items' => [
            ['brand' => 'Amlopres', 'generic' => 'Amlodipine', 'strengths' => '5 mg, 10 mg', 'forms' => 'Tablet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Treats high blood pressure and angina.'],
            ['brand' => 'Olmes', 'generic' => 'Olmesartan Medoxomil', 'strengths' => '20 mg, 40 mg', 'forms' => 'Tablet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Treats high blood pressure.'],
            ['brand' => 'Duopres', 'generic' => 'Olmesartan + Amlodipine', 'strengths' => '20/5 mg, 40/10 mg', 'forms' => 'Tablet', 'company' => 'Incepta Pharmaceuticals Ltd.', 'use' => 'Combination therapy for high blood pressure.'],
            ['brand' => 'Losar', 'generic' => 'Losartan Potassium', 'strengths' => '25 mg, 50 mg', 'forms' => 'Tablet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Treats high blood pressure and protects kidneys.'],
            ['brand' => 'Tiginor', 'generic' => 'Atorvastatin', 'strengths' => '10 mg, 20 mg', 'forms' => 'Tablet', 'company' => 'Incepta Pharmaceuticals Ltd.', 'use' => 'Lowers cholesterol and reduces cardiovascular risk.'],
            ['brand' => 'Avas', 'generic' => 'Atorvastatin', 'strengths' => '10 mg, 20 mg', 'forms' => 'Tablet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Lowers cholesterol and reduces cardiovascular risk.'],
            ['brand' => 'Rocovas', 'generic' => 'Rosuvastatin', 'strengths' => '5 mg, 10 mg, 20 mg', 'forms' => 'Tablet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Lowers cholesterol and reduces cardiovascular risk.'],
            ['brand' => 'Rosu', 'generic' => 'Rosuvastatin', 'strengths' => '5 mg, 10 mg, 20 mg', 'forms' => 'Tablet', 'company' => 'Ziska Pharmaceuticals Ltd.', 'use' => 'Lowers cholesterol and reduces cardiovascular risk.'],
            ['brand' => 'Cardiloc', 'generic' => 'Metoprolol Succinate', 'strengths' => '25 mg, 50 mg', 'forms' => 'Tablet (ER)', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Beta-blocker for hypertension and heart failure.'],
        ]
    ],
    [
        'category' => 'Diabetes Management',
        'items' => [
            ['brand' => 'Glory', 'generic' => 'Gliclazide', 'strengths' => '80 mg, 30 mg (MR)', 'forms' => 'Tablet (MR)', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Oral medication for Type 2 Diabetes.'],
            ['brand' => 'Diabinil', 'generic' => 'Metformin Hydrochloride', 'strengths' => '500 mg, 850 mg (ER)', 'forms' => 'Tablet (ER)', 'company' => 'Incepta Pharmaceuticals Ltd.', 'use' => 'Oral medication for Type 2 Diabetes.'],
            ['brand' => 'Glucomin', 'generic' => 'Metformin Hydrochloride', 'strengths' => '500 mg, 850 mg', 'forms' => 'Tablet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Oral medication for Type 2 Diabetes.'],
            ['brand' => 'Insulin', 'generic' => 'Insulin Human', 'strengths' => '100 IU/ml', 'forms' => 'Vial, Penfill', 'company' => 'Novo Nordisk, Square, Incepta', 'use' => 'Controls blood sugar in Type 1 and Type 2 Diabetes.'],
        ]
    ],
    [
        'category' => 'Vitamins & Supplements',
        'items' => [
            ['brand' => 'Ostecal D', 'generic' => 'Calcium Carbonate + Colecalciferol', 'strengths' => '500 mg + 200 IU', 'forms' => 'Tablet', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Bone health, calcium and vitamin D supplementation.'],
            ['brand' => 'Calcibex', 'generic' => 'Calcium Carbonate + Colecalciferol', 'strengths' => '500 mg + 200 IU', 'forms' => 'Tablet', 'company' => 'Beximco Pharmaceuticals Ltd.', 'use' => 'Bone health, calcium and vitamin D supplementation.'],
            ['brand' => 'Neuro-B', 'generic' => 'Vitamin B1 + B6 + B12', 'strengths' => 'Varies', 'forms' => 'Tablet, Injection', 'company' => 'Square Pharmaceuticals PLC', 'use' => 'Treats B vitamin deficiencies, supports nerve health.'],
            ['brand' => 'Ceevit', 'generic' => 'Ascorbic Acid (Vitamin C)', 'strengths' => '250 mg, 500 mg', 'forms' => 'Chewable Tablet', 'company' => 'Renata PLC', 'use' => 'Vitamin C supplementation, antioxidant.'],
        ]
    ]
];

$stmt = $db->prepare("INSERT INTO medicine_master_data (brand_name, generic_name, strength, dosage_form, manufacturer, indication, type, slug, meta_data) 
                      VALUES (:brand, :generic, :strength, :form, :manufacturer, :indication, 'Essential', :slug, :meta)");

$count = 0;
foreach ($rawMeds as $cat) {
    foreach ($cat['items'] as $item) {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['brand']));
        
        // Check if exists (by brand + generic + strength to allow multiple forms)
        $check = $db->prepare("SELECT id FROM medicine_master_data WHERE brand_name = :b AND generic_name = :g AND strength = :s LIMIT 1");
        $check->execute([':b' => $item['brand'], ':g' => $item['generic'], ':s' => $item['strengths']]);
        if ($check->rowCount() > 0) {
            echo "Skipping existing: {$item['brand']} ({$item['strengths']})\n";
            continue;
        }
        
        $stmt->execute([
            ':brand' => $item['brand'],
            ':generic' => $item['generic'],
            ':strength' => $item['strengths'],
            ':form' => $item['forms'],
            ':manufacturer' => $item['company'],
            ':indication' => $item['use'],
            ':slug' => $slug,
            ':meta' => json_encode(['category' => $cat['category']])
        ]);
        $count++;
        echo "Inserted: {$item['brand']} [{$cat['category']}]\n";
    }
}

echo "\nTotal inserted: $count\n";
echo "Done.\n";
