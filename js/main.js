/**
 * js/main.js — P2P Learning global JavaScript
 * Handles: sidebar toggle, Bootstrap tooltips, flash dismiss, form helpers
 */

document.addEventListener('DOMContentLoaded', () => {

  // ── Sidebar toggle ──────────────────────────────────────────────
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebar-overlay');
  const toggle   = document.getElementById('sidebar-toggle');

  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
  }

  toggle?.addEventListener('click', () => {
    sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  overlay?.addEventListener('click', closeSidebar);

  // Close sidebar on resize to desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 992) closeSidebar();
  });

  // ── Bootstrap tooltips ──────────────────────────────────────────
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover' });
  });

  // ── Flash / alert auto-dismiss ──────────────────────────────────
  document.querySelectorAll('.alert.auto-dismiss').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity .5s ease';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 4000);
  });

  // ── Textarea auto-resize ────────────────────────────────────────
  document.querySelectorAll('textarea[data-autoresize]').forEach(ta => {
    const resize = () => {
      ta.style.height = 'auto';
      ta.style.height = Math.min(ta.scrollHeight, 160) + 'px';
    };
    ta.addEventListener('input', resize);
    resize();
  });

  // ── Module conflict detection (profile page) ────────────────────
  // Prevent the same module from being checked in both maitrise and lacune
  document.querySelectorAll('.maitrise-cb').forEach(cb => {
    cb.addEventListener('change', () => {
      if (cb.checked) {
        const lacune = document.getElementById('l' + cb.dataset.id);
        if (lacune?.checked) lacune.checked = false;
      }
    });
  });

  document.querySelectorAll('.lacune-cb').forEach(cb => {
    cb.addEventListener('change', () => {
      if (cb.checked) {
        const maitrise = document.getElementById('m' + cb.dataset.id);
        if (maitrise?.checked) maitrise.checked = false;
      }
    });
  });

  // ── Confirm dangerous actions ───────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', e => {
      if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
  });

  // ── Landing page: smooth scroll for anchor links ────────────────
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', e => {
      const target = document.querySelector(link.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ── Landing page: navbar background on scroll ───────────────────
  const landingNav = document.querySelector('.landing-nav');
  if (landingNav) {
    window.addEventListener('scroll', () => {
      landingNav.style.boxShadow = window.scrollY > 20
        ? '0 4px 24px rgba(0,0,0,.08)'
        : '';
    }, { passive: true });
  }

  // ── Number counter animation (landing page stats) ───────────────
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el   = entry.target;
        const end  = parseInt(el.dataset.count, 10);
        const dur  = 1200;
        const step = Math.ceil(end / (dur / 16));
        let cur    = 0;
        const tick = () => {
          cur = Math.min(cur + step, end);
          el.textContent = cur.toLocaleString('fr-FR');
          if (cur < end) requestAnimationFrame(tick);
        };
        tick();
        observer.unobserve(el);
      });
    }, { threshold: 0.5 });
    counters.forEach(c => observer.observe(c));
  }

});
