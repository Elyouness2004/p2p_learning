function filtrer(type) {
  document.querySelectorAll('.match-card').forEach(card => {
    card.style.display =
      (type === 'tous' || card.dataset.type === type) ? '' : 'none';
  });
  ['tous', 'parfait', 'partiel'].forEach(t => {
    const btn = document.getElementById('btn-' + t);
    if (!btn) return;
    btn.classList.toggle('active',                t === type);
    btn.classList.toggle('btn-primary',           t === type);
    btn.classList.toggle('btn-outline-primary',   t !== type && t !== 'partiel');
    btn.classList.toggle('btn-outline-secondary', t === 'partiel' && type !== 'partiel');
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const select = document.getElementById('module-filter');
  if (select) {
    select.addEventListener('change', () => {
      const val = select.value;
      document.querySelectorAll('.match-card').forEach(card => {
        if (val === 'all') { card.style.display = ''; return; }
        const mods = card.dataset.modules || '';
        card.style.display = mods.includes(val) ? '' : 'none';
      });
    });
  }
});
