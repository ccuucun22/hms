// CarePoint HMS - shared front-end behaviour

document.addEventListener('DOMContentLoaded', function () {
  const toggleBtn = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');

  if (toggleBtn && sidebar && backdrop) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      backdrop.classList.toggle('show');
    });
    backdrop.addEventListener('click', function () {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }

  // Auto-hide alerts after 4 seconds
  document.querySelectorAll('.alert-auto-hide').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity 0.4s ease';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 4000);
  });

  // Toggle "Operation Required" section on admission form
  const opCheckbox = document.getElementById('operation_required');
  const opSection = document.getElementById('operation_section');
  if (opCheckbox && opSection) {
    const sync = function () { opSection.style.display = opCheckbox.checked ? 'block' : 'none'; };
    opCheckbox.addEventListener('change', sync);
    sync();
  }

  // Live bill preview on discharge page
  const rateInput = document.getElementById('per_day_rate_val');
  const daysInput = document.getElementById('days_val');
  const roomChargeOut = document.getElementById('room_charge_preview');
  if (rateInput && daysInput && roomChargeOut) {
    const rate = parseFloat(rateInput.value || 0);
    const days = parseFloat(daysInput.value || 0);
    roomChargeOut.textContent = (rate * days).toFixed(2);
  }
});
