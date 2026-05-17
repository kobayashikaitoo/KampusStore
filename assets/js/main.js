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

  fetch('/api/wishlist_toggle.php', {
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
function showToast(msg) {
  let toast = document.getElementById('ks-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'ks-toast';
    toast.style.cssText = `
      position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);
      background:#1e293b;color:white;
      padding:12px 20px;border-radius:999px;
      font-family:Inter,system-ui,sans-serif;font-size:14px;font-weight:500;
      box-shadow:0 8px 24px rgba(0,0,0,0.2);
      z-index:9999;opacity:0;transition:opacity .25s ease,transform .25s ease;
      white-space:nowrap;max-width:90vw;text-align:center;
    `;
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.style.opacity = '1';
  toast.style.transform = 'translateX(-50%) translateY(0)';
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(-50%) translateY(20px)';
  }, 2800);
}

// ── Search (server-side) ───────────────────────────
const searchInput = document.getElementById('nav-search-input');
const searchBtn   = document.getElementById('nav-search-btn');

function doSearch() {
  const q = (searchInput?.value || '').trim();
  if (!q) return;
  const url = new URL('/index.php', window.location.origin);
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
