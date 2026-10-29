/**
 * VLMS Dynamic Real-time Sync & Interactive Engine
 * Handles background live-polling, AJAX checkouts, dynamic toasts, and autocomplete
 */

// SweetAlert2 Toast configuration
const Toast = (typeof Swal !== 'undefined') ? Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
}) : {
    fire: (opts) => console.log(opts.title)
};

let autoSyncEnabled = true;
let syncInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    initDynamicSync();
    initDynamicCheckouts();
    initHostAutocomplete();
    initInstantTableSearch();
    initEditVisitorForm();
});

/**
 * 1. Initialize Real-Time Background Synchronization
 */
function initDynamicSync() {
    const syncStatusEl = document.getElementById('liveSyncBadge');
    
    // Initial fetch
    pollServerStats();

    // Start background interval (every 10 seconds)
    syncInterval = setInterval(() => {
        if (autoSyncEnabled) {
            pollServerStats();
        }
    }, 10000);

    // Toggle sync listener
    if (syncStatusEl) {
        syncStatusEl.addEventListener('click', (e) => {
            e.preventDefault();
            autoSyncEnabled = !autoSyncEnabled;
            updateSyncUI(autoSyncEnabled);
            Toast.fire({
                icon: 'info',
                title: autoSyncEnabled ? 'Real-time live sync resumed' : 'Real-time live sync paused'
            });
        });
    }
}

function updateSyncUI(enabled) {
    const syncBadge = document.getElementById('liveSyncBadge');
    if (!syncBadge) return;
    if (enabled) {
        syncBadge.className = 'badge text-bg-success d-flex align-items-center gap-1 cursor-pointer';
        syncBadge.innerHTML = '<i class="bi bi-broadcast"></i> <span>SYNC ON</span>';
        syncBadge.title = 'Click to pause auto-sync';
    } else {
        syncBadge.className = 'badge text-bg-secondary d-flex align-items-center gap-1 cursor-pointer';
        syncBadge.innerHTML = '<i class="bi bi-pause-circle"></i> <span>PAUSED</span>';
        syncBadge.title = 'Click to resume auto-sync';
    }
}

/**
 * Poll server for latest stats and active visitors
 */
function pollServerStats() {
    fetch('api/stats.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            // Update KPI cards if present on page
            updateKpiCounter('kpiCurrentlyInside', data.stats.currently_inside);
            updateKpiCounter('kpiCompletedToday', data.stats.completed_today);
            updateKpiCounter('kpiTotalRegistered', data.stats.total_all_time);
            updateKpiCounter('kpiOccupancyRate', data.stats.active_capacity_percent, '%');

            const occSub = document.getElementById('kpiOccupancySub');
            if (occSub) {
                occSub.textContent = `${data.stats.currently_inside} / ${data.stats.max_capacity}`;
            }

            // Update navbar and sidebar badges
            const navBadge = document.getElementById('navInsideBadge');
            if (navBadge) navBadge.textContent = data.stats.currently_inside;

            const sideBadge = document.getElementById('sidebarInsideBadge');
            if (sideBadge) {
                sideBadge.textContent = data.stats.currently_inside;
                sideBadge.style.display = data.stats.currently_inside > 0 ? '' : 'none';
            }

            // Sync live active visitors table if present
            syncActiveTable(data.active_visitors);
        })
        .catch(err => console.debug('Sync poll notice:', err));
}

/**
 * Animate numbers when KPI updates
 */
function updateKpiCounter(elementId, newValue, suffix = '') {
    const el = document.getElementById(elementId);
    if (!el) return;

    const currentText = el.textContent.replace(/[^\d]/g, '');
    const currentVal = parseInt(currentText, 10) || 0;

    if (currentVal !== newValue) {
        el.textContent = newValue + suffix;
        el.classList.add('kpi-update-highlight');
        setTimeout(() => el.classList.remove('kpi-update-highlight'), 1000);
    }
}

/**
 * Synchronize the active visitors table dynamically
 */
function syncActiveTable(visitors) {
    const tableBody = document.querySelector('.dynamic-active-table tbody');
    if (!tableBody) return;
    const tableEl = tableBody.closest('table');
    const thCount = tableEl ? tableEl.querySelectorAll('thead th').length : 9;

    if (!visitors || visitors.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="${thCount}" class="text-center py-4 text-muted">
                    <i class="bi bi-check2-circle fs-2 text-success d-block mb-2"></i>
                    No visitors currently logged inside the facility.
                </td>
            </tr>`;
        return;
    }

    // Check if table rows match current visitors
    const existingIds = Array.from(tableBody.querySelectorAll('tr[data-visitor-id]'))
        .map(tr => parseInt(tr.getAttribute('data-visitor-id'), 10));

    const newIds = visitors.map(v => v.id);

    // If counts and IDs match, just update durations
    const same = existingIds.length === newIds.length && existingIds.every((v, i) => v === newIds[i]);
    if (same) {
        visitors.forEach(v => {
            const tr = tableBody.querySelector(`tr[data-visitor-id="${v.id}"]`);
            if (tr) {
                const durEl = tr.querySelector('.visitor-duration');
                if (durEl) durEl.textContent = v.duration;
            }
        });
        return;
    }

    // Rebuild rows dynamically with smooth transition
    let html = '';
    const isDashboard = (thCount === 7);

    visitors.forEach(v => {
        const safeName = v.full_name.replace(/'/g, "\\'");
        if (isDashboard) {
            html += `
            <tr data-visitor-id="${v.id}" class="fade-in-row">
                <td>
                    <span class="badge text-bg-primary font-monospace">${v.badge_number}</span>
                </td>
                <td>
                    <div class="fw-bold text-body">${v.full_name}</div>
                    <small class="text-muted">${v.company_or_address}</small>
                </td>
                <td>
                    <div class="fw-semibold">${v.person_to_visit}</div>
                    <small class="text-muted">${v.department_to_visit}</small>
                </td>
                <td>
                    <span class="badge text-bg-secondary">${v.purpose_of_visit}</span>
                </td>
                <td>
                    <span class="text-success fw-bold font-monospace">${v.time_in_formatted}</span>
                    <div class="small text-muted">${v.date_in_formatted}</div>
                </td>
                <td>
                    <span class="badge text-bg-light border visitor-duration">${v.duration}</span>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick="viewDynamicVisitorDetails(${v.id})" title="Details">
                            Details
                        </button>
                        <a href="visitor_pass.php?id=${v.id}" target="_blank" class="btn btn-outline-info" title="Pass">
                            Pass
                        </a>
                        <button type="button" class="btn btn-outline-warning" onclick="openEditVisitorModal(${v.id})" title="Edit">
                            Edit
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="triggerDynamicDelete(${v.id}, '${safeName}', '${v.badge_number}')" title="Delete">
                            Delete
                        </button>
                        ${!v.timed_in_confirmed ? `
                        <button type="button" class="btn btn-success" onclick="triggerDynamicTimeIn(${v.id}, '${safeName}', '${v.badge_number}')" title="Time-In">
                            Time-In
                        </button>` : ''}
                        <button type="button" class="btn btn-danger" onclick="triggerDynamicTimeOut(${v.id}, '${safeName}', '${v.badge_number}')" title="Time-Out">
                            Time-Out
                        </button>
                    </div>
                </td>
            </tr>`;
        } else {
            html += `
            <tr data-visitor-id="${v.id}" class="fade-in-row">
                <td>
                    <span class="badge text-bg-success d-inline-flex align-items-center gap-1">
                        <span class="live-dot" style="width:6px; height:6px;"></span> Inside
                    </span>
                </td>
                <td>
                    <span class="badge text-bg-primary font-monospace">${v.badge_number}</span>
                </td>
                <td>
                    <div class="fw-bold text-body">${v.full_name}</div>
                    <small class="text-muted">${v.company_or_address}</small>
                </td>
                <td>
                    <div class="small fw-semibold text-body">${v.contact_number}</div>
                    <small class="text-muted">${v.email || 'No email'}</small>
                </td>
                <td>
                    <span class="badge text-bg-secondary">${v.purpose_of_visit}</span>
                </td>
                <td>
                    <div class="fw-semibold text-body">${v.person_to_visit}</div>
                    <small class="text-muted">${v.department_to_visit}</small>
                </td>
                <td>
                    <span class="text-success fw-bold font-monospace">${v.time_in_formatted}</span>
                    <div class="small text-muted">${v.date_in_formatted}</div>
                </td>
                <td>
                    <span class="badge text-bg-light border fw-bold visitor-duration">${v.duration}</span>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick="viewDynamicVisitorDetails(${v.id})" title="Details">
                            Details
                        </button>
                        <a href="visitor_pass.php?id=${v.id}" target="_blank" class="btn btn-outline-info" title="Pass">
                            Pass
                        </a>
                        <button type="button" class="btn btn-outline-warning" onclick="openEditVisitorModal(${v.id})" title="Edit">
                            Edit
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="triggerDynamicDelete(${v.id}, '${safeName}', '${v.badge_number}')" title="Delete">
                            Delete
                        </button>
                        ${!v.timed_in_confirmed ? `
                        <button type="button" class="btn btn-success" onclick="triggerDynamicTimeIn(${v.id}, '${safeName}', '${v.badge_number}')" title="Time-In">
                            Time-In
                        </button>` : ''}
                        <button type="button" class="btn btn-danger" onclick="triggerDynamicTimeOut(${v.id}, '${safeName}', '${v.badge_number}')" title="Time-Out">
                            Time-Out
                        </button>
                    </div>
                </td>
            </tr>`;
        }
    });

    tableBody.innerHTML = html;
}

/**
 * 2. Dynamic AJAX Time-Out Departure Handler
 */
function triggerDynamicTimeOut(visitorId, visitorName, badgeNumber) {
    const safeName = (visitorName || '').replace(/'/g, "\\'");
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Confirm Departure?',
            html: `Record departure for <strong>${visitorName}</strong> (<code class="text-primary">${badgeNumber}</code>)?<br><small class="text-muted">Departure Time: ${new Date().toLocaleTimeString()}</small>`,
            icon: 'question',
            input: 'text',
            inputPlaceholder: 'Exit notes / remarks (optional)',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Record Time-Out',
            showLoaderOnConfirm: true,
            preConfirm: (remarks) => {
                const formData = new FormData();
                formData.append('action', 'record_timeout');
                formData.append('visitor_id', visitorId);
                formData.append('remarks', remarks || '');

                return fetch('api/visitor_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Departure failed');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value?.success) {
                const data = result.value;
                const row = document.querySelector(`tr[data-visitor-id="${visitorId}"]`);
                const isHistoryPage = window.location.pathname.includes('visitor_history.php');
                const isFilteringInsideOnly = window.location.search.includes('status=Inside');

                if (row && isHistoryPage && !isFilteringInsideOnly) {
                    // Update in-place on history page without removing row
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 10) {
                        cells[1].innerHTML = `<span class="badge text-bg-secondary">Completed</span>`;
                        if (data.time_out_time) {
                            cells[7].innerHTML = `
                                <span class="font-monospace fw-bold text-secondary">${data.time_out_time}</span>
                                <div class="small text-muted">${data.time_out_date || ''}</div>
                            `;
                        }
                        if (data.duration) {
                            cells[8].innerHTML = `<span class="badge text-bg-light border">${data.duration}</span>`;
                        }
                        // Remove Time-In and Time-Out buttons from actions column once timed out
                        const btnGroup = cells[9].querySelector('.btn-group');
                        if (btnGroup) {
                            btnGroup.querySelectorAll('button, a').forEach(btn => {
                                const txt = (btn.textContent || '').trim();
                                if (txt === 'Time-In' || txt === 'Time-Out' || btn.title === 'Time-In' || btn.title === 'Time-Out') {
                                    btn.remove();
                                }
                            });
                        }
                    }
                    row.classList.add('table-warning');
                    setTimeout(() => row.classList.remove('table-warning'), 1500);
                } else if (row) {
                    // Remove row from active visitors table smoothly
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => row.remove(), 400);
                }

                Toast.fire({
                    icon: 'success',
                    title: result.value.message
                });

                // Immediately poll stats to update KPI boxes
                pollServerStats();
            }
        });
    } else {
        // Fallback to standard modal if SweetAlert2 is not loaded
        confirmTimeOut(visitorId, visitorName, badgeNumber);
    }
}

/**
 * 2b. Dynamic AJAX Time-In Arrival / Re-entry Handler
 */
function triggerDynamicTimeIn(visitorId, visitorName, badgeNumber) {
    const safeName = (visitorName || '').replace(/'/g, "\\'");
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Confirm Time-In?',
            html: `Record arrival / time-in for <strong>${visitorName}</strong> (<code class="text-primary">${badgeNumber}</code>)?<br><small class="text-muted">Arrival Time: ${new Date().toLocaleTimeString()}</small>`,
            icon: 'question',
            input: 'text',
            inputPlaceholder: 'Notes / remarks (optional, e.g. Return visit, Re-entry)',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-box-arrow-in-right me-1"></i> Record Time-In',
            showLoaderOnConfirm: true,
            preConfirm: (remarks) => {
                const formData = new FormData();
                formData.append('action', 'record_timein');
                formData.append('visitor_id', visitorId);
                formData.append('remarks', remarks || '');

                return fetch('api/visitor_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Time-In recording failed');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value?.success) {
                const data = result.value;
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });

                // Update table row if present
                const row = document.querySelector(`tr[data-visitor-id="${visitorId}"]`);
                const isHistoryPage = window.location.pathname.includes('visitor_history.php');

                if (row) {
                    const cells = row.querySelectorAll('td');

                    // 1. Remove Time-In button immediately across ALL tables (History, Current Visitors, Dashboard)
                    const btnGroup = row.querySelector('.btn-group');
                    if (btnGroup) {
                        btnGroup.querySelectorAll('button, a').forEach(btn => {
                            const txt = (btn.textContent || '').trim();
                            if (txt === 'Time-In' || btn.title === 'Time-In') {
                                btn.remove();
                            }
                        });
                    }

                    // 2. Update table row data according to table schema
                    if (isHistoryPage && cells.length >= 10) {
                        // visitor_history.php: 0=Badge, 1=Status, 2=Name, 3=Contact, 4=Purpose, 5=Host, 6=Time-In, 7=Time-Out, 8=Duration, 9=Actions
                        cells[1].innerHTML = `<span class="badge text-bg-success"><span class="live-dot" style="width:6px; height:6px;"></span> Inside</span>`;
                        if (data.time_in_time) {
                            cells[6].innerHTML = `
                                <span class="text-success fw-bold font-monospace">${data.time_in_time}</span>
                                <div class="small text-muted">${data.time_in_date || ''}</div>
                            `;
                        }
                        cells[7].innerHTML = `<span class="badge text-bg-success">Active</span>`;
                        cells[8].innerHTML = `<span class="badge text-bg-light border">${data.duration || 'Just now'}</span>`;
                    } else if (cells.length === 9) {
                        // current_visitors.php: 0=Status, 1=Badge, 2=Name, 3=Contact, 4=Purpose, 5=Host, 6=Time-In, 7=Duration, 8=Actions
                        cells[0].innerHTML = `<span class="badge text-bg-success d-inline-flex align-items-center gap-1"><span class="live-dot" style="width:6px; height:6px;"></span> Inside</span>`;
                        if (data.time_in_time) {
                            cells[6].innerHTML = `
                                <span class="text-success fw-bold font-monospace">${data.time_in_time}</span>
                                <div class="small text-muted">${data.time_in_date || ''}</div>
                            `;
                        }
                        cells[7].innerHTML = `<span class="badge text-bg-light border fw-bold visitor-duration">${data.duration || 'Just now'}</span>`;
                    } else if (cells.length === 7) {
                        // dashboard.php: 0=Badge, 1=Name, 2=Host, 3=Purpose, 4=Time-In, 5=Duration, 6=Actions
                        if (data.time_in_time) {
                            cells[4].innerHTML = `
                                <span class="text-success fw-bold font-monospace">${data.time_in_time}</span>
                                <div class="small text-muted">${data.time_in_date || ''}</div>
                            `;
                        }
                        cells[5].innerHTML = `<span class="badge text-bg-light border visitor-duration">${data.duration || 'Just now'}</span>`;
                    }

                    row.classList.add('table-success');
                    setTimeout(() => row.classList.remove('table-success'), 1500);
                }

                if (typeof pollServerStats === 'function') {
                    pollServerStats();
                }
            }
        });
    } else {
        if (typeof confirmTimeIn === 'function') {
            confirmTimeIn(visitorId, visitorName, badgeNumber);
        }
    }
}

/**
 * 3. Dynamic Quick Badge Scan Handler (AJAX)
 */
function initDynamicCheckouts() {
    const quickForm = document.getElementById('dynamicQuickScanForm');
    if (!quickForm) return;

    quickForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const input = quickForm.querySelector('input[name="scan_badge"]');
        const badge = input ? input.value.trim() : '';
        if (!badge) return;

        const formData = new FormData();
        formData.append('action', 'quick_checkout');
        formData.append('scan_badge', badge);

        fetch('api/visitor_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
                input.value = '';

                // Remove row if in active table
                const row = document.querySelector(`tr[data-visitor-id="${data.visitor_id}"]`);
                if (row) {
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 400);
                }

                pollServerStats();
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(err => {
            Toast.fire({ icon: 'error', title: 'Network request error' });
        });
    });
}

/**
 * 4. Dynamic Visitor Detail Viewer (via API)
 */
function viewDynamicVisitorDetails(visitorId) {
    fetch(`api/visitor_actions.php?action=get_visitor&id=${visitorId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.visitor) {
                viewVisitorDetails(data.visitor);
            }
        })
        .catch(err => console.error(err));
}

/**
 * 5. Dynamic Host Autocomplete for Registration Form
 */
function initHostAutocomplete() {
    const hostInput = document.getElementById('person_to_visit');
    const deptSelect = document.getElementById('department_to_visit');
    if (!hostInput) return;

    // Create datalist dynamically
    let datalist = document.getElementById('hostSuggestionsList');
    if (!datalist) {
        datalist = document.createElement('datalist');
        datalist.id = 'hostSuggestionsList';
        document.body.appendChild(datalist);
        hostInput.setAttribute('list', 'hostSuggestionsList');
    }

    let debounceTimer = null;
    hostInput.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) return;

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetch(`api/visitor_actions.php?action=autocomplete_hosts&term=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.suggestions) {
                        datalist.innerHTML = '';
                        data.suggestions.forEach(item => {
                            const opt = document.createElement('option');
                            opt.value = item.host;
                            opt.setAttribute('data-dept', item.dept);
                            datalist.appendChild(opt);
                        });
                    }
                });
        }, 250);
    });

    hostInput.addEventListener('change', function() {
        const val = this.value;
        const matchingOpt = datalist.querySelector(`option[value="${val}"]`);
        if (matchingOpt && deptSelect) {
            const dept = matchingOpt.getAttribute('data-dept');
            if (dept) {
                deptSelect.value = dept;
            }
        }
    });
}

/**
 * 6. Instant Live Table Search
 */
function initInstantTableSearch() {
    const searchInputs = document.querySelectorAll('#tableSearchInput, .instant-table-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            const table = document.querySelector('.data-table tbody, .dynamic-active-table tbody');
            if (!table) return;

            const rows = table.querySelectorAll('tr');
            rows.forEach(r => {
                if (r.cells.length <= 1) return; // skip empty state row
                r.style.display = r.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    });
}

/**
 * 7. Open Edit Visitor Modal and Populate Data
 */
function openEditVisitorModal(visitorId) {
    const alertEl = document.getElementById('editVisitorAlert');
    if (alertEl) {
        alertEl.classList.add('d-none');
        alertEl.textContent = '';
    }

    fetch(`api/visitor_actions.php?action=get_visitor&id=${visitorId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.visitor) {
                if (typeof Toast !== 'undefined') {
                    Toast.fire({ icon: 'error', title: data.message || 'Failed to load visitor details' });
                } else {
                    alert(data.message || 'Failed to load visitor details');
                }
                return;
            }

            const v = data.visitor;
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val || '';
            };

            setVal('editVisitorId', v.id);
            setVal('editFullName', v.full_name);
            setVal('editContactNumber', v.contact_number);
            setVal('editEmail', v.email);
            setVal('editCompany', v.company_or_address);
            setVal('editIdType', v.id_type);
            setVal('editIdNumber', v.id_number);
            setVal('editPurpose', v.purpose_of_visit);
            setVal('editPersonToVisit', v.person_to_visit);
            setVal('editDeptToVisit', v.department_to_visit);
            setVal('editStatus', v.status || 'Inside');
            setVal('editTimeIn', v.raw_time_in);
            setVal('editTimeOut', v.raw_time_out);
            setVal('editRemarks', v.remarks);

            const badgeDisplay = document.getElementById('editBadgeDisplay');
            if (badgeDisplay) badgeDisplay.textContent = v.badge_number;

            const fullPageBtn = document.getElementById('editOpenFullPageBtn');
            if (fullPageBtn) {
                fullPageBtn.href = `edit_visitor.php?id=${encodeURIComponent(v.id)}`;
            }

            if (typeof toggleEditTimeOutField === 'function') {
                toggleEditTimeOutField(v.status);
            }

            const modalEl = document.getElementById('editVisitorModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        })
        .catch(err => {
            console.error(err);
            if (typeof Toast !== 'undefined') {
                Toast.fire({ icon: 'error', title: 'Network error loading visitor' });
            }
        });
}

/**
 * 8. Dynamic Visitor Deletion with Confirmation
 */
function triggerDynamicDelete(visitorId, visitorName, badgeNumber) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Visitor Record?',
            html: `Are you sure you want to permanently delete <strong>${visitorName}</strong> (<code class="text-primary">${badgeNumber}</code>)?<br><small class="text-danger">This record will be permanently deleted from the database.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash me-1"></i> Yes, Delete',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const formData = new FormData();
                formData.append('action', 'delete_visitor');
                formData.append('visitor_id', visitorId);

                return fetch('api/visitor_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Deletion failed');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value?.success) {
                // Animate and remove row from table
                const rows = document.querySelectorAll(`tr[data-visitor-id="${visitorId}"]`);
                rows.forEach(row => {
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        const tbody = row.closest('tbody');
                        row.remove();
                        if (tbody && tbody.querySelectorAll('tr').length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                        <h5>No Visitor Records Found</h5>
                                    </td>
                                </tr>`;
                        }
                    }, 400);
                });

                Toast.fire({
                    icon: 'success',
                    title: result.value.message
                });

                // Update live counters
                if (typeof pollServerStats === 'function') {
                    pollServerStats();
                }
            }
        });
    } else {
        if (confirm(`Permanently delete visitor record for ${visitorName} (${badgeNumber})? This cannot be undone.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'visitor_history.php';
            const actInput = document.createElement('input');
            actInput.type = 'hidden';
            actInput.name = 'action';
            actInput.value = 'delete_visitor';
            form.appendChild(actInput);
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'visitor_id';
            idInput.value = visitorId;
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
}

/**
 * 9. Initialize Edit Visitor AJAX Submission
 */
function initEditVisitorForm() {
    const editForm = document.getElementById('editVisitorForm');
    if (!editForm) return;

    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('saveEditBtn');
        const alertEl = document.getElementById('editVisitorAlert');
        const origBtnText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...';
        }
        if (alertEl) {
            alertEl.classList.add('d-none');
            alertEl.textContent = '';
        }

        const formData = new FormData(editForm);

        fetch('api/visitor_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origBtnText;
            }

            if (data.success && data.visitor) {
                const modalEl = document.getElementById('editVisitorModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }

                Toast.fire({
                    icon: 'success',
                    title: data.message || 'Visitor record updated successfully'
                });

                // Update all matching rows across tables
                const rows = document.querySelectorAll(`tr[data-visitor-id="${data.visitor.id}"]`);
                if (rows.length > 0) {
                    rows.forEach(row => {
                        // In visitor_history.php table:
                        // td: 1=Badge, 2=Status, 3=Name&Company, 4=Contact, 5=Purpose, 6=Host, 7=TimeIn, 8=TimeOut, 9=Duration, 10=Actions
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 10) {
                            // Status cell
                            if (data.visitor.status === 'Inside') {
                                cells[1].innerHTML = `<span class="badge text-bg-success"><span class="live-dot" style="width:6px; height:6px;"></span> Inside</span>`;
                            } else {
                                cells[1].innerHTML = `<span class="badge text-bg-secondary">Completed</span>`;
                            }
                            // Name & Company
                            cells[2].innerHTML = `
                                <div class="fw-bold text-body">${data.visitor.full_name}</div>
                                <small class="text-muted">${data.visitor.company_or_address || 'Individual / Private'}</small>
                            `;
                            // Contact
                            cells[3].innerHTML = `<span class="small fw-semibold text-body">${data.visitor.contact_number}</span>`;
                            // Purpose
                            cells[4].innerHTML = `<span class="badge text-bg-info text-white">${data.visitor.purpose_of_visit}</span>`;
                            // Host
                            cells[5].innerHTML = `
                                <div class="fw-semibold text-body">${data.visitor.person_to_visit}</div>
                                <small class="text-muted">${data.visitor.department_to_visit || 'Front Desk'}</small>
                            `;
                            // Time In
                            cells[6].innerHTML = `
                                <span class="text-success fw-bold font-monospace">${data.visitor.time_in_time || ''}</span>
                                <div class="small text-muted">${data.visitor.time_in_date || ''}</div>
                            `;
                            // Time Out
                            if (data.visitor.time_out) {
                                cells[7].innerHTML = `
                                    <span class="font-monospace fw-bold text-secondary">${data.visitor.time_out_time || ''}</span>
                                    <div class="small text-muted">${data.visitor.time_out_date || ''}</div>
                                `;
                            } else {
                                cells[7].innerHTML = `<span class="badge text-bg-success">Active</span>`;
                            }
                            // Duration
                            cells[8].innerHTML = `<span class="badge text-bg-light border">${data.visitor.duration || '—'}</span>`;

                            // Actions buttons update based on status
                            const btnGroup = cells[9].querySelector('.btn-group');
                            if (btnGroup) {
                                if (data.visitor.status === 'Completed') {
                                    btnGroup.querySelectorAll('button, a').forEach(btn => {
                                        const txt = (btn.textContent || '').trim();
                                        if (txt === 'Time-In' || txt === 'Time-Out' || btn.title === 'Time-In' || btn.title === 'Time-Out') {
                                            btn.remove();
                                        }
                                    });
                                } else if (data.visitor.status === 'Inside') {
                                    const hasTimeIn = Array.from(btnGroup.querySelectorAll('button, a')).some(b => (b.textContent || '').trim() === 'Time-In');
                                    if (!hasTimeIn) {
                                        const timeInBtn = document.createElement('button');
                                        timeInBtn.type = 'button';
                                        timeInBtn.className = 'btn btn-success';
                                        timeInBtn.title = 'Time-In';
                                        timeInBtn.textContent = 'Time-In';
                                        timeInBtn.onclick = () => triggerDynamicTimeIn(data.visitor.id, data.visitor.full_name, data.visitor.badge_number);
                                        btnGroup.appendChild(timeInBtn);

                                        const timeOutBtn = document.createElement('button');
                                        timeOutBtn.type = 'button';
                                        timeOutBtn.className = 'btn btn-danger';
                                        timeOutBtn.title = 'Time-Out';
                                        timeOutBtn.textContent = 'Time-Out';
                                        timeOutBtn.onclick = () => triggerDynamicTimeOut(data.visitor.id, data.visitor.full_name, data.visitor.badge_number);
                                        btnGroup.appendChild(timeOutBtn);
                                    }
                                }
                            }
                        } else if (cells.length >= 8) {
                            // Active visitors table
                            const nameEl = row.querySelector('.fw-bold.text-body');
                            if (nameEl) nameEl.textContent = data.visitor.full_name;
                        }

                        row.classList.add('table-success');
                        setTimeout(() => row.classList.remove('table-success'), 1200);
                    });
                } else {
                    // Reload if not found in current DOM view
                    setTimeout(() => window.location.reload(), 800);
                }

                if (typeof pollServerStats === 'function') {
                    pollServerStats();
                }
            } else {
                if (alertEl) {
                    alertEl.textContent = data.message || 'Failed to update visitor record.';
                    alertEl.classList.remove('d-none');
                } else {
                    Toast.fire({ icon: 'error', title: data.message || 'Update failed' });
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origBtnText;
            }
            if (alertEl) {
                alertEl.textContent = 'A network error occurred. Please try again.';
                alertEl.classList.remove('d-none');
            }
        });
    });
}
