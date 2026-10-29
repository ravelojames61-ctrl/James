/**
 * AEGIS VLMS - Core UI & Interaction Controller
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Live Digital Clock
  const clockElement = document.getElementById('liveClock');
  if (clockElement) {
    function updateClock() {
      const now = new Date();
      const options = {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      };
      clockElement.textContent = now.toLocaleDateString('en-US', options);
    }
    updateClock();
    setInterval(updateClock, 1000);
  }

  // 2. Theme Toggle (Dark / Light)
  const themeToggle = document.getElementById('themeToggle');
  const currentTheme = localStorage.getItem('vlms_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', currentTheme);

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const activeTheme = document.documentElement.getAttribute('data-theme');
      const newTheme = activeTheme === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('vlms_theme', newTheme);
    });
  }

  // 3. Mobile Sidebar Toggle
  const sidebar = document.querySelector('.app-sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });

    // Close when clicking outside on mobile
    document.addEventListener('click', (e) => {
      if (window.innerWidth <= 992 && 
          sidebar.classList.contains('open') && 
          !sidebar.contains(e.target) && 
          !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // 4. Auto-dismiss alerts after 5 seconds
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  // 5. Generic Modal Handlers
  window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('active');
    }
  };

  window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('active');
    }
  };

  // Close modals when clicking overlay background or pressing ESC
  document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.classList.remove('active');
      }
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
  });

  // 6. Fast Client-side Table Filter
  const tableSearchInput = document.getElementById('tableSearchInput');
  if (tableSearchInput) {
    tableSearchInput.addEventListener('keyup', function() {
      const filter = this.value.toLowerCase();
      const targetTable = document.querySelector('.data-table tbody');
      if (!targetTable) return;

      const rows = targetTable.querySelectorAll('tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
      });
    });
  }
});

/**
 * Open Visitor Time-Out Checkout Confirmation Modal
 */
function confirmTimeOut(visitorId, visitorName, badgeNumber) {
  const modal = document.getElementById('timeOutModal');
  if (!modal) return;

  const idInput = document.getElementById('modalVisitorId');
  const nameSpan = document.getElementById('modalVisitorName');
  const badgeSpan = document.getElementById('modalBadgeNumber');

  if (idInput) idInput.value = visitorId;
  if (nameSpan) nameSpan.textContent = visitorName;
  if (badgeSpan) badgeSpan.textContent = badgeNumber;

  modal.classList.add('active');
}

/**
 * Open Visitor Time-In Arrival Confirmation Modal
 */
function confirmTimeIn(visitorId, visitorName, badgeNumber) {
  const modal = document.getElementById('timeInModal');
  if (!modal) return;

  const idInput = document.getElementById('modalTimeInVisitorId');
  const nameSpan = document.getElementById('modalTimeInVisitorName');
  const badgeSpan = document.getElementById('modalTimeInBadgeNumber');

  if (idInput) idInput.value = visitorId;
  if (nameSpan) nameSpan.textContent = visitorName;
  if (badgeSpan) badgeSpan.textContent = badgeNumber;

  if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
  } else {
    modal.classList.add('active');
  }
}

/**
 * Open Quick Visitor Detail View Modal
 */
function viewVisitorDetails(data) {
  const modal = document.getElementById('visitorDetailModal');
  if (!modal) return;

  document.getElementById('detBadge').textContent = data.badge_number || '—';
  document.getElementById('detName').textContent = data.full_name || '—';
  document.getElementById('detContact').textContent = data.contact_number || '—';
  document.getElementById('detEmail').textContent = data.email || '—';
  document.getElementById('detCompany').textContent = data.company_or_address || '—';
  document.getElementById('detId').textContent = (data.id_type || '') + (data.id_number ? ' (' + data.id_number + ')' : '—');
  document.getElementById('detPurpose').textContent = data.purpose_of_visit || '—';
  document.getElementById('detHost').textContent = data.person_to_visit || '—';
  document.getElementById('detDept').textContent = data.department_to_visit || '—';
  document.getElementById('detTimeIn').textContent = data.formatted_time_in || '—';
  document.getElementById('detTimeOut').textContent = data.formatted_time_out || 'Active (Still Inside)';
  document.getElementById('detDuration').textContent = data.duration || '—';
  document.getElementById('detRemarks').textContent = data.remarks || 'None';

  // Set pass print link
  const printPassBtn = document.getElementById('detPrintPassBtn');
  if (printPassBtn) {
    printPassBtn.href = 'visitor_pass.php?id=' + encodeURIComponent(data.id);
  }

  modal.classList.add('active');
}
