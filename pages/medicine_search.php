<?php
$page_title = "Medicine Directory";
require_once '../includes/header.php';
if (!isLoggedIn()) redirect('../index.php');
?>

<style>
    .med-card {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 12px;
        overflow: hidden;
        animation: slideUp 0.4s ease-out backwards;
    }
    .med-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        border-color: var(--bs-primary);
    }
    .med-card.related {
        border-left: 4px solid var(--bs-info);
        background-color: #f8fbff;
    }
    .generic-badge {
        background: linear-gradient(45deg, #f3f4f6, #e5e7eb);
        color: #374151;
        font-weight: 600;
        font-size: 0.75rem;
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .search-pulse {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(26, 115, 232, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(26, 115, 232, 0); }
        100% { box-shadow: 0 0 0 0 rgba(26, 115, 232, 0); }
    }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h1 class="h2 fw-bold text-dark mb-1">Medicine Explorer</h1>
        <p class="text-muted lead mb-0">Advanced pharmacological directory with alternative brand mapping.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <div class="badge bg-primary-soft text-primary p-2 px-3 rounded-pill border">
            <i class="fas fa-database me-2"></i>45,000+ Records
        </div>
    </div>
</div>

<div class="card shadow-lg border-0 mb-5 rounded-4 overflow-hidden">
    <div class="card-body p-5 bg-light">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden bg-white p-1 search-pulse">
                    <span class="input-group-text bg-white border-0 ps-4"><i class="fas fa-search text-primary"></i></span>
                    <input type="text" id="directorySearchInput" class="form-control border-0 fs-5" placeholder="Search brand, generic or symptoms (e.g. Napa, Pain, Fever)...">
                    <button class="btn btn-primary px-5 rounded-pill fw-bold" id="directorySearchBtn">Explore</button>
                </div>
                <div class="mt-3 text-center">
                    <span class="small text-muted">Try searching for <strong>Generic Name</strong> to see all available brands.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4" id="filterSection" style="display: none;">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body p-3">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <span class="text-muted small fw-bold"><i class="fas fa-filter me-2"></i>FILTER BY:</span>
                    </div>
                    <div class="col-md-3">
                        <select id="filterForm" class="form-select form-select-sm rounded-pill border-light">
                            <option value="">All Dosage Forms</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filterType" class="form-select form-select-sm rounded-pill border-light">
                            <option value="">All Types</option>
                            <option value="Essential">Essential</option>
                            <option value="Allopathic">Allopathic</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filterClass" class="form-select form-select-sm rounded-pill border-light">
                            <option value="">All Drug Classes</option>
                        </select>
                    </div>
                    <div class="col-auto ms-auto">
                        <button id="resetFilters" class="btn btn-link btn-sm text-decoration-none text-muted">Clear All</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="directoryResults" class="row g-4">
    <div class="col-12 text-center py-5">
        <div class="py-5">
            <i class="fas fa-microscope fa-4x mb-4 text-primary opacity-25"></i>
            <h4 class="text-dark">Ready to Analyze</h4>
            <p class="text-muted">Type at least 2 characters to trigger the clinical search engine.</p>
        </div>
    </div>
</div>

<!-- Remove Modal -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('directorySearchInput');
    const btn = document.getElementById('directorySearchBtn');
    const results = document.getElementById('directoryResults');
    const filterSection = document.getElementById('filterSection');
    const filterForm = document.getElementById('filterForm');
    const filterType = document.getElementById('filterType');
    const filterClass = document.getElementById('filterClass');
    const resetBtn = document.getElementById('resetFilters');

    let allData = [];

    // Helper to clean text in JS
    function cleanText(text) {
        if (!text) return 'N/A';
        const div = document.createElement('div');
        div.innerHTML = text;
        return div.textContent || div.innerText || '';
    }

    function populateFilters(data) {
        const forms = [...new Set(data.map(m => cleanText(m.dosage_form)).filter(f => f && f !== 'N/A'))].sort();
        const classes = [...new Set(data.map(m => cleanText(m.drug_class)).filter(c => c && c !== 'N/A'))].sort();

        filterForm.innerHTML = '<option value="">All Dosage Forms</option>' + 
            forms.map(f => `<option value="${f}">${f}</option>`).join('');
        
        filterClass.innerHTML = '<option value="">All Drug Classes</option>' + 
            classes.map(c => `<option value="${c}">${c}</option>`).join('');
        
        filterSection.style.display = data.length > 0 ? 'flex' : 'none';
    }

    function applyFilters() {
        const formVal = filterForm.value;
        const typeVal = filterType.value;
        const classVal = filterClass.value;

        const filtered = allData.filter(med => {
            const matchesForm = !formVal || cleanText(med.dosage_form) === formVal;
            const matchesType = !typeVal || (med.type && med.type.toLowerCase().includes(typeVal.toLowerCase()));
            const matchesClass = !classVal || cleanText(med.drug_class) === classVal;
            return matchesForm && matchesType && matchesClass;
        });

        renderResults(filtered, false); // false = don't repopulate filters
    }

    function search() {
        const q = input.value.trim();
        if (q.length < 2) return;

        results.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-grow text-primary" role="status"></div>
            <p class="mt-3 text-muted fw-bold">Analyzing pharmaceutical data...</p>
        </div>`;
        filterSection.style.display = 'none';

        fetch(`../process.php?action=search_medicine&q=${encodeURIComponent(q)}`, {credentials: 'same-origin'})
        .then(r => r.json())
        .then(data => {
            if (data && data.ok === false) throw new Error(data.message || 'Server error');
            
            if (!Array.isArray(data) || data.length === 0) {
                results.innerHTML = '<div class="col-12"><div class="alert alert-info rounded-4 border-0 shadow-sm p-4 text-center">No clinical matches found.</div></div>';
                localStorage.removeItem('last_med_search');
                localStorage.removeItem('last_med_results');
                return;
            }
            
            allData = data;
            localStorage.setItem('last_med_search', q);
            localStorage.setItem('last_med_results', JSON.stringify(data));
            
            populateFilters(data);
            renderResults(data);
        })
        .catch(err => {
            console.error(err);
            results.innerHTML = `<div class="col-12"><div class="alert alert-danger rounded-4 border-0 shadow-sm p-4 text-center">Search failed: ${err.message}</div></div>`;
        });
    }

    function getDosageIcon(form) {
        const f = form.toLowerCase();
        if (f.includes('tablet')) return '<i class="fas fa-tablets text-primary"></i>';
        if (f.includes('capsule')) return '<i class="fas fa-capsules text-success"></i>';
        if (f.includes('injection') || f.includes('vial') || f.includes('ampoule')) return '<i class="fas fa-syringe text-danger"></i>';
        if (f.includes('syrup') || f.includes('liquid') || f.includes('suspension')) return '<i class="fas fa-prescription-bottle text-warning"></i>';
        if (f.includes('drop')) return '<i class="fas fa-eye-dropper text-info"></i>';
        if (f.includes('inhaler') || f.includes('spray')) return '<i class="fas fa-wind text-secondary"></i>';
        if (f.includes('cream') || f.includes('ointment') || f.includes('gel')) return '<i class="fas fa-pump-medical text-dark"></i>';
        if (f.includes('saline') || f.includes('infusion') || f.includes('i/v')) return '<i class="fas fa-vial text-info"></i>';
        return '<i class="fas fa-pills text-muted"></i>';
    }

    function renderResults(data, resetFilterVisibility = true) {
        if (data.length === 0) {
            results.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">No medicines match your current filters.</p></div>';
            return;
        }

        let html = '';
        data.forEach((med, index) => {
            const isRelated = med.type === 'related';
            const brand = cleanText(med.brand_name);
            const generic = cleanText(med.generic_name);
            const strength = cleanText(med.strength);
            const form = cleanText(med.dosage_form);
            const manufacturer = cleanText(med.manufacturer);
            const dosageIcon = getDosageIcon(form);

            html += `
            <div class="col-md-6 col-lg-4" style="animation-delay: ${index * 0.05}s">
                <div class="card h-100 shadow-sm border-0 med-card ${isRelated ? 'related' : ''}">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                ${isRelated ? '<span class="badge bg-info text-white mb-2" style="font-size: 10px;">ALTERNATIVE BRAND</span>' : ''}
                                <h5 class="fw-bold text-dark mb-1">${brand}</h5>
                                <span class="badge generic-badge px-2 py-1 rounded-pill">${generic}</span>
                            </div>
                            <div class="text-end">
                                <div class="fs-4 mb-1">${dosageIcon}</div>
                                <span class="text-primary fw-bold small d-block">${strength === 'N/A' ? '' : strength}</span>
                                <small class="text-muted" style="font-size: 11px;">${form === 'N/A' ? '' : form}</small>
                            </div>
                        </div>
                        <p class="small text-muted mb-4 text-truncate-2" style="height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            <strong>Manufacturer:</strong> ${manufacturer}
                        </p>
                        <div class="d-grid">
                            <a href="medicine_details.php?id=${med.id}" class="btn btn-outline-primary rounded-pill btn-sm">
                                <i class="fas fa-file-medical me-2"></i>View Full Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>`;
        });
        results.innerHTML = html;
    }

    // Events
    btn.addEventListener('click', search);
    input.addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); search(); } });
    
    filterForm.addEventListener('change', applyFilters);
    filterType.addEventListener('change', applyFilters);
    filterClass.addEventListener('change', applyFilters);
    
    resetBtn.addEventListener('click', (e) => {
        e.preventDefault();
        filterForm.value = '';
        filterType.value = '';
        filterClass.value = '';
        renderResults(allData);
    });

    // Load saved data
    const savedSearch = localStorage.getItem('last_med_search');
    const savedData = localStorage.getItem('last_med_results');
    if (savedSearch && savedData) {
        input.value = savedSearch;
        allData = JSON.parse(savedData);
        populateFilters(allData);
        renderResults(allData);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
