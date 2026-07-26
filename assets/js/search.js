/**
 * assets/js/search.js
 * Live search for admin dashboard.
 * Debounced fetch to api/search.php?q=... — renders results into the table.
 */
(function () {
  'use strict';

  // ── Config ──────────────────────────────────────────────────────
  const DEBOUNCE_MS = 300;
  const MIN_CHARS   = 0; // search even on empty → shows all

  // ── DOM Refs ─────────────────────────────────────────────────────
  const searchInput   = document.getElementById('rgx-search');
  const tableBody     = document.getElementById('rgx-table-body');
  const noResults     = document.getElementById('rgx-no-results');
  const resultCount   = document.getElementById('rgx-result-count');
  const spinner       = document.getElementById('rgx-search-spinner');

  if (!searchInput || !tableBody) return; // not on dashboard page

  // Read the API URL from the input's data attribute (injected by PHP)
  const apiUrl = searchInput.dataset.apiUrl || 'api/search.php';

  // ── Debounce utility ─────────────────────────────────────────────
  function debounce(fn, ms) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), ms);
    };
  }

  // ── Escape HTML to prevent XSS in rendered results ───────────────
  function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // ── Build the photo cell ──────────────────────────────────────────
  function photoCell(student) {
    if (student.photo && student.photo_mime) {
      return `<img src="data:${esc(student.photo_mime)};base64,${esc(student.photo)}"
                   alt="${esc(student.name)}"
                   class="rgx-photo-thumb">`;
    }
    return `<div class="rgx-photo-placeholder"><i class="bi bi-person"></i></div>`;
  }

  // ── Build the status badge ────────────────────────────────────────
  function statusBadge(status) {
    if (status === 'active') {
      return `<span class="rgx-status-active">Active</span>`;
    }
    return `<span class="rgx-status-inactive">Inactive</span>`;
  }

  // ── Render a single table row ─────────────────────────────────────
  function renderRow(student, editBase, deleteBase, viewBase) {
    return `<tr class="rgx-result-row">
      <td>${photoCell(student)}</td>
      <td><span class="rgx-roll-badge">${esc(student.roll_no)}</span></td>
      <td>
        <div class="fw-500">${esc(student.name)}</div>
        ${student.email ? `<div class="small text-muted">${esc(student.email)}</div>` : ''}
      </td>
      <td class="rgx-hide-sm">${esc(student.class)}</td>
      <td class="rgx-hide-sm">${esc(student.course)}</td>
      <td>${statusBadge(student.status)}</td>
      <td>
        <div class="d-flex gap-1">
          <a href="${esc(viewBase)}?id=${esc(student.id)}"
             class="btn btn-sm btn-outline-secondary"
             title="View">
            <i class="bi bi-eye"></i>
          </a>
          <a href="${esc(editBase)}?id=${esc(student.id)}"
             class="btn btn-sm btn-primary"
             title="Edit">
            <i class="bi bi-pencil"></i>
          </a>
          <form method="POST" action="${esc(deleteBase)}"
                class="d-inline"
                onsubmit="return confirmDelete(event, '${esc(student.name)}')">
            <input type="hidden" name="id" value="${esc(student.id)}">
            <input type="hidden" name="csrf_token" value="${esc(window.REGISTRIX_CSRF || '')}">
            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        </div>
      </td>
    </tr>`;
  }

  // ── Fetch and render results ──────────────────────────────────────
  function doSearch(query) {
    const q = query.trim();

    // Show spinner
    if (spinner) { spinner.classList.add('active'); }

    const url = apiUrl + '?q=' + encodeURIComponent(q);

    fetch(url, {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        if (spinner) { spinner.classList.remove('active'); }

        const students  = data.students || [];
        const editBase  = data.edit_url  || 'edit_student.php';
        const deleteBase= data.delete_url|| 'delete_student.php';
        const viewBase  = data.view_url  || 'edit_student.php';

        if (students.length === 0) {
          tableBody.innerHTML = '';
          if (noResults) {
            const displayQ = q || '';
            noResults.innerHTML = `
              <span class="rgx-no-results-icon"><i class="bi bi-search"></i></span>
              ${displayQ
                ? `No matching records found for <strong>"${esc(displayQ)}"</strong>.`
                : 'No students found. Add your first student to get started.'}
            `;
            noResults.style.display = 'block';
          }
          if (resultCount) resultCount.textContent = '0 records';
        } else {
          if (noResults) { noResults.style.display = 'none'; }
          tableBody.innerHTML = students
            .map(s => renderRow(s, editBase, deleteBase, viewBase))
            .join('');
          if (resultCount) {
            resultCount.textContent = students.length + ' record' + (students.length === 1 ? '' : 's');
          }
        }
      })
      .catch(err => {
        if (spinner) { spinner.classList.remove('active'); }
        console.error('Search failed:', err);
      });
  }

  // ── Wire up the input ─────────────────────────────────────────────
  const debouncedSearch = debounce(doSearch, DEBOUNCE_MS);

  searchInput.addEventListener('input', function () {
    debouncedSearch(this.value);
  });

  // ── Delete confirmation ───────────────────────────────────────────
  window.confirmDelete = function (e, name) {
    if (!confirm(`Delete student "${name}"? This action cannot be undone.`)) {
      e.preventDefault();
      return false;
    }
    return true;
  };

}());
