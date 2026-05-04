<?php
$page_title = "Medicine Directory";
$hide_footer = true; // Hide footer on this page
require_once '../includes/header.php';
// Publicly accessible - removed isLoggedIn() check
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
    .med-card-enter {
        animation: cardAppear 0.3s ease-out backwards;
    }
    @keyframes cardAppear {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .price-input-mode {
        border: 2px solid var(--bs-success) !important;
        background: #f0fff4 !important;
    }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h1 class="h2 fw-bold text-dark mb-1">Medicine Explorer</h1>
        <p class="text-muted lead mb-0">Advanced pharmacological directory with real-time price tracking.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <div id="google_translate_element" class="d-inline-block me-3"></div>
        <div class="badge bg-primary-soft text-primary p-2 px-3 rounded-pill border">
            <i class="fas fa-database me-2"></i><span id="totalRecordCount">Loading...</span> Records
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
                    <div class="col-md-2">
                        <select id="filterClass" class="form-select form-select-sm rounded-pill border-light">
                            <option value="">All Drug Classes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filterManufacturer" class="form-select form-select-sm rounded-pill border-light">
                            <option value="">All Manufacturers</option>
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
            <i class="fas fa-search fa-4x mb-4 text-primary opacity-25"></i>
            <h4 class="text-dark">Global Medicine Database</h4>
            <p class="text-muted">Type to search 45,000+ brands, generics or indications. Results appear automatically.</p>
        </div>
    </div>
</div>

<div class="row mt-5" id="loadMoreSection" style="display: none;">
    <div class="col-12 text-center">
        <button id="loadMoreBtn" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
            <i class="fas fa-plus-circle me-2"></i>Load More Medicines
        </button>
    </div>
</div>

<!-- Google Translate Widget -->
<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', includedLanguages: 'bn,en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
}
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

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
    const filterManufacturer = document.getElementById('filterManufacturer');
    const resetBtn = document.getElementById('resetFilters');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadMoreSection = document.getElementById('loadMoreSection');
    const totalRecordCountSpan = document.getElementById('totalRecordCount');

    let allData = [];
    let currentOffset = 0;
    const itemsPerPage = 20;
    let currentSearchQuery = '';

    // Fetch dynamic record count with timeout/error handling
    fetch('../process.php?action=get_medicine_count')
        .then(r => r.json())
        .then(data => {
            if (data.ok) totalRecordCountSpan.textContent = data.total.toLocaleString();
            else totalRecordCountSpan.textContent = '45,000+';
        })
        .catch(() => { totalRecordCountSpan.textContent = '45,000+'; });

    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

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
        const manufacturers = [...new Set(data.map(m => cleanText(m.manufacturer)).filter(m => m && m !== 'N/A'))].sort();

        filterForm.innerHTML = '<option value="">All Dosage Forms</option>' + 
            forms.map(f => `<option value="${f}">${f}</option>`).join('');
        
        filterClass.innerHTML = '<option value="">All Drug Classes</option>' + 
            classes.map(c => `<option value="${c}">${c}</option>`).join('');

        filterManufacturer.innerHTML = '<option value="">All Manufacturers</option>' + 
            manufacturers.map(m => `<option value="${m}">${m}</option>`).join('');
        
        filterSection.style.display = data.length > 0 ? 'flex' : 'none';
    }

    function applyFilters() {
        const formVal = filterForm.value;
        const typeVal = filterType.value;
        const classVal = filterClass.value;
        const manufacturerVal = filterManufacturer.value;

        const filtered = allData.filter(med => {
            const matchesForm = !formVal || cleanText(med.dosage_form) === formVal;
            const matchesType = !typeVal || (med.type && med.type.toLowerCase().includes(typeVal.toLowerCase()));
            const matchesClass = !classVal || cleanText(med.drug_class) === classVal;
            const matchesManufacturer = !manufacturerVal || cleanText(med.manufacturer) === manufacturerVal;
            return matchesForm && matchesType && matchesClass && matchesManufacturer;
        });

        renderResults(filtered, false); 
    }

    function search(isLoadMore = false) {
        const q = input.value.trim() || 'Square'; // Default to Square for initial load if empty
        
        if (!isLoadMore) {
            currentOffset = 0;
            allData = [];
            results.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                <h5 class="text-primary fw-bold">Searching clinical database...</h5>
                <p class="text-muted small">Prioritizing famous brands...</p>
            </div>`;
            filterSection.style.display = 'none';
        }

        currentSearchQuery = q;

        fetch(`../process.php?action=search_medicine&q=${encodeURIComponent(q)}&limit=${itemsPerPage}&offset=${currentOffset}`, {credentials: 'same-origin'})
        .then(r => r.json())
        .then(data => {
            if (data && data.ok === false) throw new Error(data.message || 'Server error');
            
            if (!Array.isArray(data) || data.length === 0) {
                if (!isLoadMore) {
                    results.innerHTML = '<div class="col-12"><div class="alert alert-info rounded-4 border-0 shadow-sm p-4 text-center">No clinical matches found.</div></div>';
                }
                loadMoreSection.style.display = 'none';
                return;
            }
            
            if (!isLoadMore) results.innerHTML = ''; // Clear previous results on new search

            allData = [...allData, ...data];
            currentOffset += data.length;
            
            populateFilters(allData);
            renderResults(data, !isLoadMore); // append results

            // Show load more if we got a full page
            loadMoreSection.style.display = data.length === itemsPerPage ? 'block' : 'none';
        })
        .catch(err => {
            console.error(err);
            if (!isLoadMore) {
                results.innerHTML = `<div class="col-12"><div class="alert alert-danger rounded-4 border-0 shadow-sm p-4 text-center">Search failed: ${err.message}</div></div>`;
            }
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

    function renderResults(data, clearResults = true) {
        if (data.length === 0 && clearResults) {
            results.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">No medicines match your current filters.</p></div>';
            return;
        }

        let html = clearResults ? '' : results.innerHTML;
        data.forEach((med, index) => {
            const isRelated = med.type === 'related';
            const brand = cleanText(med.brand_name);
            const generic = cleanText(med.generic_name);
            const strength = cleanText(med.strength);
            const form = cleanText(med.dosage_form);
            const manufacturer = cleanText(med.manufacturer);
            const price = med.unit_price ? parseFloat(med.unit_price).toFixed(2) : '0.00';
            const dosageIcon = getDosageIcon(form);

            html += `
            <div class="col-md-6 col-lg-4 med-card-enter" style="animation-delay: ${(index % itemsPerPage) * 0.05}s">
                <div class="card h-100 shadow-sm border-0 med-card ${isRelated ? 'related' : ''}">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div style="max-width: 70%;">
                                ${isRelated ? '<span class="badge bg-info text-white mb-2" style="font-size: 10px;">ALTERNATIVE BRAND</span>' : ''}
                                <h5 class="fw-bold text-dark mb-1 text-truncate" title="${brand}">${brand}</h5>
                                <span class="badge generic-badge px-2 py-1 rounded-pill text-truncate d-inline-block" style="max-width: 100%;" title="${generic}">${generic}</span>
                            </div>
                            <div class="text-end">
                                <div class="fs-4 mb-1">${dosageIcon}</div>
                                <span class="text-primary fw-bold small d-block">${strength === 'N/A' ? '' : strength}</span>
                                <small class="text-muted" style="font-size: 11px;">${form === 'N/A' ? '' : form}</small>
                            </div>
                        </div>
                        <div class="row g-2 mb-4 mt-auto">
                            <div class="col-7">
                                <p class="small text-muted mb-0 text-truncate" title="${manufacturer}">
                                    <strong>Manufacturer:</strong><br>${manufacturer}
                                </p>
                            </div>
                            <div class="col-5 text-end">
                                <div class="price-container" id="price-box-${med.id}">
                                    <p class="small text-success mb-0 fw-bold">
                                        ৳ <span class="price-val">${price}</span>
                                        <i class="fas fa-edit ms-1 cursor-pointer text-muted small opacity-50 edit-price-btn" data-id="${med.id}" data-price="${price}"></i>
                                    </p>
                                </div>
                            </div>
                        </div>
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
    const debouncedSearch = debounce(search, 300);
    input.addEventListener('input', debouncedSearch);
    btn.addEventListener('click', search);
    input.addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); search(); } });
    
    filterForm.addEventListener('change', applyFilters);
    filterType.addEventListener('change', applyFilters);
    filterClass.addEventListener('change', applyFilters);
    filterManufacturer.addEventListener('change', applyFilters);
    
    resetBtn.addEventListener('click', (e) => {
        e.preventDefault();
        filterForm.value = '';
        filterType.value = '';
        filterClass.value = '';
        filterManufacturer.value = '';
        renderResults(allData);
    });

    loadMoreBtn.addEventListener('click', () => search(true));

    // Handle Public Price Edits
    results.addEventListener('click', e => {
        if (e.target.classList.contains('edit-price-btn')) {
            const id = e.target.dataset.id;
            const currentPrice = e.target.dataset.price;
            const container = document.getElementById(`price-box-${id}`);
            
            container.innerHTML = `
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" class="form-control price-edit-input" value="${currentPrice}" style="width: 60px;">
                    <button class="btn btn-success btn-sm save-price-btn" data-id="${id}"><i class="fas fa-check"></i></button>
                </div>
            `;
        }
        
        if (e.target.closest('.save-price-btn')) {
            const btn = e.target.closest('.save-price-btn');
            const id = btn.dataset.id;
            const container = document.getElementById(`price-box-${id}`);
            const newPrice = container.querySelector('.price-edit-input').value;
            
            if (newPrice > 0) {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('price', newPrice);
                
                fetch('../process.php?action=update_medicine_price', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        container.innerHTML = `
                            <p class="small text-success mb-0 fw-bold">
                                ৳ <span class="price-val">${parseFloat(newPrice).toFixed(2)}</span>
                                <i class="fas fa-edit ms-1 cursor-pointer text-muted small opacity-50 edit-price-btn" data-id="${id}" data-price="${newPrice}"></i>
                            </p>
                        `;
                        // Show a small toast or success indicator if available
                    } else {
                        alert('Update failed: ' + data.message);
                    }
                });
            }
        }
    });

    // No local storage used - fresh search every time
});
</script>

<?php require_once '../includes/footer.php'; ?>
