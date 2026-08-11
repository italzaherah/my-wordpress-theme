/**
 * السكربت الرئيسي لقالب الزاهرة
 * قائمة الجوال + التبويبات + الفلترة والبحث + التنبيهات + عدّادات الأرقام
 *
 * @version 3.12.0
 */
(() => {
  'use strict';

  const body = document.body;

  /* ============================================================
     ١) قائمة الجوال المنسدلة
     مع مزامنة aria-expanded وإدارة التركيز وإغلاق بزر Escape
  ============================================================ */
  const drawer   = document.querySelector('.mobile-drawer');
  const openBtn  = document.querySelector('.menu-toggle');
  const closeBtn = document.querySelector('.drawer-close');
  const backdrop = document.querySelector('.drawer-backdrop');

  const openDrawer = () => {
    if (!drawer) return;
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    openBtn?.setAttribute('aria-expanded', 'true');
    body.classList.add('menu-open');
    // نقل التركيز داخل القائمة ليتنقل مستخدم لوحة المفاتيح وقارئ الشاشة بسلاسة.
    closeBtn?.focus();
  };

  const closeDrawer = () => {
    if (!drawer) return;
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    openBtn?.setAttribute('aria-expanded', 'false');
    body.classList.remove('menu-open');
    // إعادة التركيز إلى زر الفتح بعد الإغلاق.
    openBtn?.focus();
  };

  openBtn?.addEventListener('click', openDrawer);
  closeBtn?.addEventListener('click', closeDrawer);
  backdrop?.addEventListener('click', closeDrawer);
  drawer?.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeDrawer));

  // إغلاق القائمة بزر Escape.
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer?.classList.contains('open')) {
      closeDrawer();
    }
  });

  /* ============================================================
     ٢) تبويبات صفحة الدورة (.detail-tab / .detail-panel)
  ============================================================ */
  document.querySelectorAll('.detail-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.target;

      document.querySelectorAll('.detail-tab').forEach((t) => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('.detail-panel').forEach((p) => p.classList.remove('active'));

      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');
      document.getElementById(target)?.classList.add('active');
    });
  });

  /* ============================================================
     ٣–٤) فلترة الدورات والبحث الفوري معًا
  ============================================================ */
  const courseFilterRoot = document.getElementById('courses-title')?.closest('.alz3-course-section') || document;
  const courseCards = Array.from(courseFilterRoot.querySelectorAll('.course-card[data-search]'));
  const filterTabs  = Array.from(courseFilterRoot.querySelectorAll('.filter-tab'));
  const search      = courseFilterRoot.querySelector('#course-search');
  const noResults   = courseFilterRoot.querySelector('#courses-no-results');
  let activeCategory = 'all';
  let activeSearch = '';

  const normalizeText = (value) => String(value || '').trim().toLocaleLowerCase('ar');

  const applyCourseFilters = () => {
    let visible = 0;

    courseCards.forEach((card) => {
      const categories = String(card.dataset.category || '').split(/\s+/).filter(Boolean);
      const searchable = normalizeText(card.dataset.search);
      const categoryMatch = activeCategory === 'all' || categories.includes(activeCategory);
      const searchMatch = !activeSearch || searchable.includes(activeSearch);
      const shouldShow = categoryMatch && searchMatch;

      card.hidden = !shouldShow;
      card.style.display = shouldShow ? '' : 'none';
      if (shouldShow) visible += 1;
    });

    if (noResults) noResults.hidden = visible !== 0;
  };

	filterTabs.filter((tab) => tab.matches('button[data-category]')).forEach((tab) => {
		tab.addEventListener('click', () => {
			filterTabs.filter((item) => item.matches('button[data-category]')).forEach((item) => {
        item.classList.remove('active');
        item.setAttribute('aria-pressed', 'false');
      });

      tab.classList.add('active');
      tab.setAttribute('aria-pressed', 'true');
      activeCategory = tab.dataset.category || 'all';
      applyCourseFilters();
    });
  });

	if (search?.dataset.searchMode !== 'remote') {
		search?.addEventListener('input', (event) => {
			activeSearch = normalizeText(event.target.value);
			applyCourseFilters();
		});
	}

  /* ============================================================
     ٥) اختيار طريقة الدخول (.method-btn)
  ============================================================ */
  document.querySelectorAll('.method-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.method-btn').forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');

      const passwordGroup = document.querySelector('[data-password-group]');
      if (passwordGroup) {
        passwordGroup.style.display = btn.dataset.method === 'password' ? '' : 'none';
      }
    });
  });

  /* ============================================================
     ٦) التنبيهات المنبثقة (Toast)
  ============================================================ */
  const toast = document.querySelector('.toast');
  const showToast = (message) => {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
  };

  document.querySelectorAll('[data-demo-action]').forEach((el) => {
    el.addEventListener('click', (event) => {
      event.preventDefault();
      showToast(el.dataset.demoAction || 'هذه نسخة تصميم تجريبية');
    });
  });

  /* ============================================================
     ٧) عدّادات الأرقام المتحركة ([data-counter])
     - تنسيق الأرقام حسب لغة الصفحة (عربي/إنجليزي) تلقائيًا.
     - دعم بادئة ولاحقة اختيارية دون تخزينهما ضمن القيمة الرقمية.
     - احترام تفضيل "تقليل الحركة" لدى المستخدم.
  ============================================================ */
  const counters = document.querySelectorAll('[data-counter]');

  if (counters.length) {
    // لغة الصفحة الحالية (ar / en) لتنسيق الأرقام بالشكل الصحيح في النسختين.
    const pageLocale = document.documentElement.lang || 'ar-SA';
    const formatNumber = (n) => n.toLocaleString(pageLocale);

    const prefersReducedMotion =
      window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const renderFinal = (el) => {
      const target = Number(el.dataset.counter || 0);
      const prefix = el.dataset.prefix || '';
      const suffix = el.dataset.suffix || '';
      el.textContent = prefix + formatNumber(target) + suffix;
    };

    if (!('IntersectionObserver' in window) || prefersReducedMotion) {
      // بدون حركة: عرض الرقم النهائي مباشرة.
      counters.forEach(renderFinal);
    } else {
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            const el = entry.target;
            const target = Number(el.dataset.counter || 0);
            const prefix = el.dataset.prefix || '';
            const suffix = el.dataset.suffix || '';
            const duration = 1100;
            const start = performance.now();

            const tick = (now) => {
              const p = Math.min((now - start) / duration, 1);
              const eased = 1 - Math.pow(1 - p, 3);
              el.textContent = prefix + formatNumber(Math.floor(target * eased)) + suffix;
              if (p < 1) requestAnimationFrame(tick);
            };

            requestAnimationFrame(tick);
            observer.unobserve(el);
          });
        },
        { threshold: 0.4 }
      );

      counters.forEach((counter) => observer.observe(counter));
    }
  }
})();
/* فتح نموذج إنشاء الحساب مباشرة عند استخدام ?action=register */
(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('action') !== 'register') return;

  const register = document.querySelector('.woocommerce-form-register, #customer-register');
  if (!register) return;

  register.id = register.id || 'customer-register';
  window.setTimeout(() => {
    register.scrollIntoView({ behavior: 'smooth', block: 'start' });
    register.querySelector('input:not([type="hidden"])')?.focus({ preventScroll: true });
  }, 150);
})();

/* محرك Infinite Marquee مشترك (آراء المتدربين + شركاء النجاح). */
window.alzCreateInfiniteMarquee = function alzCreateInfiniteMarquee(options) {
  const root = options.root;
  if (!root) return null;

  const viewport = root.querySelector(options.viewportSelector);
  const track = root.querySelector(options.trackSelector);
  if (!viewport || !track) return null;

  const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  if (reducedMotion) {
    root.classList.add('is-static');
    return null;
  }

  const itemSelector = options.itemSelector;
  const setClassName = options.setClassName;
  const speed = Number(options.speed) > 0 ? Number(options.speed) : 42;
  const controls = Array.isArray(options.controls) ? options.controls : [];

  let templates = Array.from(track.querySelectorAll(`:scope > ${itemSelector}`));
  if (!templates.length) {
    const seedSet = track.querySelector(`.${setClassName}`);
    if (seedSet) {
      templates = Array.from(seedSet.querySelectorAll(`:scope > ${itemSelector}`));
    }
  }
  if (!templates.length) return null;

  templates = templates.map((card) => {
    const copy = card.cloneNode(true);
    card.remove();
    return copy;
  });
  track.innerHTML = '';

  let offset = 0;
  let loopWidth = 0;
  let rafId = 0;
  let lastTs = 0;
  let paused = false;
  let running = false;
  let resumeTimer = 0;

  const buildSet = (ariaHidden) => {
    const set = document.createElement('div');
    set.className = setClassName;
    if (ariaHidden) set.setAttribute('aria-hidden', 'true');
    templates.forEach((tpl) => set.appendChild(tpl.cloneNode(true)));
    return set;
  };

  const fillSetToViewport = (set, viewW) => {
    let guard = 0;
    while (viewW > 0 && set.scrollWidth < viewW && guard < 16) {
      templates.forEach((tpl) => {
        const clone = tpl.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        set.appendChild(clone);
      });
      guard += 1;
    }
  };

  const applyTransform = () => {
    // سالب = حركة فيزيائية نحو يسار الشاشة → دخول من اليمين وخروج من اليسار.
    track.style.transform = `translate3d(${-offset}px, 0, 0)`;
  };

  const normalizeOffset = () => {
    if (loopWidth < 1) return;
    while (offset < 0) offset += loopWidth;
    if (offset >= loopWidth) {
      offset -= Math.floor(offset / loopWidth) * loopWidth;
    }
  };

  /** ثبّت المحاذاة الفيزيائية لليسار حتى لا يحاذي RTL الـtrack لليمين فيبدو الدخول من اليسار. */
  const pinTrackStart = () => {
    track.style.display = 'flex';
    track.style.flexDirection = 'row';
    track.style.position = 'relative';
    track.style.left = '0';
    track.style.right = 'auto';
    track.style.margin = '0';
    track.style.marginInline = '0';
    track.style.insetInlineStart = '0';
    track.style.insetInlineEnd = 'auto';
  };

  const rebuild = () => {
    track.innerHTML = '';
    offset = 0;
    loopWidth = 0;
    pinTrackStart();
    applyTransform();

    const viewW = viewport.clientWidth || root.clientWidth || window.innerWidth || 0;
    const setA = buildSet(false);
    track.appendChild(setA);
    fillSetToViewport(setA, viewW);

    const setB = setA.cloneNode(true);
    setB.setAttribute('aria-hidden', 'true');
    setB.querySelectorAll('a, button, input, textarea, select').forEach((el) => {
      el.setAttribute('tabindex', '-1');
    });
    // المجموعة B يجب أن تكون فيزيائيًا على يمين A داخل الـtrack.
    track.appendChild(setB);

    pinTrackStart();
    void track.offsetWidth;

    let aRect = setA.getBoundingClientRect();
    let bRect = setB.getBoundingClientRect();

    // إن وُجدت B يسار A (بسبب RTL/row-reverse)، أعد الترتيب صراحةً.
    if (bRect.left < aRect.left - 0.5) {
      track.insertBefore(setA, setB);
      track.appendChild(setB);
      pinTrackStart();
      void track.offsetWidth;
      aRect = setA.getBoundingClientRect();
      bRect = setB.getBoundingClientRect();
    }

    loopWidth = bRect.left - aRect.left;

    if (!Number.isFinite(loopWidth) || loopWidth < 1) {
      loopWidth = setA.scrollWidth;
      const styles = window.getComputedStyle(track);
      const gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
      loopWidth += gap;
    }

    let guard = 0;
    while (viewW > 0 && track.scrollWidth < viewW * 2 && guard < 8) {
      const extra = setA.cloneNode(true);
      extra.setAttribute('aria-hidden', 'true');
      track.appendChild(extra);
      guard += 1;
      void track.offsetWidth;
      const next = track.children[1];
      if (next) {
        loopWidth = next.getBoundingClientRect().left - setA.getBoundingClientRect().left;
      }
    }

    root.classList.add('is-ready');
    applyTransform();
  };

  const tick = (ts) => {
    if (!running) return;
    if (!lastTs) lastTs = ts;
    const dt = Math.min(0.064, (ts - lastTs) / 1000);
    lastTs = ts;

    if (!paused && !document.hidden && loopWidth > 0) {
      offset += speed * dt;
      normalizeOffset();
      applyTransform();
    }

    rafId = window.requestAnimationFrame(tick);
  };

  const start = () => {
    if (running) return;
    running = true;
    lastTs = 0;
    rafId = window.requestAnimationFrame(tick);
  };

  const pauseAuto = (ms = 0) => {
    paused = true;
    window.clearTimeout(resumeTimer);
    if (ms > 0) {
      resumeTimer = window.setTimeout(() => {
        paused = false;
        lastTs = 0;
      }, ms);
    }
  };

  const resumeAuto = () => {
    window.clearTimeout(resumeTimer);
    paused = false;
    lastTs = 0;
  };

  const stepSize = () => {
    const item = track.querySelector(itemSelector);
    if (!item) return 220;
    const set = track.querySelector(`.${setClassName}`);
    const gapSource = set || track;
    const gap = parseFloat(window.getComputedStyle(gapSource).columnGap || window.getComputedStyle(gapSource).gap || '0') || 0;
    return item.getBoundingClientRect().width + gap;
  };

  const nudge = (direction) => {
    if (loopWidth < 1) return;
    const step = stepSize();
    offset += direction === 'next' ? step : -step;
    normalizeOffset();
    applyTransform();
    pauseAuto(2800);
  };

  rebuild();
  start();

  let resizeTimer = 0;
  window.addEventListener('resize', () => {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(() => {
      rebuild();
      lastTs = 0;
    }, 120);
  }, { passive: true });

  root.addEventListener('mouseenter', () => pauseAuto(0));
  root.addEventListener('mouseleave', () => resumeAuto());
  root.addEventListener('focusin', () => pauseAuto(0));
  root.addEventListener('focusout', (event) => {
    if (!root.contains(event.relatedTarget)) resumeAuto();
  });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) pauseAuto(0);
    else resumeAuto();
  });

  controls.forEach((button) => {
    button.addEventListener('click', () => {
      const direction = button.getAttribute('data-partner-direction')
        || button.getAttribute('data-marquee-direction')
        || 'next';
      nudge(direction === 'previous' || direction === 'prev' ? 'previous' : 'next');
    });
  });

  return { rebuild, nudge, pauseAuto, resumeAuto };
};

/* قائمة آراء المتدربين */
(() => {
  const root = document.querySelector('[data-testimonial-marquee]');
  if (!root || typeof window.alzCreateInfiniteMarquee !== 'function') return;
  window.alzCreateInfiniteMarquee({
    root,
    viewportSelector: '.alz3-testimonial-marquee-viewport',
    trackSelector: '.alz3-testimonial-marquee-track',
    itemSelector: '.alz3-testimonial-card',
    setClassName: 'alz3-testimonial-marquee-set',
    speed: 42,
  });
})();

/* شريط شركاء النجاح — نفس محرك الـInfinite Marquee */
(() => {
  const root = document.querySelector('[data-partner-marquee]');
  if (!root || typeof window.alzCreateInfiniteMarquee !== 'function') return;
  const controls = Array.from(root.querySelectorAll('[data-partner-direction]'));
  window.alzCreateInfiniteMarquee({
    root,
    viewportSelector: '.alz3-home-partner-marquee-viewport',
    trackSelector: '.alz3-home-partner-marquee-track',
    itemSelector: '.alz3-home-partner',
    setClassName: 'alz3-home-partner-marquee-set',
    speed: 42,
    controls,
  });
})();

/* إعلانات الصفحة الرئيسية: انتقال أفقي سلس، كوبون قابل للنسخ، وإيقاف ذكي للحركة. */
(() => {
  const roots = document.querySelectorAll('[data-announcements]');
  if (!roots.length) return;

  const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

  const copyText = async (value) => {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(value);
      return;
    }
    const input = document.createElement('textarea');
    input.value = value;
    input.setAttribute('readonly', '');
    input.style.position = 'fixed';
    input.style.opacity = '0';
    document.body.appendChild(input);
    input.select();
    const copied = document.execCommand('copy');
    input.remove();
    if (!copied) throw new Error('copy_failed');
  };

  roots.forEach((root) => {
    const track = root.querySelector('[data-announcement-track]');
    const slides = Array.from(root.querySelectorAll('[data-announcement-slide]'));
    const dots = Array.from(root.querySelectorAll('[data-announcement-dot]'));
    const previous = root.querySelector('[data-announcement-prev]');
    const next = root.querySelector('[data-announcement-next]');
    const current = root.querySelector('[data-announcement-current]');
    const interval = Math.max(6500, Number(root.dataset.announcementInterval || 8000));
    if (!track || !slides.length) return;

    let index = 0;
    let autoDirection = 1;
    let timer = 0;
    let paused = false;
    let pointerStartX = null;
    root.dataset.enhanced = 'true';

    const setSlideAccess = (slide, active) => {
      slide.classList.toggle('is-active', active);
      slide.setAttribute('aria-hidden', active ? 'false' : 'true');
      if ('inert' in slide) slide.inert = !active;
    };

    const show = (nextIndex, moveFocus = false) => {
      if (slides.length < 1) return;
      const targetIndex = (nextIndex + slides.length) % slides.length;
      if (targetIndex === index) return;
      index = targetIndex;

      window.requestAnimationFrame(() => {
        track.style.transform = `translate3d(${-index * 100}%,0,0)`;
        slides.forEach((slide, slideIndex) => setSlideAccess(slide, slideIndex === index));
        dots.forEach((dot, dotIndex) => dot.setAttribute('aria-selected', dotIndex === index ? 'true' : 'false'));
        if (current) current.textContent = String(index + 1);
        if (moveFocus) slides[index].querySelector('a,button')?.focus({ preventScroll: true });
      });

    };

    const stop = () => {
      if (timer) window.clearInterval(timer);
      timer = 0;
    };

    const start = () => {
      stop();
      if (reducedMotion || slides.length < 2 || paused || document.hidden) return;
      timer = window.setInterval(() => {
        if (index >= slides.length - 1) autoDirection = -1;
        else if (index <= 0) autoDirection = 1;
        show(index + autoDirection);
      }, interval);
    };

    previous?.addEventListener('click', () => { show(index - 1); start(); });
    next?.addEventListener('click', () => { show(index + 1); start(); });
    dots.forEach((dot) => dot.addEventListener('click', () => {
      show(Number(dot.dataset.announcementDot || 0));
      start();
    }));

    root.addEventListener('keydown', (event) => {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      if (event.target.closest('input,textarea,select')) return;
      event.preventDefault();
      show(index + (event.key === 'ArrowLeft' ? 1 : -1));
      start();
    });

    root.addEventListener('pointerdown', (event) => {
      pointerStartX = event.clientX;
    }, { passive: true });
    root.addEventListener('pointerup', (event) => {
      if (pointerStartX === null) return;
      const distance = event.clientX - pointerStartX;
      pointerStartX = null;
      if (Math.abs(distance) < 45) return;
      show(index + (distance < 0 ? 1 : -1));
      start();
    }, { passive: true });
    root.addEventListener('pointercancel', () => { pointerStartX = null; }, { passive: true });

    const recordCouponCopy = (button) => {
      const endpoint = String(root.dataset.announcementAjax || '').trim();
      const announcementId = String(button.dataset.announcementId || '').trim();
      const token = String(button.dataset.announcementToken || '').trim();
      if (!endpoint || !announcementId || !token) return;

      const body = new URLSearchParams({
        action: 'alz_record_announcement_interaction',
        announcement_id: announcementId,
        token,
      });
      window.fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: true,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString(),
      }).catch(() => {});
    };

    root.querySelectorAll('[data-copy-coupon]').forEach((button) => {
      button.addEventListener('click', async () => {
        const code = String(button.dataset.copyCoupon || '').trim();
        const label = button.querySelector('[data-copy-label]');
        const status = button.parentElement?.querySelector('[data-copy-status]');
        if (!code) return;
        try {
          await copyText(code);
          recordCouponCopy(button);
          button.classList.add('is-copied');
          if (label) label.textContent = 'تم النسخ';
          if (status) status.textContent = `تم نسخ كود الخصم ${code}`;
          window.setTimeout(() => {
            button.classList.remove('is-copied');
            if (label) label.textContent = 'نسخ الكود';
            if (status) status.textContent = '';
          }, 1800);
        } catch (error) {
          if (status) status.textContent = 'تعذر نسخ الكود تلقائيًا. حدده وانسخه يدويًا.';
        }
      });
    });

    root.addEventListener('mouseenter', () => { paused = true; stop(); });
    root.addEventListener('mouseleave', () => { paused = false; start(); });
    root.addEventListener('focusin', () => { paused = true; stop(); });
    root.addEventListener('focusout', (event) => {
      if (root.contains(event.relatedTarget)) return;
      paused = false;
      start();
    });
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) stop();
      else start();
    });

    show(0);
    start();
  });
})();

/* ظهور أقسام وخصائص الصفحة تدريجيًا أثناء النزول دون مكتبات خارجية. */
(() => {
  const home = document.querySelector('.alz-home');
  if (!home) return;

  const sections = Array.from(home.querySelectorAll(':scope > section:not(.alz3-hero), :scope > .alz3-trust-wrap'));
  if (!sections.length) return;

  const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  const itemSelectors = [
    '.alz3-trust-grid > article',
    '.courses-grid > *',
    '.alz3-top-grid > *',
    '.alz3-benefit-grid > *',
    '.alz3-step-grid > *',
    '.alz3-home-partner-marquee-track .alz3-home-partner',
    '.alz-announcement-content > *',
    '.alz3-impact-grid > *',
    '.alz3-testimonial-grid > *',
    '.alz3-content-grid > *',
  ].join(',');

  sections.forEach((section) => {
    section.classList.add('alz-reveal');
    section.querySelectorAll(itemSelectors).forEach((item, index) => {
      item.classList.add('alz-reveal-item');
      item.style.setProperty('--alz-reveal-delay', `${Math.min(index, 7) * 70}ms`);
    });
  });

  document.body.classList.add('alz-reveal-ready');

  const reveal = (section) => {
    section.classList.add('is-visible');
    section.querySelectorAll('.alz-reveal-item').forEach((item) => item.classList.add('is-visible'));
  };

  if (reducedMotion || !('IntersectionObserver' in window)) {
    sections.forEach(reveal);
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      reveal(entry.target);
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

  sections.forEach((section) => observer.observe(section));
})();
