// ── KampusStore main.js ──────────────────────────────────────

// Navbar scroll effect
const nav = document.getElementById('ks-nav');
window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 40);
}, { passive: true });

// ── Category Filter ──────────────────────────────────────────
function filterCat(el) {
  document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  const cat = el.dataset.cat;
  document.querySelectorAll('.product-card').forEach(card => {
    const show = cat === 'semua' || card.dataset.cat === cat;
    card.style.display = show ? '' : 'none';
    if (show) {
      card.classList.remove('anim-fiu');
      void card.offsetWidth; // reflow
      card.classList.add('anim-fiu');
    }
  });
}

// ── Filter Pills ─────────────────────────────────────────────
function setFilter(el) {
  document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
}

// ── Wishlist Toggle (DB-connected) ─────────────────────────
function toggleWishlist(btn) {
  const id = btn.dataset.id;
  btn.disabled = true;

  fetch(window.BASE_URL + 'api/wishlist_toggle.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'product_id=' + encodeURIComponent(id)
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'unauthenticated') {
      window.location.href = data.redirect + '?redirect=' + encodeURIComponent(window.location.pathname);
      return;
    }
    if (data.saved) {
      btn.textContent = '\u2665';
      btn.style.color = '#ef4444';
      btn.classList.add('saved');
      showToast('\u2665 Ditambahkan ke Wishlist');
    } else {
      btn.textContent = '\u2661';
      btn.style.color = '';
      btn.classList.remove('saved');
      showToast('Dihapus dari Wishlist');
    }
    // Bounce
    btn.style.transform = 'scale(1.35)';
    setTimeout(() => { btn.style.transform = ''; btn.disabled = false; }, 280);
  })
  .catch(() => { btn.disabled = false; });
}



// ── Toast ────────────────────────────────────────────────────
function showToast(msg, type = 'info', duration = 3000) {
  // Icons untuk berbagai tipe
  const icons = {
    success: '<i class="fas fa-check-circle"></i>',
    error: '<i class="fas fa-exclamation-circle"></i>',
    warning: '<i class="fas fa-exclamation-triangle"></i>',
    info: '<i class="fas fa-info-circle"></i>'
  };

  // Create container jika belum ada
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  // Create toast element
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <span class="toast-icon">${icons[type] || icons['info']}</span>
    <span class="toast-message">${msg}</span>
    <button type="button" class="toast-close" aria-label="Close">
      <i class="fas fa-times"></i>
    </button>
  `;

  // Add to container
  container.appendChild(toast);

  // Close button handler
  toast.querySelector('.toast-close').addEventListener('click', () => {
    removeToast(toast);
  });

  // Auto remove
  const timer = setTimeout(() => {
    removeToast(toast);
  }, duration);

  // Store timer untuk cancel jika perlu
  toast._timer = timer;
}

function removeToast(toast) {
  if (toast._timer) clearTimeout(toast._timer);
  toast.classList.add('remove');
  setTimeout(() => {
    if (toast.parentNode) toast.remove();
  }, 300);
}

// ── Search (server-side) ───────────────────────────
const searchInput = document.getElementById('nav-search-input');
const searchBtn   = document.getElementById('nav-search-btn');

function doSearch() {
  const q = (searchInput?.value || '').trim();
  if (!q) return;
  const url = new URL(window.BASE_URL + 'index.php', window.location.origin);
  url.searchParams.set('q', q);
  window.location.href = url.toString();
}

// Pre-fill search input from URL on page load
(function() {
  const params = new URLSearchParams(window.location.search);
  const q = params.get('q');
  if (q && searchInput) searchInput.value = q;
})();

searchBtn?.addEventListener('click', doSearch);
searchInput?.addEventListener('keydown', e => { if (e.key === 'Enter') doSearch(); });

// ── Intersection Observer for cards ─────────────────────────
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.product-card').forEach(card => observer.observe(card));
