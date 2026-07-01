document.addEventListener('DOMContentLoaded', function () {

  document.querySelectorAll('.alert-success, .alert-info').forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity 0.6s ease';
      alert.style.opacity    = '0';
      setTimeout(function () { alert.remove(); }, 600);
    }, 4000);
  });

  var navbar = document.querySelector('.navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 10) {
        navbar.style.boxShadow = '0 4px 16px rgba(0,0,0,0.25)';
      } else {
        navbar.style.boxShadow = 'none';
      }
    });
  }

  if ('IntersectionObserver' in window) {
    var lazyImages = document.querySelectorAll('img[data-src]');
    var imgObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var img    = entry.target;
          img.src    = img.dataset.src;
          img.removeAttribute('data-src');
          imgObserver.unobserve(img);
        }
      });
    });
    lazyImages.forEach(function (img) { imgObserver.observe(img); });
  }

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var msg = form.dataset.confirm || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  document.querySelectorAll('textarea[data-maxlength]').forEach(function (ta) {
    var max     = parseInt(ta.dataset.maxlength);
    var counter = document.createElement('div');
    counter.className   = 'char-counter text-muted';
    counter.style.cssText = 'font-size:12px;text-align:right;margin-top:4px;';
    ta.parentNode.appendChild(counter);

    function update() {
      var left = max - ta.value.length;
      counter.textContent = left + ' characters remaining';
      counter.style.color = left < 20 ? '#e74c3c' : '';
    }
    ta.addEventListener('input', update);
    update();
  });

  var priceInput   = document.getElementById('price');
  var pricePreview = document.getElementById('price-preview');
  if (priceInput && pricePreview) {
    priceInput.addEventListener('input', function () {
      var val = parseFloat(priceInput.value);
      pricePreview.textContent = isNaN(val) ? '' : 'Preview: R ' + val.toLocaleString('en-ZA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    });
  }

  var scrollBtn = document.createElement('button');
  scrollBtn.innerHTML   = '↑';
  scrollBtn.title       = 'Back to top';
  scrollBtn.id          = 'scroll-top-btn';
  scrollBtn.style.cssText =
    'position:fixed;bottom:24px;right:24px;width:40px;height:40px;' +
    'border-radius:50%;background:var(--yellow);color:var(--gray-dark);' +
    'border:none;font-size:18px;font-weight:700;cursor:pointer;' +
    'box-shadow:0 2px 8px rgba(0,0,0,0.15);opacity:0;transition:opacity 0.3s;' +
    'z-index:999;line-height:1;';
  document.body.appendChild(scrollBtn);

  window.addEventListener('scroll', function () {
    scrollBtn.style.opacity = window.scrollY > 300 ? '1' : '0';
    scrollBtn.style.pointerEvents = window.scrollY > 300 ? 'auto' : 'none';
  });

  scrollBtn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  var params   = new URLSearchParams(window.location.search);
  var activeCat = params.get('category');
  if (activeCat) {
    document.querySelectorAll('.category-pill').forEach(function (pill) {
      if (pill.textContent.trim() === activeCat) {
        pill.classList.add('active');
      }
    });
  }

  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      form.querySelectorAll('[required]').forEach(function (field) {
        if (!field.value.trim()) {
          field.style.borderColor = '#e74c3c';
          field.addEventListener('input', function fix() {
            field.style.borderColor = '';
            field.removeEventListener('input', fix);
          });
        }
      });
    });
  });

  document.querySelectorAll('.product-card').forEach(function (card) {
    card.addEventListener('mouseenter', function () {
      card.style.transform = 'translateY(-4px)';
    });
    card.addEventListener('mouseleave', function () {
      card.style.transform = 'translateY(0)';
    });
  });

  var uploadArea = document.getElementById('upload-area');
  var fileInput  = document.getElementById('image');
  if (uploadArea && fileInput) {
    uploadArea.addEventListener('dragover', function (e) {
      e.preventDefault();
      uploadArea.style.borderColor = 'var(--yellow)';
      uploadArea.style.background  = 'var(--yellow-light)';
    });
    uploadArea.addEventListener('dragleave', function () {
      uploadArea.style.borderColor = '';
      uploadArea.style.background  = '';
    });
    uploadArea.addEventListener('drop', function (e) {
      e.preventDefault();
      uploadArea.style.borderColor = '';
      uploadArea.style.background  = '';
      var file = e.dataTransfer.files[0];
      if (file && file.type.startsWith('image/')) {
        var dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        if (typeof previewImage === 'function') previewImage(fileInput);
      }
    });
  }

  var navSearch = document.querySelector('.navbar-search input');
  if (navSearch && navSearch.value !== '') {
    var clearBtn = document.createElement('button');
    clearBtn.type        = 'button';
    clearBtn.textContent = '×';
    clearBtn.style.cssText =
      'position:absolute;right:54px;top:50%;transform:translateY(-50%);' +
      'background:none;border:none;font-size:16px;cursor:pointer;color:#aaa;padding:0 4px;';
    navSearch.parentNode.style.position = 'relative';
    navSearch.parentNode.appendChild(clearBtn);
    clearBtn.addEventListener('click', function () {
      navSearch.value = '';
      navSearch.focus();
      clearBtn.remove();
      if (window.location.search.includes('q=')) {
        window.location.href = 'index.php';
      }
    });
  }

});