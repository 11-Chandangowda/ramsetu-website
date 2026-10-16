(function () {
  'use strict';
  document.addEventListener('click', function (e) {
    var del = e.target.closest('[data-confirm]');
    if (del && !window.confirm(del.getAttribute('data-confirm'))) e.preventDefault();

    if (e.target.closest('.rsl-branch__remove')) {
      e.target.closest('.rsl-branch').remove();
    }
  });

  // Confirm bulk delete and flagged action forms.
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.hasAttribute('data-confirm-form') && !window.confirm(f.getAttribute('data-confirm-form'))) { e.preventDefault(); return; }
    var a = f.querySelector('select[name="action"]'), a2 = f.querySelector('select[name="action2"]');
    var v = (a && a.value !== '-1' ? a.value : (a2 && a2.value));
    if (v === 'delete' && !window.confirm('Delete the selected records permanently?')) e.preventDefault();
  });

  var add = document.getElementById('rsl-add-branch');
  if (add) {
    var n = 1000;
    add.addEventListener('click', function () {
      var tpl = document.querySelector('.rsl-branch[data-template]');
      var html = tpl.outerHTML.replace(/__i__/g, 'n' + (n++));
      tpl.insertAdjacentHTML('beforebegin', html);
      var row = tpl.previousElementSibling;
      row.removeAttribute('data-template'); row.hidden = false; row.disabled = false;
      var first = row.querySelector('select,input'); if (first) first.focus();
    });
  }
})();
