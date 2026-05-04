/**
 * js/profile.js
 * Module checkbox sync logic for pages/student/profile.php.
 * Prevents a module from being selected as both "maitrise" and "lacune".
 */

function syncModules(id, side) {
  const otherId    = (side === 'maitrise' ? 'l' : 'm') + id;
  const otherWrap  = document.getElementById((side === 'maitrise' ? 'lw' : 'mw') + '-' + id);
  const otherLabel = otherWrap?.querySelector('.mod-check');
  const selfCb     = document.getElementById((side === 'maitrise' ? 'm' : 'l') + id);
  const otherCb    = document.getElementById(otherId);

  if (selfCb.checked) {
    if (otherCb)    otherCb.checked = false;
    if (otherLabel) otherLabel.classList.add('disabled-look');
  } else {
    if (otherLabel) otherLabel.classList.remove('disabled-look');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.maitrise-cb').forEach(cb => {
    cb.addEventListener('change', () => syncModules(cb.dataset.id, 'maitrise'));
  });
  document.querySelectorAll('.lacune-cb').forEach(cb => {
    cb.addEventListener('change', () => syncModules(cb.dataset.id, 'lacune'));
  });
  // Initialise disabled state on page load for already-checked boxes
  document.querySelectorAll('.maitrise-cb:checked').forEach(cb => syncModules(cb.dataset.id, 'maitrise'));
  document.querySelectorAll('.lacune-cb:checked').forEach(cb => syncModules(cb.dataset.id, 'lacune'));
});
