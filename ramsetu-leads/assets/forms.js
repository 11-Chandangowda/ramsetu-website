/* RamSetu Leads – form submission (progressive enhancement over a normal POST). */
(function () {
  'use strict';
  if (!window.fetch || !window.FormData) return;


  function formatINR(v) {
    // Max 10 digits = ₹99,99,99,999 (just under ₹100 crore).
    var d = String(v).replace(/\D/g, '').replace(/^0+/, '').slice(0, 10);
    if (!d) return '';
    var last3 = d.slice(-3), rest = d.slice(0, -3);
    return (rest ? rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' : '') + last3;
  }

  // Indian mobile: keep digits only, drop a pasted +91 / 0 prefix, stop at 10 digits.
  function cleanPhone(v) {
    var d = String(v).replace(/\D/g, '');
    if (d.length > 10 && d.indexOf('91') === 0) d = d.slice(2);
    else if (d.length > 10 && d.charAt(0) === '0') d = d.slice(1);
    return d.slice(0, 10);
  }

  function setError(form, name, msg) {
    var wrap = form.querySelector('[data-field="' + name + '"]');
    if (!wrap) return false;
    var p = wrap.querySelector('.rsl-error');
    var input = wrap.querySelector('input,select,textarea');
    wrap.classList.toggle('has-error', !!msg);
    if (p) { p.textContent = msg || ''; p.hidden = !msg; }
    if (input) {
      if (msg) {
        input.setAttribute('aria-invalid', 'true');
        if (p) { p.id = p.id || input.id + '-err'; input.setAttribute('aria-describedby', p.id); }
      } else {
        input.removeAttribute('aria-invalid');
      }
    }
    return true;
  }

  // Fields that only apply for some answers, e.g. monthly income only when "Salaried".
  function applyConditions(form) {
    form.querySelectorAll('[data-show-if]').forEach(function (wrap) {
      var rule = wrap.getAttribute('data-show-if').split('=');
      var src = form.querySelector('[name="' + rule[0] + '"]');
      var show = !!src && src.value === rule[1];
      wrap.hidden = !show;
      wrap.querySelectorAll('input,select,textarea').forEach(function (el) {
        el.disabled = !show;
        if (!show) { el.value = ''; }
      });
    });
  }

  // Aadhaar: 12 digits, not starting with 0/1, Verhoeff check digit.
  var VD = [[0,1,2,3,4,5,6,7,8,9],[1,2,3,4,0,6,7,8,9,5],[2,3,4,0,1,7,8,9,5,6],[3,4,0,1,2,8,9,5,6,7],[4,0,1,2,3,9,5,6,7,8],[5,9,8,7,6,0,4,3,2,1],[6,5,9,8,7,1,0,4,3,2],[7,6,5,9,8,2,1,0,4,3],[8,7,6,5,9,3,2,1,0,4],[9,8,7,6,5,4,3,2,1,0]];
  var VP = [[0,1,2,3,4,5,6,7,8,9],[1,5,7,6,2,8,3,0,9,4],[5,8,0,3,7,9,6,1,4,2],[8,9,1,6,0,4,3,5,2,7],[9,4,5,3,1,2,6,8,7,0],[4,2,8,6,5,7,3,9,0,1],[2,7,9,3,8,0,6,4,1,5],[7,0,4,6,9,1,3,2,5,8]];
  function aadhaarOk(v) {
    if (!/^[2-9]\d{11}$/.test(v)) return false;
    var c = 0, r = v.split('').reverse();
    for (var i = 0; i < r.length; i++) c = VD[c][VP[i % 8][+r[i]]];
    return c === 0;
  }

  function clientCheck(form) {
    var errors = {};
    form.querySelectorAll('[required]:not(:disabled)').forEach(function (el) {
      var name = el.name;
      var empty = el.type === 'checkbox' ? !el.checked : !String(el.value).trim();
      if (empty) errors[name] = el.type === 'checkbox' ? 'Please accept to continue.' : 'This field is required.';
    });
    form.querySelectorAll('input[type="tel"]').forEach(function (el) {
      if (el.value.trim() && !/^[6-9]\d{9}$/.test(el.value.trim())) errors[el.name] = 'Enter a valid 10-digit mobile number starting with 6, 7, 8 or 9.';
    });
    form.querySelectorAll('input[type="email"]').forEach(function (el) {
      if (el.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim())) errors[el.name] = 'This email address does not look right.';
    });
    form.querySelectorAll('[data-rsl-digits]').forEach(function (el) {
      var n = parseInt(el.getAttribute('data-rsl-digits'), 10);
      if (el.value && el.name === 'pincode' && !/^[1-9]\d{5}$/.test(el.value)) errors[el.name] = 'Enter a valid ' + n + '-digit pincode.';
    });
    form.querySelectorAll('[data-rsl-pan]').forEach(function (el) {
      if (el.value && !/^[A-Z]{5}\d{4}[A-Z]$/.test(el.value)) errors[el.name] = 'PAN should look like ABCDE1234F.';
    });
    form.querySelectorAll('[data-rsl-aadhaar]').forEach(function (el) {
      if (el.value && !aadhaarOk(el.value)) errors[el.name] = 'Please enter a valid 12-digit Aadhaar number.';
    });
    return errors;
  }

  function showErrors(form, errors) {
    form.querySelectorAll('[data-field]').forEach(function (w) { setError(form, w.getAttribute('data-field'), ''); });
    var first = null, general = [];
    Object.keys(errors).forEach(function (k) {
      if (!setError(form, k, errors[k])) general.push(errors[k]);
      else if (!first) first = form.querySelector('[data-field="' + k + '"] input,[data-field="' + k + '"] select,[data-field="' + k + '"] textarea');
    });
    var alert = form.querySelector('.rsl-alert');
    if (alert) {
      alert.className = 'rsl-alert rsl-alert--error';
      alert.textContent = general.length ? general.join(' ') : RSL.i18n.fix;
      alert.hidden = false;
    }
    if (first) first.focus();
  }

  function init(form, ajax) {
    var url = form.querySelector('[name="page_url"]');
    if (url) url.value = window.location.href.split('#')[0];

    form.querySelectorAll('[data-rsl-money]').forEach(function (el) {
      el.addEventListener('input', function () {
        var end = el.value.length - el.selectionEnd;
        el.value = formatINR(el.value);
        var pos = Math.max(0, el.value.length - end);
        try { el.setSelectionRange(pos, pos); } catch (e) {}
      });
    });

    form.querySelectorAll('[data-rsl-digits]').forEach(function (el) {
      var n = parseInt(el.getAttribute('data-rsl-digits'), 10);
      el.addEventListener('input', function () {
        var d = el.value.replace(/\D/g, '').slice(0, n);
        if (d !== el.value) el.value = d;
      });
    });
    form.querySelectorAll('[data-rsl-pan]').forEach(function (el) {
      el.addEventListener('input', function () {
        var v = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);
        if (v !== el.value) el.value = v;
      });
    });
    applyConditions(form);
    form.addEventListener('change', function (e) {
      if (e.target.name && form.querySelector('[data-show-if^="' + e.target.name + '="]')) applyConditions(form);
    });

    form.querySelectorAll('input[type="tel"]').forEach(function (el) {
      el.addEventListener('input', function () {
        var clean = cleanPhone(el.value);
        if (clean !== el.value) el.value = clean;
      });
    });

    form.addEventListener('input', function (e) {
      var w = e.target.closest('[data-field]');
      if (w && w.classList.contains('has-error')) setError(form, w.getAttribute('data-field'), '');
    });

    if (!ajax) {
      // Portal forms submit normally; only check required fields first.
      form.addEventListener('submit', function (e) {
        var errors = clientCheck(form);
        if (Object.keys(errors).length) { e.preventDefault(); showErrors(form, errors); }
      });
      return;
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var errors = clientCheck(form);
      if (Object.keys(errors).length) { showErrors(form, errors); return; }

      var btn = form.querySelector('.rsl-submit');
      var label = btn.innerHTML;
      btn.disabled = true;
      btn.textContent = RSL.i18n.sending;
      form.classList.add('is-busy');

      fetch(RSL.ajaxUrl, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
        .then(function (r) { return r.json().catch(function () { return { success: false, data: {} }; }); })
        .then(function (res) {
          if (res && res.success) {
            var alert = form.querySelector('.rsl-alert');
            alert.className = 'rsl-alert rsl-alert--success';
            alert.textContent = '';
            if (res.data.title) {
              var t = document.createElement('strong');
              t.className = 'rsl-alert__title';
              t.textContent = res.data.title;
              alert.appendChild(t);
            }
            var m = document.createElement('span');
            m.textContent = res.data.message;
            alert.appendChild(m);
            alert.hidden = false;
            form.classList.add('is-done');
            form.reset();
            applyConditions(form);
            form.dispatchEvent(new CustomEvent('rsl:success', { bubbles: true, detail: { type: form.querySelector('[name="rsl_type"]').value } }));
            alert.setAttribute('tabindex', '-1');
            alert.focus();
          } else {
            showErrors(form, (res && res.data && res.data.errors) || { _form: RSL.i18n.error });
          }
        })
        .catch(function () { showErrors(form, { _form: RSL.i18n.error }); })
        .then(function () {
          btn.disabled = false;
          btn.innerHTML = label;
          form.classList.remove('is-busy');
        });
    });
  }

  function boot() {
    document.querySelectorAll('[data-rsl-form]').forEach(function (f) { init(f, true); });
    document.querySelectorAll('[data-rsl-enhance]').forEach(function (f) { init(f, false); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();

  // Let the theme reset a form (e.g. when the Apply Now modal reopens).
  window.RSLResetForm = function (form) {
    form.classList.remove('is-done');
    var a = form.querySelector('.rsl-alert');
    if (a) { a.hidden = true; a.textContent = ''; }
    form.querySelectorAll('[data-field]').forEach(function (w) { setError(form, w.getAttribute('data-field'), ''); });
  };
})();
