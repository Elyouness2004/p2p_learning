/**
 * js/admin.js
 * Chart initialisation and user-table filter for pages/admin/admin.php.
 * Data is injected by the page via window.P2P_ADMIN before this script loads.
 */

document.addEventListener('DOMContentLoaded', () => {
  const { chartLabels, chartData, sessChart } = window.P2P_ADMIN ?? {};

  // ── Activity bar chart ──────────────────────────────────────────
  const actCtx = document.getElementById('activityChart');
  if (actCtx && chartLabels && chartData) {
    new Chart(actCtx, {
      type: 'bar',
      data: {
        labels: chartLabels,
        datasets: [{
          label: 'Messages',
          data: chartData,
          backgroundColor: 'rgba(79,70,229,.7)',
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // ── Session doughnut chart ──────────────────────────────────────
  const sessCtx = document.getElementById('sessionsChart');
  if (sessCtx && sessChart) {
    new Chart(sessCtx, {
      type: 'doughnut',
      data: {
        labels: ['En attente', 'Confirmées', 'Terminées'],
        datasets: [{
          data: sessChart,
          backgroundColor: ['#fbbf24', '#34d399', '#818cf8'],
          borderWidth: 0,
          hoverOffset: 6,
        }]
      },
      options: {
        cutout: '65%',
        plugins: {
          legend: { position: 'bottom', labels: { padding: 16 } }
        }
      }
    });
  }

  // ── User table live filter ──────────────────────────────────────
  const searchInput = document.getElementById('userSearch');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('#userTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }
});
