<?php
require_once __DIR__ . '/../config/config.php';

$rawText = <<<'EOD'
1 Abacavir (ABC) Oral Liquid, Tablet
2 Acetazolamide Tablet
3 Acetylsalicylic acid Suppository, Tablet
4 Aciclovir Powder for injection, Tablet
5 Albendazole Tablet (chewable)
6 Allopurinol Tablet
7 Aluminium hydroxide + Magnesium hydroxide Oral liquid, Tablet
8 Amitriptyline Tablet
9 Amlodipine Besylate Tablet
10 Amoxicillin Capsule or Tablet, Powder for oral liquid, Powder for injection
11 Ampicillin Powder for Injection
12 Anti-D immunoglobulin (human) Injection
13 Antitetanus immunoglobulin (human) Injection
14 Artemether + Lumefantrine* Tablet
15 Artesunate Injection, Tablet
16 Ascorbic Acid Tablet
17 Atenolol Tablet
18 Atropine Injection, Solution (eye drops)
19 Barium Sulfate Aqueous suspension
20 BCG vaccine Injection
21 Benzathine benzylpenicillin Powder for injection
22 Benzoic acid + Salicylic acid Ointment or cream
23 Benzyl benzoate Lotion
24 Benzyl penicillin Powder for injection
25 Betamethasone Ointment or cream
26 Bleomycin Powder for injection
27 Bupivacaine Injection
28 Calcium gluconate Injection
29 Carbamazepine Oral liquid, Tablet (chewable), Tablet (scored)
30 Charcoal, activated Powder
31 Chlorambucil Tablet
32 Chloramphenicol Eye drops, Eye ointment
33 Chlorhexidine Solution
34 Chloroquine Oral liquid, Tablet
35 Chlorpheniramine Injection , Tablet
36 Chlorpromazine Injection, Oral liquid, Tablet
37 Ciprofloxacin Tablet or powder for suspension
38 Cisplatin Injection
39 Clofazimine Capsule
40 Clotrimazole Vaginal cream, Vaginal tablet
41 Cloxacillin Capsule, Powder for injection, Power for oral liquid,
42 Condoms
43 Cyclophosphamide Powder for injection, Tablet
44 Dapsone Tablet
45 Dexamethasone Injection
46 Dextran 70 Injectable solution
47 Diazepam Injection, Tablet, Tablet (scored)
48 Didanosine (ddl) Buffered powder for oral liquid, Capsule (unbuffered enteric coated), Tablet (buffered chewable, dispersible)
49 Diethylcarbamazine Tablet
50 Digoxin Injection, Oral liquid, Tablet
51 Diloxanide Tablet
52 Diphtheria antitoxin Injection
53 Diptheria vaccine Injection
54 Dopamine Injection
55 Doxorubicin Powder for injection
56 Doxycycline Capsule or Tablet, Tablet (dispersible)
57 DPT vaccine Oral + Injection
58 Efavirenz (EFV or EFZ) Capsule, Oral liquid, Tablet
59 Enalapril Tablet
60 Epinephrine (adrenaline) Injection, Solution (eye drops)
61 Ergocalciferol Capsule or Tablet, Oral liquid
62 Ergometrine Injection
63 Erythromycin Capsule or Tablet, Powder for injection, Powder for oral liquid
64 Ethambutol Tablet
65 Ethinylestradiol + Levonorgestrel Tablet
66 Ferrous salt Oral liquid, Tablet
67 Ferrous salt + Folic acid Capsule, Tablet
68 Fluconazole Capsule, Oral liquid
69 Fluorescein Eye drops
70 Fluorouracil Injection, Ointment
71 Fluphenazine Injection
72 Folic acid Tablet
73 Furosemide Injection, Tablet
74 Gentamycin Injection, Solution (eye drops)
75 Gentamycin + Hydrocortisone Ear drop
76 Glibenclamide Tablet
77 Gliclazide Tablet
78 Glucose Injectable solution
79 Glucose with sodium chloride Injectable solution
80 Glyceryl trinitrate Tablet (sublingual)
81 Griseofulvin Capsule or Tablet
82 Haloperidol Injection, Tablet
83 Halothane Inhalation
84 Heparin sodium Injection
85 Hepatitis B vaccine Injection
86 Homatropine Solution (eye drops)
87 Human normal immunoglobulin Intramuscular administration, Intravenous administration
88 Hydrochlorothiazide Tablet (scored)
89 Hydrocortisone Powder for injection, Ointment or cream, Suppository
90 Hyoscine butylbromide Tablet, Injection
91 Ibuprofen Tablet
92 Indinavir (IDV) Capsule
93 Insulin Injection (Soluble) Injection
94 Isoniazide Tablet, Tablet (scored)
95 Isoniazide + Ethambutol Tablet
96 Isosorbide dinitrate Tablet (sublingual)
97 Ketamine Injection
98 Lamivudine (3TC) Oral liquid, Tablet,
99 Levamisole Tablet
100 Levodopa + Carbidopa Tablet
101 Levothyroxine Tablet
102 Lidocaine Injection, Topical
103 Lithium Carbonate Capsule or tablet
104 Lopinavir + Ritonavir (LPV/r) Capsule, Oral liquid
105 Magnesium hydroxide Oral liquid
106 Magnesium sulfate* Injection
107 Mannitol Injectable solution
108 Measles vaccine Injection
109 Mebendazole Tablet (chewable)
110 Mefloquine Tablet
111 Metformin Tablet
112 Methotrexate Powder for injection, Tablet
113 Methyldopa Tablet : 250 mg
114 Methylrosanilinium chloride (gentian violet) Aqueous solution, Tincture
115 Metoclopramide Injection, Tablet
116 Metronidazole Injection, Oral liquid, Suppository, Tablet
117 Miconazole Ointment/Ceam
118 Miltefosine Capsule/Oral liquid
119 Misoprostol Tablet
120 Morphine Injection, Oral liquid, Tablet, Tablet (prolonged release)
121 Naloxone Injection
122 Nelfinavir (NFV) Oral powder, Tablet
123 Neomycin Sulfate + Bacitracin Ointment
124 Neostigmine Injection, Tablet
125 Nevirapine (NVP) Oral liquid, Tablet
126 Nicotinamide Tablet
127 Nifedipine Immediate release capsule
128 Nitrofurantoin Tablet
129 Nitrous oxide Inhalation
130 Nystatin Oral Suspension
131 Omeprazole Capsule
132 Oral rehydration salts Powder
133 Oseltamivir Tablet
134 Oxygen Inhalation
135 Oxytocin Injection
136 Paracetamol Oral liquid, Suppository, Tablet
137 Paromomycin Solution for intramuscular injection
138 Peritoneal Dialysis Solution Intraperitoneal dialysis solution (of appropriate composition)
139 Permethrin Cream, Lotion
140 Pertussis vaccine Injection
141 Pethidine hydrochloride Injection
142 Phenobarbital Injection, Oral liquid, Tablet
143 Phenoxymethylpenicillin Powder for oral liquid, Tablet,
144 Phenytoin Capsule, Injection, Oral liquid, Tablet, Tablet (chewable)
145 Pilocarpine Solution (eye drops)
146 Poliomyelitis vaccine Oral
147 Polyvalent anti snake venom Injection
148 Potassium chloride Tablet, Solution
149 Povidone Iodine Solution
150 Prednisolone Tablet, Solution (eye drops)
151 Primaquine Tablet
152 Procainamide Injection
153 Procaine benzylpenicllin Powder for injection
154 Procarbazine Capsule
155 Proguanil Tablet
156 Promethazine Oral liquid, Injection, Oral liquid, Tablet
157 Propranolol Tablet
158 Protamine sulfate Injection
159 Pyrazinamide Tablet, Tablet (dispersible), Tablet (scored)
160 Pyridoxine Tablet
161 Pyrimethamine Tablet
162 Quinine Injection, Tablet
163 Rabies immunoglobulin Injection
164 Rabies vaccine Injection
165 Retinol Capsule, Tablet, Oral oily solution, Water-miscible injection
166 Riboflavin Tablet
167 Rifampicin Capsule or Tablet
168 Rifampicin + Isoniazid Tablet
169 Rifampicin + Isoniazid + Ethambutol Tablet
170 Rifampicin + Isoniazid + Pyrazinamide Tablet
171 Rifampicin + Isoniazid + Pyrazinamide + Ethambutol Tablet
172 Ritonavir Oral liquid, Oral solid dosage form
173 Salbutamol Injection, Oral liquid, Respirator solution for use in nebulizers, Tablet
174 Salicylic acid Solution
175 Saquinavir (SQV) Capsule
176 Senna Tablet
177 Silver sulfadiazine Cream
178 Sodium chloride Injectable solution
179 Sodium Chloride 3% I/V fluid
180 Sodium Chloride quartet strength (0.225%) + Dextrose 5% I/V fluid
181 Sodium Hydrogen Carbonate Injectable solution, Soution
182 Sodium stibogluconate Injection
183 Sodium thiosulfate Solution
184 Spironolactone Tablet
185 Stavudine (d4t) Capsule, Powder for oral liquid
186 Streptomycin Powder for injection
187 Sulfadoxine + Pyrimethamine Tablet
188 Sulfamethoxazole + Trimethoprim Oral liquid, Tablet, Injection
189 Suxamethonium Injection, Powder for injection
190 Tamoxifen Tablet
191 Tenofovir disoproxil fumarate (TDF) Tablet
192 Tetanus vaccine Injection
193 Tetracycline Eye ointment
194 Thiamine Tablet
195 Thiopental Powder for injection
196 Trimethoprim Tablet
197 Tropicamide Eye drops
198 Tuberculin, purified protein derivative (PPD) Injection
199 Valproic acid Oral liquid, Tablet (crushable), Tablet (enteric coated)
200 Vecuronium Injection
201 Verapamil Injection, Tablet
202 Vinblastine Powder for injection
203 Vincristine Powder for injection
204 Vitamin B-Complex (Vitamin B1- 5 mg + Vitamin B2- 2 mg + Vitamin B6 - 2 mg + Nicotinamide 20 mg) Tablet
205 Warfarin Tablet
206 Water for Injection Ampoule
207 Xylometazoline Hydrochloride Nasal drops
208 Zidovudine (ZDV or AZT) Capsule, Oral liquid, Solution for IV infusion injection, Tablet
209 Zinc sulphate Oral liquid, Tablet
EOD;

$lines = explode("\n", trim($rawText));
$stmt = $db->prepare("INSERT INTO medicine_master_data (brand_name, generic_name, dosage_form, type, slug, meta_data) VALUES (:brand, :generic, :form, 'Essential', :slug, :meta)");

$count = 0;
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    // Find the first space after the digits
    $firstSpace = strpos($line, ' ');
    if ($firstSpace === false) continue;
    
    $sl = substr($line, 0, $firstSpace);
    $rest = trim(substr($line, $firstSpace));
    
    // Split the rest into Name and Dosage Form
    // Usually the name is up to the last word that is not a dosage form? 
    // Actually, dosage form starts with words like Tablet, Capsule, Injection, etc.
    
    $dosageKeywords = ['Tablet', 'Capsule', 'Injection', 'Powder', 'Suppository', 'Oral', 'Solution', 'Aqueous', 'Ointment', 'Cream', 'Lotion', 'Eye', 'Inhalation', 'Vaginal', 'Lotion', 'Injectable', 'Buffered', 'Spray', 'Nasal', 'I/V', 'Ear', 'Vax', 'Intramuscular', 'Intravenous', 'Ampoule'];
    
    $namePart = '';
    $formPart = '';
    
    $words = explode(' ', $rest);
    $foundForm = false;
    foreach ($words as $index => $word) {
        $cleanWord = trim($word, ',()');
        if (!$foundForm && in_array($cleanWord, $dosageKeywords)) {
            $foundForm = true;
        }
        
        if ($foundForm) {
            $formPart .= $word . ' ';
        } else {
            $namePart .= $word . ' ';
        }
    }
    
    $name = trim($namePart);
    $form = trim($formPart);
    
    if (empty($name)) {
        // Fallback for items like "Condoms"
        $name = $rest;
        $form = 'Other';
    }

    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
    
    // Check if exists
    $check = $db->prepare("SELECT id FROM medicine_master_data WHERE generic_name = :n LIMIT 1");
    $check->execute([':n' => $name]);
    if ($check->rowCount() > 0) {
        echo "Skipping existing: $name\n";
        continue;
    }
    
    $stmt->execute([
        ':brand' => $name,
        ':generic' => $name,
        ':form' => $form,
        ':slug' => $slug,
        ':meta' => json_encode(['source' => 'Updated Essential Drug List 2008'])
    ]);
    $count++;
    echo "[$sl] Inserted: $name ($form)\n";
}

echo "\nTotal inserted: $count\n";
echo "Done.\n";
