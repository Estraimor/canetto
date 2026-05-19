document.addEventListener('click', function(e) {
  var a = e.target.closest('a[href]');
  if (!a) return;
  var href = a.getAttribute('href');
  if (!href || href.startsWith('#') || href.startsWith('http') || href.startsWith('javascript') || a.target === '_blank') return;
  e.preventDefault();
  document.body.classList.add('page-exit');
  setTimeout(function() { window.location.href = href; }, 210);
});

// Cuando el browser restaura la página desde bfcache (botón atrás),
// eliminar page-exit para que el contenido no quede invisible.
window.addEventListener('pageshow', function(e) {
  if (e.persisted) {
    document.body.classList.remove('page-exit');
  }
});
