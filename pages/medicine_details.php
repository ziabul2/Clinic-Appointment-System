<?php
$page_title = "Medicine Details";
require_once '../includes/header.php';
if (!isLoggedIn()) redirect('../index.php');

$id = $_GET['id'] ?? null;
$medicine = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM medicine_master_data WHERE id = ?");
    $stmt->execute([$id]);
    $medicine = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$medicine) {
    echo '<div class="alert alert-danger mt-4">Medicine not found. <a href="medicine_search.php">Go back to search</a></div>';
    require_once '../includes/footer.php';
    exit;
}

// Helper to clean HTML from data
function clean_clinical_text($text) {
    if (!$text) return 'N/A';
    // Remove specific known tags or all tags
    $cleaned = strip_tags($text);
    // Decode entities just in case
    $cleaned = html_entity_decode($cleaned);
    return trim($cleaned);
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-auto">
            <a href="medicine_search.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Back to Search
            </a>
        </div>
        <div class="col">
            <h1 class="h2 fw-bold text-dark mb-0"><?php echo htmlspecialchars($medicine['brand_name']); ?></h1>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar / Basic Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4 text-center">
                    <div class="p-4 bg-primary-soft rounded-pill d-inline-block mb-3">
                        <i class="fas fa-pills fa-3x text-primary"></i>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($medicine['brand_name']); ?></h3>
                    <p class="badge bg-light text-dark border px-3 py-2 rounded-pill fs-6"><?php echo clean_clinical_text($medicine['generic_name']); ?></p>
                    
                    <div class="mt-4 text-start">
                        <div class="mb-3">
                            <label class="small text-muted fw-bold d-block">STRENGTH</label>
                            <div class="fw-bold text-dark fs-5"><?php echo clean_clinical_text($medicine['strength']); ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold d-block">DOSAGE FORM</label>
                            <div class="fw-bold text-dark"><?php echo clean_clinical_text($medicine['dosage_form']); ?></div>
                        </div>
                        <div class="mb-0">
                            <label class="small text-muted fw-bold d-block">MANUFACTURER</label>
                            <div class="fw-bold text-dark text-primary"><?php echo clean_clinical_text($medicine['manufacturer']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-dark text-white p-3 border-0">
                    <h5 class="mb-0 small fw-bold">DRUG CLASSIFICATION</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="small text-muted fw-bold d-block">CLASS</label>
                        <p class="mb-0 text-dark"><?php echo clean_clinical_text($medicine['drug_class']); ?></p>
                    </div>
                    <div>
                        <label class="small text-muted fw-bold d-block">TYPE</label>
                        <p class="mb-0 text-dark"><?php echo htmlspecialchars($medicine['type'] ?: 'Allopathic'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Monographs -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom p-0">
                    <ul class="nav nav-pills p-3 gap-2" id="medTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#indications">Indications</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#pharmacology">Pharmacology</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#dosage">Dosage</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#safety">Safety</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content">
                        <!-- Indications -->
                        <div class="tab-pane fade show active" id="indications">
                            <h5 class="fw-bold mb-3">Therapeutic Indications</h5>
                            <div class="content-text lead fs-6">
                                <?php echo nl2br(clean_clinical_text($medicine['indication'])); ?>
                            </div>
                            <?php if ($medicine['indication_description']): ?>
                                <hr class="my-4 opacity-5">
                                <h6 class="fw-bold text-muted small mb-3 uppercase">DETAILED DESCRIPTION</h6>
                                <div class="small text-secondary">
                                    <?php echo nl2br(clean_clinical_text($medicine['indication_description'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Pharmacology -->
                        <div class="tab-pane fade" id="pharmacology">
                            <h5 class="fw-bold mb-3">Pharmacological Profile</h5>
                            <div class="mb-4">
                                <label class="small text-primary fw-bold d-block mb-2">THERAPEUTIC CLASS</label>
                                <p><?php echo nl2br(clean_clinical_text($medicine['therapeutic_class_description'])); ?></p>
                            </div>
                            <div>
                                <label class="small text-primary fw-bold d-block mb-2">MECHANISM OF ACTION</label>
                                <p><?php echo nl2br(clean_clinical_text($medicine['pharmacology_description'])); ?></p>
                            </div>
                        </div>

                        <!-- Dosage -->
                        <div class="tab-pane fade" id="dosage">
                            <h5 class="fw-bold mb-3">Administration & Dosage</h5>
                            <div class="p-3 bg-light rounded-4 mb-4 border-start border-primary border-4">
                                <?php echo nl2br(clean_clinical_text($medicine['dosage_description'])); ?>
                            </div>
                            <h6 class="fw-bold small mb-2">MODE OF ADMINISTRATION</h6>
                            <p><?php echo nl2br(clean_clinical_text($medicine['administration_description'])); ?></p>
                        </div>

                        <!-- Safety -->
                        <div class="tab-pane fade" id="safety">
                            <div class="mb-4">
                                <h5 class="fw-bold text-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Contraindications</h5>
                                <p><?php echo nl2br(clean_clinical_text($medicine['contraindications_description'])); ?></p>
                            </div>
                            <div class="mb-4">
                                <h5 class="fw-bold text-warning mb-3"><i class="fas fa-biohazard me-2"></i>Side Effects</h5>
                                <p><?php echo nl2br(clean_clinical_text($medicine['side_effects_description'])); ?></p>
                            </div>
                            <div class="mb-4">
                                <h5 class="fw-bold text-info mb-3"><i class="fas fa-baby me-2"></i>Pregnancy & Lactation</h5>
                                <p><?php echo nl2br(clean_clinical_text($medicine['pregnancy_and_lactation_description'])); ?></p>
                            </div>
                            <div>
                                <h5 class="fw-bold text-secondary mb-3"><i class="fas fa-shield-alt me-2"></i>Precautions</h5>
                                <p><?php echo nl2br(clean_clinical_text($medicine['precautions_description'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .content-text { line-height: 1.8; color: #4b5563; }
    .nav-pills .nav-link { color: #6b7280; font-weight: 600; border: 1px solid transparent; }
    .nav-pills .nav-link.active { background-color: var(--bs-primary); color: white; }
    .nav-pills .nav-link:not(.active):hover { background-color: #f3f4f6; }
</style>

<?php require_once '../includes/footer.php'; ?>
