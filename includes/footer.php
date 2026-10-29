<?php
/**
 * Global Footer & Modals Component — AdminLTE 4.10.1 Design
 */
?>
</main>

<!--begin::Footer-->
<footer class="app-footer">
    <div class="float-end d-none d-sm-inline">
        <b>VLMS</b> v1.0 • Built with AdminLTE 4.10.1
    </div>
    <strong>Copyright &copy; 2026 <a href="dashboard.php" class="text-decoration-none">Aegis Visitor Log Management System</a>.</strong> All rights reserved.
</footer>
<!--end::Footer-->
</div><!-- /.app-wrapper -->

<!-- ==========================================
     Time-Out Departure Confirmation Modal (Bootstrap 5)
     ========================================== -->
<div class="modal fade" id="timeOutModal" tabindex="-1" aria-labelledby="timeOutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="record_timeout">
            <input type="hidden" name="visitor_id" id="modalVisitorId" value="">
            
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="timeOutModalLabel">
                    <i class="bi bi-clock-history me-2"></i> Record Visitor Departure
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <p class="mb-3">
                    Confirm departure for <strong id="modalVisitorName">Visitor</strong> 
                    (<span id="modalBadgeNumber" class="badge text-bg-dark font-monospace">VIS-XXXX</span>)?
                </p>
                <div class="alert alert-secondary d-flex align-items-center justify-content-between p-2 mb-3">
                    <span class="small text-muted">Departure Timestamp</span>
                    <span class="font-monospace fw-bold text-danger"><?= date('M d, Y h:i A') ?></span>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="departureRemarks">Exit Notes / Remarks (Optional)</label>
                    <textarea name="remarks" id="departureRemarks" class="form-control" rows="2" placeholder="e.g. Pass returned, escorted out via Main Entrance"></textarea>
                </div>
            </div>
            
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-check2-circle me-1"></i> Confirm Time-Out
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     Time-In Arrival Confirmation Modal (Bootstrap 5)
     ========================================== -->
<div class="modal fade" id="timeInModal" tabindex="-1" aria-labelledby="timeInModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="record_timein">
            <input type="hidden" name="visitor_id" id="modalTimeInVisitorId" value="">
            
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="timeInModalLabel">
                    <i class="bi bi-clock-history me-2"></i> Record Visitor Time-In
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <p class="mb-3">
                    Confirm arrival / time-in for <strong id="modalTimeInVisitorName">Visitor</strong> 
                    (<span id="modalTimeInBadgeNumber" class="badge text-bg-dark font-monospace">VIS-XXXX</span>)?
                </p>
                <div class="alert alert-secondary d-flex align-items-center justify-content-between p-2 mb-3">
                    <span class="small text-muted">Arrival Timestamp</span>
                    <span class="font-monospace fw-bold text-success"><?= date('M d, Y h:i A') ?></span>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="modalTimeInRemarks">Arrival Notes / Remarks (Optional)</label>
                    <textarea name="remarks" id="modalTimeInRemarks" class="form-control" rows="2" placeholder="e.g. Return visit, Re-entry"></textarea>
                </div>
            </div>
            
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check2-circle me-1"></i> Confirm Time-In
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     Visitor Detail Quick View Modal (Bootstrap 5)
     ========================================== -->
<div class="modal fade" id="visitorDetailModal" tabindex="-1" aria-labelledby="visitorDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="visitorDetailModalLabel">
                    <i class="bi bi-person-badge-fill me-2"></i> Visitor Profile & Visit Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <div>
                        <h4 id="detName" class="mb-1 fw-bold text-primary">—</h4>
                        <span id="detCompany" class="text-secondary small">—</span>
                    </div>
                    <span id="detBadge" class="badge text-bg-primary fs-6 font-monospace px-3 py-2">—</span>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Contact Number</label>
                        <span id="detContact" class="fw-semibold text-body">—</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Email Address</label>
                        <span id="detEmail" class="fw-semibold text-body">—</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Identification</label>
                        <span id="detId" class="fw-semibold text-body">—</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Purpose of Visit</label>
                        <span id="detPurpose" class="badge text-bg-info text-white">—</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Person to Visit (Host)</label>
                        <span id="detHost" class="fw-semibold text-body">—</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Department / Floor</label>
                        <span id="detDept" class="fw-semibold text-body">—</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Time-In (Arrival)</label>
                        <span id="detTimeIn" class="font-monospace text-success fw-bold">—</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Time-Out (Departure)</label>
                        <span id="detTimeOut" class="font-monospace fw-bold">—</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Duration Inside</label>
                        <span id="detDuration" class="fw-bold">—</span>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small text-uppercase fw-semibold d-block">Exit Remarks</label>
                        <span id="detRemarks" class="text-body small">—</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-body-tertiary">
                <a href="#" id="detPrintPassBtn" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-1"></i> Print Pass
                </a>
                <button type="button" class="btn btn-outline-warning" id="detEditBtn" onclick="openEditFromDetailModal()">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     Edit Visitor Record Modal (Bootstrap 5)
     ========================================== -->
<div class="modal fade" id="editVisitorModal" tabindex="-1" aria-labelledby="editVisitorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editVisitorForm" method="POST" action="visitor_history.php" class="modal-content shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_visitor">
            <input type="hidden" name="visitor_id" id="editVisitorId" value="">

            <div class="modal-header bg-warning-subtle border-bottom border-warning">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square text-warning fs-4"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold text-dark" id="editVisitorModalLabel">Edit Visitor Record</h5>
                        <small class="text-muted">Badge: <span id="editBadgeDisplay" class="font-monospace fw-bold text-primary">—</span></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div id="editVisitorAlert" class="alert alert-danger d-none" role="alert"></div>

                <div class="row g-3">
                    <!-- Personal Info -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="editFullName">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="editFullName" class="form-control" placeholder="Visitor full name" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="editContactNumber">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" id="editContactNumber" class="form-control" placeholder="e.g. 90789678676" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="editEmail">Email Address</label>
                        <input type="email" name="email" id="editEmail" class="form-control" placeholder="visitor@example.com">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="editCompany">Company / Address</label>
                        <input type="text" name="company_or_address" id="editCompany" class="form-control" placeholder="Company or organization">
                    </div>

                    <!-- ID Credentials -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="editIdType">ID Type Presented</label>
                        <select name="id_type" id="editIdType" class="form-select">
                            <option value="">-- None / Not Provided --</option>
                            <?php foreach (getIdTypeOptions() as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="editIdNumber">ID Number / Reference</label>
                        <input type="text" name="id_number" id="editIdNumber" class="form-control" placeholder="e.g. DL-987213">
                    </div>

                    <!-- Visit Purpose & Host -->
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="editPurpose">Purpose of Visit <span class="text-danger">*</span></label>
                        <select name="purpose_of_visit" id="editPurpose" class="form-select" required>
                            <option value="">-- Select Purpose --</option>
                            <?php foreach (getPurposeOptions() as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="editPersonToVisit">Person to Visit (Host) <span class="text-danger">*</span></label>
                        <input type="text" name="person_to_visit" id="editPersonToVisit" class="form-control" placeholder="Host name" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="editDeptToVisit">Department / Floor</label>
                        <select name="department_to_visit" id="editDeptToVisit" class="form-select">
                            <option value="">-- Select Department --</option>
                            <?php foreach (getDepartmentOptions() as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status & Timestamps -->
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="editStatus">Visit Status <span class="text-danger">*</span></label>
                        <select name="status" id="editStatus" class="form-select" onchange="toggleEditTimeOutField(this.value)">
                            <option value="Inside">Inside</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="editTimeIn">Time-In (Arrival) <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="time_in" id="editTimeIn" class="form-control" required>
                    </div>

                    <div class="col-md-4" id="editTimeOutContainer">
                        <label class="form-label small fw-semibold" for="editTimeOut">Time-Out (Departure)</label>
                        <input type="datetime-local" name="time_out" id="editTimeOut" class="form-control">
                    </div>

                    <!-- Remarks -->
                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="editRemarks">Remarks / Notes</label>
                        <textarea name="remarks" id="editRemarks" class="form-control" rows="2" placeholder="Exit notes or special instructions"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-body-tertiary">
                <a href="#" id="editOpenFullPageBtn" class="btn btn-outline-secondary me-auto btn-sm" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Full Page Edit
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveEditBtn">
                    <i class="bi bi-check2-circle me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!--begin::Third Party Plugin(OverlayScrollbars)-->
<script
  src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.16.0/browser/overlayscrollbars.browser.es6.min.js"
  crossorigin="anonymous"
></script>
<!--end::Third Party Plugin(OverlayScrollbars)-->

<!--begin::Required Plugin(popperjs for Bootstrap 5)-->
<script
  src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
  crossorigin="anonymous"
></script>
<!--end::Required Plugin(popperjs for Bootstrap 5)-->

<!--begin::Required Plugin(Bootstrap 5)-->
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"
  crossorigin="anonymous"
></script>
<!--end::Required Plugin(Bootstrap 5)-->

<!--begin::Required Plugin(AdminLTE 4.10.1)-->
<script src="assets/adminlte/js/adminlte.min.js"></script>
<!--end::Required Plugin(AdminLTE)-->

<!--begin::SweetAlert2 Plugin-->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!--end::SweetAlert2 Plugin-->

<!--begin::VLMS Dynamic Real-Time Engine-->
<script src="assets/js/dynamic.js?v=<?= time() ?>"></script>
<!--end::VLMS Dynamic Real-Time Engine-->

<!--begin::OverlayScrollbars Configure-->
<script>
  const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
  const Default = {
    scrollbarTheme: 'os-theme-light',
    scrollbarAutoHide: 'leave',
    scrollbarClickScroll: true,
  };
  document.addEventListener('DOMContentLoaded', function () {
    const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
    if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
      OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
        scrollbars: {
          theme: Default.scrollbarTheme,
          autoHide: Default.scrollbarAutoHide,
          clickScroll: Default.scrollbarClickScroll,
        },
      });
    }

    // Live clock
    const clock = document.getElementById('liveClock');
    if (clock) {
      function tick() {
        const d = new Date();
        clock.textContent = d.toLocaleDateString('en-US', {
          weekday: 'short', month: 'short', day: 'numeric',
          hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
      }
      tick();
      setInterval(tick, 1000);
    }

    // Color mode switch logic from AdminLTE 4
    const colorModeButtons = document.querySelectorAll('[data-bs-theme-value]');
    colorModeButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const theme = btn.getAttribute('data-bs-theme-value');
        localStorage.setItem('lte-theme', theme);
        const resolved = (theme === 'auto') 
          ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') 
          : theme;
        document.documentElement.setAttribute('data-bs-theme', resolved);
        
        colorModeButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });

    // Client-side instant filter for data tables
    const searchInput = document.getElementById('tableSearchInput');
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('.data-table tbody tr');
        rows.forEach(r => {
          r.style.display = r.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
      });
    }
  });

  // Modal Triggers
  function confirmTimeOut(id, name, badge) {
    document.getElementById('modalVisitorId').value = id;
    document.getElementById('modalVisitorName').textContent = name;
    document.getElementById('modalBadgeNumber').textContent = badge;
    const modal = new bootstrap.Modal(document.getElementById('timeOutModal'));
    modal.show();
  }

  function confirmTimeIn(id, name, badge) {
    const idInput = document.getElementById('modalTimeInVisitorId');
    const nameSpan = document.getElementById('modalTimeInVisitorName');
    const badgeSpan = document.getElementById('modalTimeInBadgeNumber');
    if (idInput) idInput.value = id;
    if (nameSpan) nameSpan.textContent = name;
    if (badgeSpan) badgeSpan.textContent = badge;
    const modalEl = document.getElementById('timeInModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      modal.show();
    } else {
      if (confirm(`Confirm Time-In for ${name} (${badge})?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        form.innerHTML = `
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="record_timein">
          <input type="hidden" name="visitor_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
      }
    }
  }

  // Guaranteed Global Handlers for Time-In and Time-Out
  function triggerDynamicTimeIn(visitorId, visitorName, badgeNumber) {
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
            body: formData,
            credentials: 'same-origin'
          })
          .then(res => res.json())
          .then(data => {
            if (!data.success) {
              throw new Error(data.message || 'Time-In recording failed');
            }
            return data;
          })
          .catch(err => {
            Swal.showValidationMessage(`Request failed: ${err.message}`);
          });
        },
        allowOutsideClick: () => !Swal.isLoading()
      }).then(result => {
        if (result.isConfirmed && result.value?.success) {
          const data = result.value;
          if (typeof Toast !== 'undefined' && Toast.fire) {
            Toast.fire({ icon: 'success', title: data.message });
          } else {
            alert(data.message);
          }
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
              // visitor_history.php
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
          if (typeof pollServerStats === 'function') pollServerStats();
        }
      });
    } else {
      confirmTimeIn(visitorId, visitorName, badgeNumber);
    }
  }

  function triggerDynamicTimeOut(visitorId, visitorName, badgeNumber) {
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
            body: formData,
            credentials: 'same-origin'
          })
          .then(res => res.json())
          .then(data => {
            if (!data.success) {
              throw new Error(data.message || 'Departure recording failed');
            }
            return data;
          })
          .catch(err => {
            Swal.showValidationMessage(`Request failed: ${err.message}`);
          });
        },
        allowOutsideClick: () => !Swal.isLoading()
      }).then(result => {
        if (result.isConfirmed && result.value?.success) {
          const data = result.value;
          const row = document.querySelector(`tr[data-visitor-id="${visitorId}"]`);
          const isHistoryPage = window.location.pathname.includes('visitor_history.php');
          const isFilteringInsideOnly = window.location.search.includes('status=Inside');

          if (row && isHistoryPage && !isFilteringInsideOnly) {
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
            row.style.transition = 'all 0.4s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(20px)';
            setTimeout(() => row.remove(), 400);
          }

          if (typeof Toast !== 'undefined' && Toast.fire) {
            Toast.fire({ icon: 'success', title: data.message });
          } else {
            alert(data.message);
          }
          if (typeof pollServerStats === 'function') pollServerStats();
        }
      });
    } else {
      confirmTimeOut(visitorId, visitorName, badgeNumber);
    }
  }

  let currentDetailVisitorId = null;

  function viewVisitorDetails(d) {
    currentDetailVisitorId = d.id;
    document.getElementById('detName').textContent = d.full_name || '—';
    document.getElementById('detCompany').textContent = d.company_or_address || '—';
    document.getElementById('detBadge').textContent = d.badge_number || '—';
    document.getElementById('detContact').textContent = d.contact_number || '—';
    document.getElementById('detEmail').textContent = d.email || '—';
    document.getElementById('detId').textContent = (d.id_type || '') + (d.id_number ? ' (' + d.id_number + ')' : '—');
    document.getElementById('detPurpose').textContent = d.purpose_of_visit || '—';
    document.getElementById('detHost').textContent = d.person_to_visit || '—';
    document.getElementById('detDept').textContent = d.department_to_visit || '—';
    document.getElementById('detTimeIn').textContent = d.formatted_time_in || '—';
    document.getElementById('detTimeOut').textContent = d.formatted_time_out || 'Active (Inside)';
    document.getElementById('detDuration').textContent = d.duration || '—';
    document.getElementById('detRemarks').textContent = d.remarks || 'None';
    document.getElementById('detPrintPassBtn').href = 'visitor_pass.php?id=' + encodeURIComponent(d.id);
    const modal = new bootstrap.Modal(document.getElementById('visitorDetailModal'));
    modal.show();
  }

  function openEditFromDetailModal() {
    const detailModalEl = document.getElementById('visitorDetailModal');
    const detailModal = bootstrap.Modal.getInstance(detailModalEl);
    if (detailModal) detailModal.hide();
    if (currentDetailVisitorId) {
      setTimeout(() => openEditVisitorModal(currentDetailVisitorId), 300);
    }
  }

  function toggleEditTimeOutField(status) {
    const timeOutContainer = document.getElementById('editTimeOutContainer');
    const timeOutInput = document.getElementById('editTimeOut');
    if (!timeOutContainer || !timeOutInput) return;
    if (status === 'Completed') {
      timeOutContainer.style.opacity = '1';
      timeOutInput.disabled = false;
      if (!timeOutInput.value) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        timeOutInput.value = now.toISOString().slice(0, 16);
      }
    } else {
      timeOutInput.disabled = true;
      timeOutInput.value = '';
      timeOutContainer.style.opacity = '0.5';
    }
  }
</script>
</body>
</html>
