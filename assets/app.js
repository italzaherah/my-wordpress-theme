/**
 * السكربت الرئيسي لقالب الزاهرة
 * قائمة الجوال + التبويبات + الفلترة والبحث + التنبيهات + عدّادات الأرقام
 *
 * @version 4.12.4
 */
(() => {
  'use strict';

  const body = document.body;

  /* ============================================================
     ١) قائمة الجوال المنسدلة
     مع مزامنة aria-expanded وإدارة التركيز وإغلاق بزر Escape
  ============================================================ */
  const drawer   = document.querySelector('.mobile-drawer');
	const drawerDialog = drawer?.querySelector('.drawer-panel');
  const openBtn  = document.querySelector('.menu-toggle');
  const closeBtn = document.querySelector('.drawer-close');
  const backdrop = document.querySelector('.drawer-backdrop');
	let drawerReturnFocus = null;

	const drawerFocusable = () => drawer
		? Array.from(drawer.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])'))
			.filter((element) => !element.hidden && element.getAttribute('aria-hidden') !== 'true')
		: [];

	if (drawer && 'inert' in drawer && !drawer.classList.contains('open')) {
		drawer.inert = true;
	}

  const openDrawer = () => {
    if (!drawer) return;
		drawerReturnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : openBtn;
		if ('inert' in drawer) drawer.inert = false;
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
		if ('inert' in drawer) drawer.inert = true;
    openBtn?.setAttribute('aria-expanded', 'false');
    body.classList.remove('menu-open');
    // إعادة التركيز إلى زر الفتح بعد الإغلاق.
		const returnTarget = drawerReturnFocus && document.contains(drawerReturnFocus) ? drawerReturnFocus : openBtn;
		drawerReturnFocus = null;
		returnTarget?.focus();
  };

  openBtn?.addEventListener('click', openDrawer);
  closeBtn?.addEventListener('click', closeDrawer);
  backdrop?.addEventListener('click', closeDrawer);
  drawer?.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeDrawer));

  // إغلاق القائمة بزر Escape.
  document.addEventListener('keydown', (e) => {
		if (!drawer?.classList.contains('open')) return;
		if (e.key === 'Escape') {
			e.preventDefault();
      closeDrawer();
			return;
		}
		if (e.key !== 'Tab') return;

		const focusable = drawerFocusable();
		if (!focusable.length) {
			e.preventDefault();
			drawerDialog?.focus();
			return;
		}

		const first = focusable[0];
		const last = focusable[focusable.length - 1];
		if (e.shiftKey && document.activeElement === first) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
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
	const autoplay = options.autoplay !== false && !reducedMotion;
	const controls = Array.isArray(options.controls) ? options.controls : [];
  if (reducedMotion) {
    root.classList.add('is-static');
		if (!viewport.hasAttribute('tabindex')) viewport.setAttribute('tabindex', '0');
		if (!viewport.hasAttribute('aria-label')) viewport.setAttribute('aria-label', 'محتوى يدوي؛ استخدم التمرير أو الأسهم لاستعراض العناصر');
		controls.forEach((button) => {
			button.addEventListener('click', () => {
				const direction = button.getAttribute('data-partner-direction')
					|| button.getAttribute('data-marquee-direction')
					|| 'next';
				const distance = Math.max(180, Math.round(viewport.clientWidth * 0.7));
				viewport.scrollBy({ left: direction === 'previous' || direction === 'prev' ? -distance : distance, behavior: 'auto' });
			});
		});
    return null;
  }

  const itemSelector = options.itemSelector;
  const setClassName = options.setClassName;
  const speed = Number(options.speed) > 0 ? Number(options.speed) : 42;

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
  let paused = !autoplay;
	let manuallyPaused = !autoplay;
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
				paused = manuallyPaused;
        lastTs = 0;
      }, ms);
    }
  };

  const resumeAuto = () => {
    window.clearTimeout(resumeTimer);
		paused = manuallyPaused;
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
	root.classList.toggle('is-manual', !autoplay);
	if (autoplay) start();

  let resizeTimer = 0;
  window.addEventListener('resize', () => {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(() => {
      rebuild();
      lastTs = 0;
    }, 120);
  }, { passive: true });

	if (autoplay) {
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
	}

  controls.forEach((button) => {
    button.addEventListener('click', () => {
      const direction = button.getAttribute('data-partner-direction')
        || button.getAttribute('data-marquee-direction')
        || 'next';
      nudge(direction === 'previous' || direction === 'prev' ? 'previous' : 'next');
    });
  });

	// Manual carousels retain touch/pen/mouse swipe without any timed motion.
	let pointerStartX = null;
	let pointerStartY = null;
	viewport.addEventListener('pointerdown', (event) => {
		if (!event.isPrimary || event.button > 0) return;
		pointerStartX = event.clientX;
		pointerStartY = event.clientY;
		viewport.setPointerCapture?.(event.pointerId);
	});
	viewport.addEventListener('pointerup', (event) => {
		if (pointerStartX === null || pointerStartY === null) return;
		const horizontal = event.clientX - pointerStartX;
		const vertical = event.clientY - pointerStartY;
		pointerStartX = null;
		pointerStartY = null;
		if (Math.abs(horizontal) < 40 || Math.abs(horizontal) <= Math.abs(vertical)) return;
		nudge(horizontal > 0 ? 'previous' : 'next');
	});
	viewport.addEventListener('pointercancel', () => {
		pointerStartX = null;
		pointerStartY = null;
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
		autoplay: false,
		controls: Array.from(root.querySelectorAll('[data-marquee-direction]')),
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
		autoplay: false,
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
    const stage = root.querySelector('.alz-announcements-stage');
    const slides = Array.from(root.querySelectorAll('[data-announcement-slide]'));
    const dots = Array.from(root.querySelectorAll('[data-announcement-dot]'));
    const previous = root.querySelector('[data-announcement-prev]');
    const next = root.querySelector('[data-announcement-next]');
    const current = root.querySelector('[data-announcement-current]');
    const requestedInterval = Number(root.dataset.announcementInterval || 0);
    const interval = requestedInterval > 0 ? Math.max(6500, requestedInterval) : 0;
    if (!track || !slides.length) return;

		const announcementControls = root.querySelector('.alz-announcements-controls');
		let pauseToggle = root.querySelector('[data-announcement-toggle]');
		if (!pauseToggle && interval > 0 && slides.length > 1 && announcementControls) {
			pauseToggle = document.createElement('button');
			pauseToggle.type = 'button';
			pauseToggle.setAttribute('data-announcement-toggle', '');
			pauseToggle.setAttribute('aria-controls', track.id || 'alz-announcements-track');
			pauseToggle.innerHTML = '<span aria-hidden="true" data-announcement-toggle-icon>Ⅱ</span>';
			announcementControls.appendChild(pauseToggle);
		}

    let index = 0;
    let autoDirection = 1;
    let timer = 0;
    let paused = interval <= 0;
		let manuallyPaused = interval <= 0;
    let pointerStartX = null;
    const fitActiveSlide = () => {
      if (!stage || !slides[index]) return;
      const height = Math.ceil(slides[index].getBoundingClientRect().height);
      if (height > 0) stage.style.height = `${height}px`;
    };
    root.dataset.enhanced = 'true';
		const countStatus = current?.closest('.alz-announcements-count');
		if (countStatus) countStatus.setAttribute('aria-live', 'off');

		const updatePauseToggle = () => {
			if (!pauseToggle) return;
			const label = manuallyPaused ? 'تشغيل الإعلانات المتحركة' : 'إيقاف الإعلانات المتحركة';
			pauseToggle.setAttribute('aria-pressed', manuallyPaused ? 'true' : 'false');
			pauseToggle.setAttribute('aria-label', label);
			pauseToggle.title = label;
			const icon = pauseToggle.querySelector('[data-announcement-toggle-icon]');
			if (icon) icon.textContent = manuallyPaused ? '▶' : 'Ⅱ';
		};

    const setSlideAccess = (slide, active) => {
      slide.classList.toggle('is-active', active);
      slide.setAttribute('aria-hidden', active ? 'false' : 'true');
		slide.toggleAttribute('inert', !active);
    };

		slides.forEach((slide, slideIndex) => {
			const slideId = slide.id || `alz-announcement-slide-${slideIndex + 1}`;
			slide.id = slideId;
			setSlideAccess(slide, slideIndex === index);
			if (dots[slideIndex]) {
				dots[slideIndex].setAttribute('aria-controls', slideId);
				dots[slideIndex].setAttribute('tabindex', slideIndex === index ? '0' : '-1');
			}
		});

    const show = (nextIndex, moveFocus = false) => {
      if (slides.length < 1) return;
      const targetIndex = (nextIndex + slides.length) % slides.length;
      if (targetIndex === index) return;
      index = targetIndex;

      window.requestAnimationFrame(() => {
        track.style.transform = `translate3d(${-index * 100}%,0,0)`;
        slides.forEach((slide, slideIndex) => setSlideAccess(slide, slideIndex === index));
		dots.forEach((dot, dotIndex) => {
			dot.setAttribute('aria-selected', dotIndex === index ? 'true' : 'false');
			dot.setAttribute('tabindex', dotIndex === index ? '0' : '-1');
		});
        if (current) current.textContent = String(index + 1);
        fitActiveSlide();
        if (moveFocus) slides[index].querySelector('a,button')?.focus({ preventScroll: true });
      });

    };

    const stop = () => {
      if (timer) window.clearInterval(timer);
      timer = 0;
    };

    const start = () => {
      stop();
			if (!interval || reducedMotion || slides.length < 2 || paused || manuallyPaused || document.hidden) return;
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
		pauseToggle?.addEventListener('click', () => {
			manuallyPaused = !manuallyPaused;
			paused = manuallyPaused;
			if (manuallyPaused) stop();
			else start();
			updatePauseToggle();
		});
		if (pauseToggle && reducedMotion) {
			manuallyPaused = true;
			paused = true;
			pauseToggle.disabled = true;
		}
		updatePauseToggle();
		if (pauseToggle && reducedMotion) {
			pauseToggle.setAttribute('aria-label', 'الحركة متوقفة حسب إعدادات تقليل الحركة');
			pauseToggle.title = 'الحركة متوقفة حسب إعدادات تقليل الحركة';
		}

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
		root.addEventListener('mouseleave', () => { paused = manuallyPaused; start(); });
    root.addEventListener('focusin', () => { paused = true; stop(); });
    root.addEventListener('focusout', (event) => {
      if (root.contains(event.relatedTarget)) return;
			paused = manuallyPaused;
      start();
    });
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) stop();
      else start();
    });

		track.style.transform = 'translate3d(0,0,0)';
    if ('ResizeObserver' in window) {
      const observer = new ResizeObserver(fitActiveSlide);
      slides.forEach((slide) => observer.observe(slide));
    } else {
      window.addEventListener('resize', fitActiveSlide, { passive: true });
      root.querySelectorAll('img').forEach((img) => img.addEventListener('load', fitActiveSlide));
    }
    document.fonts?.ready.then(fitActiveSlide);
    fitActiveSlide();
    if (interval > 0) start();
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
