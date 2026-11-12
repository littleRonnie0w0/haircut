import './bootstrap';

function initReveal() {
	if (!document.body.classList.contains('home-page')) return;

	const elements = document.querySelectorAll('.home-page-wrap > *, .home-mosaic-item, .home-products-section .product-card, .home-reviews-badge, footer.site .footer-top, footer.site .footer-bottom');
	if (!('IntersectionObserver' in window)) {
		elements.forEach((element) => element.classList.add('is-in'));
		return;
	}

	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			entry.target.classList.add('is-in');
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.01, rootMargin: '0px 0px -4% 0px' });

	elements.forEach((element, index) => {
		element.style.transitionDelay = `${Math.min(index % 8, 7) * 55}ms`;
		observer.observe(element);
	});
}

function initNavigation() {
	const toggle = document.querySelector('.nav-toggle');
	const navigation = document.getElementById('nav-acc');
	const overlay = document.querySelector('.side-nav-overlay');
	const close = document.querySelector('.side-nav-close');
	if (!toggle || !navigation) return;

	const setOpen = (open) => {
		navigation.classList.toggle('is-open', open);
		document.body.classList.toggle('nav-open', open);
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		if (overlay) overlay.hidden = !open;
	};

	window.setSideNavOpen = setOpen;
	toggle.addEventListener('click', () => setOpen(!navigation.classList.contains('is-open')));
	close?.addEventListener('click', () => setOpen(false));
	overlay?.addEventListener('click', () => setOpen(false));
	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') setOpen(false);
	});
	window.addEventListener('pagehide', () => setOpen(false));
}

function initLoader() {
	const loader = document.getElementById('app-loader');
	const show = () => {
		window.setSideNavOpen?.(false);
		document.documentElement.classList.add('hhs-loading');
		document.body.classList.add('is-loading');
		if (loader) loader.hidden = false;
	};
	const hide = () => {
		window.setSideNavOpen?.(false);
		document.documentElement.classList.remove('hhs-loading');
		document.body.classList.remove('is-loading');
		if (loader) loader.hidden = true;
	};

	window.showAppLoader = show;
	window.hideAppLoader = hide;
	hide();
	window.addEventListener('pageshow', hide);
	window.addEventListener('pagehide', hide);
	document.addEventListener('visibilitychange', () => {
		if (document.visibilityState === 'visible') hide();
	});

	initRoleDefaultNavigation();

	document.addEventListener('submit', (event) => {
		const form = event.target;
		if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-loader')) return;
		// Esperar a confirms/onsubmit que cancelan el envío.
		window.setTimeout(() => {
			if (event.defaultPrevented) return;
			show();
		}, 0);
	});
	document.addEventListener('click', (event) => {
		if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
		if (event.target.closest('.nav-toggle, .side-nav-close, .carousel-dot, .carousel-btn, [data-panel-toggle], [data-panel-close], [data-no-loader], #header-back, [data-gallery-open], .gallery-modal, .confirm-modal')) return;
		const link = event.target.closest('a[href]');
		if (!link || link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-no-loader')) return;
		const href = link.getAttribute('href') || '';
		if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
		try {
			const url = new URL(link.href, window.location.href);
			if (url.origin !== window.location.origin) return;
			// Descargas / rutas de imagen no navegan a HTML: no mostrar loader.
			if (url.searchParams.has('download') || /\/imagen\//.test(url.pathname)) return;
			show();
		} catch {}
	});
}

function initCarousels() {
	document.querySelectorAll('.carousel-auto').forEach((root) => {
		const track = root.querySelector('.carousel-track');
		const viewport = root.querySelector('.carousel-viewport');
		const dots = root.querySelector('.carousel-dots');
		const previous = root.querySelector('.carousel-btn-prev');
		const next = root.querySelector('.carousel-btn-next');
		const slides = track ? Array.from(track.querySelectorAll('.carousel-slide')) : [];
		if (!track || !viewport || slides.length === 0) return;

		let page = 0;
		let direction = 1;
		let timer;
		const delay = Number.parseInt(root.dataset.autoplay || '5000', 10);
		const desktopCount = Number.parseInt(root.dataset.perView || '3', 10);
		const perView = () => window.matchMedia('(min-width: 900px)').matches ? Math.min(desktopCount, slides.length) : 1;
		const pageCount = () => Math.max(1, Math.ceil(slides.length / perView()));
		const maxStart = () => Math.max(0, slides.length - perView());
		const startIndex = () => Math.min(page * perView(), maxStart());

		const render = () => {
			page = Math.max(0, Math.min(page, pageCount() - 1));
			const gap = Number.parseFloat(getComputedStyle(track).gap) || 16;
			const offset = startIndex() * (slides[0].getBoundingClientRect().width + gap);
			track.style.transform = `translateX(${-offset}px)`;
			dots?.querySelectorAll('.carousel-dot').forEach((dot, index) => dot.classList.toggle('is-active', index === page));
			if (previous) previous.disabled = startIndex() === 0;
			if (next) next.disabled = startIndex() >= maxStart();
		};
		const restart = () => {
			window.clearInterval(timer);
			if (pageCount() > 1) {
				timer = window.setInterval(() => {
					if (page >= pageCount() - 1) direction = -1;
					if (page <= 0) direction = 1;
					page += direction;
					render();
				}, delay);
			}
		};
		const buildDots = () => {
			if (!dots) return;
			dots.replaceChildren();
			for (let index = 0; index < pageCount(); index += 1) {
				const dot = document.createElement('button');
				dot.type = 'button';
				dot.className = `carousel-dot${index === page ? ' is-active' : ''}`;
				dot.setAttribute('aria-label', `Ir a la diapositiva ${index + 1}`);
				dot.addEventListener('click', () => { page = index; render(); restart(); });
				dots.appendChild(dot);
			}
		};

		previous?.addEventListener('click', () => { direction = -1; page -= 1; render(); restart(); });
		next?.addEventListener('click', () => { direction = 1; page += 1; render(); restart(); });
		root.addEventListener('mouseenter', () => window.clearInterval(timer));
		root.addEventListener('mouseleave', restart);
		let startX = 0;
		let startY = 0;
		viewport.addEventListener('touchstart', (event) => {
			startX = event.changedTouches[0].clientX;
			startY = event.changedTouches[0].clientY;
		}, { passive: true });
		viewport.addEventListener('touchend', (event) => {
			const deltaX = event.changedTouches[0].clientX - startX;
			const deltaY = event.changedTouches[0].clientY - startY;
			if (Math.abs(deltaX) < 36 || Math.abs(deltaX) < Math.abs(deltaY)) return;
			direction = deltaX < 0 ? 1 : -1;
			page += direction;
			render();
			restart();
		}, { passive: true });

		buildDots();
		render();
		restart();
		window.addEventListener('resize', () => { buildDots(); render(); restart(); });
	});
}

function initPanelsAndFilters() {
	document.querySelectorAll('[data-panel-toggle]').forEach((button) => {
		button.addEventListener('click', () => {
			const panel = document.getElementById(button.dataset.panelToggle);
			if (!panel) return;
			panel.hidden = !panel.hidden;
			button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
		});
	});
	document.querySelectorAll('[data-panel-close]').forEach((button) => {
		button.addEventListener('click', () => {
			const panel = document.getElementById(button.dataset.panelClose);
			if (!panel) return;
			panel.hidden = true;
			document.querySelector(`[data-panel-toggle="${panel.id}"]`)?.setAttribute('aria-expanded', 'false');
		});
	});
	document.querySelectorAll('[data-filter-input]').forEach((input) => {
		input.addEventListener('input', () => {
			const key = input.dataset.filterInput;
			const query = input.value.trim().toLocaleLowerCase('es');
			document.querySelectorAll(`[data-filter-row="${key}"]`).forEach((row) => {
				row.hidden = query !== '' && !(row.dataset.filterText || '').includes(query);
			});
		});
	});
}

function initPhotoPicker() {
	const panel = document.getElementById('book-photo-panel');
	const message = document.getElementById('book-photo-msg');
	const addWrap = panel?.querySelector('[data-book-photo-add]');
	const picker = panel?.querySelector('[data-book-photo-picker]');
	const drop = panel?.querySelector('[data-book-photo-drop]');
	const list = panel?.querySelector('[data-book-photo-list]');
	if (!panel || !addWrap || !picker || !drop || !list) return;

	const max = Number(panel.dataset.bookPhotoMax || 2);
	const allowed = new Set(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
	const items = [];

	const showMessage = (text) => {
		if (!message) return;
		message.hidden = !text;
		message.textContent = text;
	};

	const syncUi = () => {
		list.hidden = items.length === 0;
		addWrap.hidden = items.length >= max;
		picker.value = '';
	};

	const relabelItems = () => {
		items.forEach((item, index) => {
			const title = item.row.querySelector('[data-book-photo-title]');
			if (title) title.textContent = `Referencia ${index + 1}`;
			const image = item.row.querySelector('img');
			if (image) image.alt = `Referencia ${index + 1}`;
			const clear = item.row.querySelector('.book-photo-clear');
			if (clear) clear.setAttribute('aria-label', `Quitar referencia ${index + 1}`);
		});
	};

	const removeItem = (item) => {
		const index = items.indexOf(item);
		if (index < 0) return;
		items.splice(index, 1);
		if (item.objectUrl) URL.revokeObjectURL(item.objectUrl);
		item.row.remove();
		relabelItems();
		syncUi();
		showMessage('');
	};

	const addFile = (file) => {
		if (!file) return;
		if (items.length >= max) {
			showMessage(`Solo puedes agregar hasta ${max} imágenes.`);
			return;
		}
		if (!allowed.has(file.type)) {
			showMessage('Solo se permiten imágenes (JPG, PNG o WEBP). No PDF.');
			return;
		}
		if (file.size > 3 * 1024 * 1024) {
			showMessage('Cada imagen no puede superar 3 MB.');
			return;
		}

		showMessage('');
		const objectUrl = URL.createObjectURL(file);
		const row = document.createElement('li');
		row.className = 'book-photo-item';

		const figure = document.createElement('div');
		figure.className = 'book-photo-item-preview';
		const image = document.createElement('img');
		image.src = objectUrl;
		image.alt = `Referencia ${items.length + 1}`;
		figure.appendChild(image);

		const meta = document.createElement('div');
		meta.className = 'book-photo-item-meta';
		const title = document.createElement('strong');
		title.dataset.bookPhotoTitle = '1';
		title.textContent = `Referencia ${items.length + 1}`;
		const name = document.createElement('span');
		name.textContent = file.name || 'Referencia';
		meta.append(title, name);

		const clear = document.createElement('button');
		clear.type = 'button';
		clear.className = 'book-photo-clear';
		clear.setAttribute('aria-label', `Quitar referencia ${items.length + 1}`);
		clear.innerHTML = '<span aria-hidden="true">×</span>';

		const fileInput = document.createElement('input');
		fileInput.type = 'file';
		fileInput.name = 'fotos[]';
		fileInput.className = 'book-photo-input';
		fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
		fileInput.hidden = true;
		try {
			const transfer = new DataTransfer();
			transfer.items.add(file);
			fileInput.files = transfer.files;
		} catch {
			showMessage('No se pudo adjuntar la imagen en este navegador.');
			URL.revokeObjectURL(objectUrl);
			return;
		}

		row.append(figure, meta, clear, fileInput);
		list.appendChild(row);

		const item = { row, objectUrl, fileInput };
		clear.addEventListener('click', () => removeItem(item));
		items.push(item);
		relabelItems();
		syncUi();
	};

	picker.addEventListener('change', () => {
		addFile(picker.files?.[0]);
		picker.value = '';
	});
	['dragenter', 'dragover'].forEach((name) => drop.addEventListener(name, (event) => {
		event.preventDefault();
		drop.classList.add('is-drag');
	}));
	['dragleave', 'drop'].forEach((name) => drop.addEventListener(name, (event) => {
		event.preventDefault();
		drop.classList.remove('is-drag');
	}));
	drop.addEventListener('drop', (event) => {
		addFile(event.dataTransfer?.files?.[0]);
	});

	syncUi();
}

function initAvailability() {
	const form = document.querySelector('.avail-form');
	const month = document.querySelector('[data-availability-month]');
	const year = document.querySelector('[data-availability-year]');
	const base = document.querySelector('[data-availability-url]')?.dataset.availabilityUrl;
	let saving = false;

	const autosave = async () => {
		if (!form || saving) return false;
		saving = true;
		try {
			const body = new FormData(form);
			const response = await fetch(form.action, {
				method: 'POST',
				body,
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				credentials: 'same-origin',
			});
			return response.ok;
		} catch {
			return false;
		} finally {
			saving = false;
		}
	};

	const navigate = async () => {
		if (!month || !year || !base) return;
		await autosave();
		window.location.href = `${base}?anio=${encodeURIComponent(year.value)}&mes=${encodeURIComponent(month.value)}`;
	};
	month?.addEventListener('change', () => { void navigate(); });
	year?.addEventListener('change', () => { void navigate(); });

	document.querySelectorAll('.avail-nav').forEach((link) => {
		link.addEventListener('click', (event) => {
			event.preventDefault();
			const href = link.href;
			void (async () => {
				await autosave();
				window.location.href = href;
			})();
		});
	});

	form?.querySelectorAll('input[name="off[]"]').forEach((checkbox) => {
		checkbox.addEventListener('change', () => { void autosave(); });
	});

	const enableManualTime = (select) => {
		if (!(select instanceof HTMLSelectElement) || select.dataset.manual === '1') return;
		const input = document.createElement('input');
		input.type = 'time';
		input.name = select.name;
		input.id = select.id;
		input.value = select.value;
		input.required = true;
		input.step = '60';
		input.className = select.className || '';
		input.title = 'Escribe la hora manualmente';
		input.dataset.availTime = '1';
		select.replaceWith(input);
		input.focus();
		input.select?.();
	};

	document.querySelectorAll('[data-avail-time]').forEach((el) => {
		el.addEventListener('dblclick', () => enableManualTime(el));
	});

	const visibleYear = document.querySelector('[data-visible-year]');
	const showYear = () => {
		document.querySelectorAll('[data-visible-panel]').forEach((panel) => {
			panel.hidden = panel.dataset.visiblePanel !== visibleYear?.value;
		});
	};
	visibleYear?.addEventListener('change', showYear);
	showYear();
}

function initHours() {
	const labels = { pendiente: 'Pendiente', confirmada: 'Confirmada', realizada: 'Realizada', realizando: 'Realizando', finalizado: 'Finalizado' };
	const parse = (value) => {
		const parts = (value || '').split(/[-T:]/).map(Number);
		return parts.length >= 5 ? new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5] || 0) : null;
	};
	const tick = () => {
		const current = Date.now();
		document.querySelectorAll('.hours-item').forEach((item) => {
			const start = parse(item.dataset.start);
			const end = parse(item.dataset.end);
			if (!start || !end) return;
			if (current >= end.getTime() + 5 * 60 * 1000) {
				item.remove();
				return;
			}
			let state = item.dataset.base || 'pendiente';
			if (current >= end.getTime()) state = 'finalizado';
			else if (current >= start.getTime()) state = 'realizando';
			const badge = item.querySelector('.hours-status');
			if (badge) {
				badge.className = `dash-pill hours-status st-${state}`;
				badge.textContent = labels[state] || state;
			}
		});
		document.querySelectorAll('[data-hours-day]').forEach((day) => {
			const count = day.querySelectorAll('.hours-item').length;
			const label = day.querySelector('.hours-count');
			if (label) label.textContent = `${count} ${count === 1 ? 'persona' : 'personas'}`;
			if (count === 0) day.hidden = true;
		});
	};
	tick();
	if (document.querySelector('.hours-item')) window.setInterval(tick, 15000);
}

function initSmallInteractions() {
	const map = document.querySelector('.footer-map');
	const loadMap = () => {
		if (map?.dataset.src && map.src !== map.dataset.src) map.src = map.dataset.src;
	};
	if ('requestIdleCallback' in window) window.requestIdleCallback(loadMap, { timeout: 2500 });
	else window.setTimeout(loadMap, 1200);

	document.querySelectorAll('.alert[data-autohide]').forEach((alert) => {
		window.setTimeout(() => {
			alert.classList.add('is-gone');
			window.setTimeout(() => alert.remove(), 260);
		}, Number.parseInt(alert.dataset.autohide || '2500', 10));
	});

	if (document.body.classList.contains('book-page')) {
		const key = 'hhs-book-scroll';
		const saved = sessionStorage.getItem(key);
		if (saved) {
			window.scrollTo(0, Number.parseInt(saved, 10) || 0);
			sessionStorage.removeItem(key);
		}
		document.addEventListener('click', (event) => {
			if (event.target.closest('a[href*="/reservar"]')) sessionStorage.setItem(key, String(window.scrollY));
		});
	}
}

function initAiStudio() {
	const panel = document.querySelector('[data-ai-photo-panel]');
	const input = document.getElementById('ai-photo');
	const cameraInput = document.querySelector('[data-ai-photo-camera]');
	const galleryInput = document.querySelector('[data-ai-photo-gallery]');
	const preview = document.querySelector('[data-ai-photo-preview]');
	const image = preview?.querySelector('img');
	const emptyActions = document.querySelector('[data-ai-photo-actions-empty]');
	const cameraActions = document.querySelector('[data-ai-photo-actions-camera]');
	const galleryActions = document.querySelector('[data-ai-photo-actions-gallery]');
	const changeLink = document.querySelector('[data-ai-photo-change-link]');
	const error = document.querySelector('[data-ai-photo-error]');
	const form = document.querySelector('[data-ai-form]');
	let objectUrl;
	let preparing = false;
	let photoSource = null;
	let photoKept = false;

	const showError = (message) => {
		if (!error) return;
		error.textContent = message;
		error.hidden = false;
	};

	const setActionVisibility = () => {
		const hasPhoto = Boolean(input?.files?.length);
		if (emptyActions) emptyActions.hidden = hasPhoto;
		if (preview) preview.hidden = !hasPhoto;
		if (changeLink) changeLink.hidden = !(hasPhoto && photoKept);
		if (cameraActions) cameraActions.hidden = !(hasPhoto && photoSource === 'camera' && !photoKept);
		if (galleryActions) galleryActions.hidden = !(hasPhoto && photoSource === 'gallery' && !photoKept);
		panel?.classList.toggle('has-photo', hasPhoto);
		panel?.classList.toggle('is-kept', hasPhoto && photoKept);
	};

	const reset = () => {
		if (objectUrl) URL.revokeObjectURL(objectUrl);
		objectUrl = undefined;
		photoSource = null;
		photoKept = false;
		if (input) input.value = '';
		if (cameraInput) cameraInput.value = '';
		if (galleryInput) galleryInput.value = '';
		image?.removeAttribute('src');
		if (error) error.hidden = true;
		setActionVisibility();
	};

	const loadImage = (file) => new Promise((resolve, reject) => {
		const url = URL.createObjectURL(file);
		const img = new Image();
		img.onload = () => {
			URL.revokeObjectURL(url);
			resolve(img);
		};
		img.onerror = () => {
			URL.revokeObjectURL(url);
			reject(new Error('No se pudo leer la imagen.'));
		};
		img.src = url;
	});

	const canvasToJpegFile = (canvas, quality) => new Promise((resolve, reject) => {
		canvas.toBlob((blob) => {
			if (!blob) {
				reject(new Error('No se pudo optimizar la imagen.'));
				return;
			}
			resolve(new File([blob], 'simulacion.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
		}, 'image/jpeg', quality);
	});

	const optimizePhoto = async (file) => {
		const img = await loadImage(file);
		const maxSide = 2048;
		const scale = Math.min(1, maxSide / Math.max(img.naturalWidth || img.width, img.naturalHeight || img.height));
		const width = Math.max(1, Math.round((img.naturalWidth || img.width) * scale));
		const height = Math.max(1, Math.round((img.naturalHeight || img.height) * scale));
		const canvas = document.createElement('canvas');
		canvas.width = width;
		canvas.height = height;
		const ctx = canvas.getContext('2d');
		if (!ctx) throw new Error('Tu navegador no puede preparar la foto.');
		ctx.drawImage(img, 0, 0, width, height);

		let quality = 0.88;
		let optimized = await canvasToJpegFile(canvas, quality);
		while (optimized.size > 5.5 * 1024 * 1024 && quality > 0.55) {
			quality -= 0.08;
			optimized = await canvasToJpegFile(canvas, quality);
		}
		if (optimized.size > 6 * 1024 * 1024) {
			throw new Error('La fotografía sigue siendo demasiado pesada. Prueba otra con menos resolución.');
		}
		return optimized;
	};

	const assignToInput = (file) => {
		if (!input) return;
		try {
			const transfer = new DataTransfer();
			transfer.items.add(file);
			input.files = transfer.files;
		} catch {
			throw new Error('No se pudo adjuntar la fotografía en este dispositivo.');
		}
	};

	const showPreview = (file, source) => {
		if (!preview || !image) return;
		if (objectUrl) URL.revokeObjectURL(objectUrl);
		objectUrl = URL.createObjectURL(file);
		image.src = objectUrl;
		photoSource = source;
		photoKept = false;
		if (error) error.hidden = true;
		setActionVisibility();
	};

	const select = async (file, source) => {
		if (!file || !input || preparing) return;
		preparing = true;
		panel?.classList.add('is-preparing');
		if (error) error.hidden = true;
		try {
			if (!file.type.startsWith('image/') && file.type !== '') {
				throw new Error('El archivo debe ser una imagen.');
			}
			const optimized = await optimizePhoto(file);
			assignToInput(optimized);
			showPreview(optimized, source);
		} catch (err) {
			reset();
			showError(err?.message || 'No se pudo usar esa imagen. Toma una foto JPG o elige otra de la galería.');
		} finally {
			preparing = false;
			panel?.classList.remove('is-preparing');
			if (cameraInput) cameraInput.value = '';
			if (galleryInput) galleryInput.value = '';
		}
	};

	document.querySelectorAll('[data-ai-photo-pick]').forEach((button) => {
		button.addEventListener('click', () => {
			if (button.disabled || preparing) return;
			const mode = button.getAttribute('data-ai-photo-pick');
			const target = mode === 'camera' ? cameraInput : galleryInput;
			target?.click();
		});
	});

	document.querySelectorAll('[data-ai-photo-keep]').forEach((button) => {
		button.addEventListener('click', () => {
			if (button.disabled || !input?.files?.length) return;
			photoKept = true;
			setActionVisibility();
		});
	});

	changeLink?.addEventListener('click', () => {
		photoKept = false;
		setActionVisibility();
	});

	cameraInput?.addEventListener('change', () => {
		const file = cameraInput.files?.[0];
		if (file) select(file, 'camera');
	});
	galleryInput?.addEventListener('change', () => {
		const file = galleryInput.files?.[0];
		if (file) select(file, 'gallery');
	});

	form?.addEventListener('submit', (event) => {
		if (preparing) {
			event.preventDefault();
			showError('Espera un momento: aún estamos preparando la fotografía.');
			return;
		}
		if (!input?.files?.length) {
			event.preventDefault();
			showError('Toma una foto o elige una de la galería.');
		}
	});

	setActionVisibility();

	const categoryTabs = Array.from(document.querySelectorAll('[data-ai-category-tab]'));
	const categoryPanels = Array.from(document.querySelectorAll('[data-ai-category-panel]'));
	categoryTabs.forEach((tab) => {
		tab.addEventListener('click', () => {
			if (tab.getAttribute('aria-selected') === 'true') return;
			const category = tab.dataset.aiCategoryTab;
			document.querySelectorAll('input[name="servicio_id"]').forEach((radio) => {
				radio.checked = false;
			});
			categoryTabs.forEach((item) => {
				const active = item === tab;
				item.classList.toggle('is-active', active);
				item.setAttribute('aria-selected', active ? 'true' : 'false');
			});
			categoryPanels.forEach((panel) => {
				const active = panel.dataset.aiCategoryPanel === category;
				panel.hidden = !active;
				panel.querySelectorAll('input[name="servicio_id"]').forEach((radio) => {
					radio.required = active;
				});
			});
		});
	});

	const statusRoot = document.querySelector('[data-ai-status-url]');
	if (statusRoot) {
		const poll = window.setInterval(async () => {
			try {
				const response = await fetch(statusRoot.dataset.aiStatusUrl, { headers: { Accept: 'application/json' } });
				if (!response.ok) return;
				const status = await response.json();
				if (['pending', 'processing'].includes(status.status)) return;

				window.clearInterval(poll);
				window.hideAppLoader?.();

				const next = new URL(status.show_url, window.location.href);
				const here = new URL(window.location.href);
				if (next.pathname === here.pathname) {
					window.location.reload();
				} else {
					window.location.replace(next.href);
				}
			} catch {}
		}, 3000);
	} else {
		window.hideAppLoader?.();
	}
}

function initBookingWizard() {
	const wizard = document.querySelector('[data-book-wizard]');
	if (!wizard) return;

	const stepOrder = ['servicio', 'fecha', 'referencia', 'lugar', 'resumen'];
	const steps = new Map([...wizard.querySelectorAll('[data-book-step]')].map((step) => [step.dataset.bookStep, step]));
	const progress = new Map([...wizard.querySelectorAll('[data-book-progress]')].map((item) => [item.dataset.bookProgress, item]));
	const form = wizard.querySelector('.book-wizard-form');
	const categoryTabs = [...wizard.querySelectorAll('[data-book-category-tab]')];
	const categoryPanels = [...wizard.querySelectorAll('[data-book-category-panel]')];
	const serviceInput = wizard.querySelector('input[name="servicio_id"]');
	const serviceOptions = [...wizard.querySelectorAll('[data-book-service-option]')];
	let current = stepOrder.includes(wizard.dataset.bookInitialStep) ? wizard.dataset.bookInitialStep : 'servicio';
	let selectedServiceUrl;

	const selectService = (option) => {
		if (!option || !serviceInput) return;
		serviceInput.value = option.dataset.bookServiceId;
		selectedServiceUrl = option.dataset.bookServiceUrl;
		serviceOptions.forEach((item) => {
			const selected = item === option;
			item.classList.toggle('is-selected', selected);
			const label = item.querySelector(':scope > span');
			if (label) label.textContent = selected ? 'Seleccionado' : 'Elegir';
		});
	};

	const updatePlace = () => {
		const selected = wizard.querySelector('input[name="lugar"]:checked');
		const label = selected?.closest('.book-place-option')?.querySelector('strong')?.textContent?.trim();
		const summary = wizard.querySelector('[data-book-summary-place]');
		if (label && summary) summary.textContent = label;
	};
	const show = (target, scroll = true) => {
		if (!steps.has(target)) return;
		const previous = steps.get(current);
		const previousHeight = form?.getBoundingClientRect().height || 0;
		current = target;
		const currentIndex = stepOrder.indexOf(target);
		steps.forEach((step, key) => {
			const active = key === target;
			step.hidden = !active;
			step.classList.remove('is-entering');
			step.classList.toggle('is-active', active);
		});
		const activeStep = steps.get(target);
		if (form && previous && previous !== activeStep && previousHeight) {
			form.style.height = `${previousHeight}px`;
			activeStep.classList.add('is-entering');
			requestAnimationFrame(() => {
				form.style.height = `${activeStep.getBoundingClientRect().height}px`;
				requestAnimationFrame(() => activeStep.classList.remove('is-entering'));
			});
			window.setTimeout(() => { form.style.height = ''; }, 460);
		}
		progress.forEach((item, key) => {
			const index = stepOrder.indexOf(key);
			item.classList.toggle('is-active', key === target);
			item.classList.toggle('is-complete', index < currentIndex);
			item.setAttribute('aria-current', key === target ? 'step' : 'false');
		});
		updatePlace();
		if (scroll) wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
	};
	const canAdvance = (target) => {
		if (target !== 'referencia') return true;
		const hasDate = Boolean(wizard.querySelector('input[name="fecha"]')?.value);
		const hasTime = Boolean(wizard.querySelector('input[name="hora"]')?.value);
		const warning = wizard.querySelector('[data-book-step-warning]');
		if (warning) warning.hidden = hasDate && hasTime;
		return hasDate && hasTime;
	};

	wizard.querySelectorAll('[data-book-next]').forEach((button) => {
		button.addEventListener('click', () => {
			const target = button.dataset.bookNext;
			if (current === 'servicio' && selectedServiceUrl) {
				window.location.assign(selectedServiceUrl);
				return;
			}
			if (canAdvance(target)) show(target);
		});
	});
	wizard.querySelectorAll('[data-book-back]').forEach((button) => {
		button.addEventListener('click', () => show(button.dataset.bookBack));
	});
	categoryTabs.forEach((tab) => {
		tab.addEventListener('click', (event) => {
			if (tab.getAttribute('aria-selected') === 'true') return;
			event.preventDefault();
			const category = tab.dataset.bookCategoryTab;
			categoryTabs.forEach((item) => {
				const active = item === tab;
				item.classList.toggle('is-active', active);
				item.setAttribute('aria-selected', active ? 'true' : 'false');
			});
			categoryPanels.forEach((panel) => {
				const active = panel.dataset.bookCategoryPanel === category;
				panel.hidden = !active;
				if (active) {
					panel.classList.remove('is-entering');
					requestAnimationFrame(() => panel.classList.add('is-entering'));
				}
			});
			const firstService = wizard.querySelector(`[data-book-category-panel="${category}"] [data-book-service-option]`);
			selectService(firstService);
			selectedServiceUrl = tab.dataset.bookCategoryServiceUrl;
		});
	});
	serviceOptions.forEach((option) => {
		option.addEventListener('click', (event) => {
			event.preventDefault();
			selectService(option);
		});
	});
	wizard.querySelectorAll('input[name="lugar"]').forEach((input) => input.addEventListener('change', updatePlace));

	show(current, false);
}

function initGalleryLightbox() {
	const modal = document.getElementById('gallery-modal');
	const image = document.getElementById('gallery-modal-image');
	const caption = document.getElementById('gallery-modal-caption');
	const stage = modal?.querySelector('.gallery-modal-stage');
	const swipeArea = modal?.querySelector('[data-gallery-swipe]') || stage;
	const triggers = Array.from(document.querySelectorAll('[data-gallery-open]'));
	if (!modal || !image || triggers.length === 0) return;

	let index = 0;
	let activeItems = [];
	let lastFocus = null;
	let touchStartX = 0;
	let touchStartY = 0;
	let touchDeltaX = 0;
	let touchDeltaY = 0;
	let swiping = false;
	let lastTapAt = 0;
	let zoomed = false;
	let panX = 0;
	let panY = 0;
	let dragStartX = 0;
	let dragStartY = 0;
	let panOriginX = 0;
	let panOriginY = 0;
	let dragging = false;
	let pointerId = null;
	const zoomScale = () => (modal.dataset.galleryMode === 'catalogo' ? 1.7 : 1.9);

	const applyTransform = (animate = false) => {
		if (!image) return;
		image.style.transition = animate ? 'transform 0.2s ease' : 'none';
		const scale = zoomed ? zoomScale() : 1;
		image.style.transform = `translate3d(${panX}px, ${panY}px, 0) scale(${scale})`;
	};

	const clampPan = () => {
		if (!stage || !zoomed) {
			panX = 0;
			panY = 0;
			return;
		}
		const rect = stage.getBoundingClientRect();
		const maxX = Math.max(40, (rect.width * (zoomScale() - 1)) / 2 + 24);
		const maxY = Math.max(40, (rect.height * (zoomScale() - 1)) / 2 + 24);
		panX = Math.max(-maxX, Math.min(maxX, panX));
		panY = Math.max(-maxY, Math.min(maxY, panY));
	};

	const setZoom = (value) => {
		zoomed = Boolean(value);
		if (!zoomed) {
			panX = 0;
			panY = 0;
		}
		stage?.classList.toggle('is-zoomed', zoomed);
		stage?.classList.remove('is-panning');
		dragging = false;
		pointerId = null;
		applyTransform(true);
	};

	const buildItems = (group) => triggers
		.filter((trigger) => (trigger.getAttribute('data-gallery-group') || 'default') === group)
		.map((trigger) => {
			const img = trigger.querySelector('img');
			return {
				src: trigger.getAttribute('data-gallery-src') || img?.currentSrc || img?.src || '',
				alt: trigger.getAttribute('data-gallery-alt') || img?.alt || trigger.textContent?.trim() || '',
				mode: trigger.getAttribute('data-gallery-mode') || group,
			};
		})
		.filter((item) => item.src);

	const show = (nextIndex) => {
		if (!activeItems.length) return;
		index = (nextIndex + activeItems.length) % activeItems.length;
		const item = activeItems[index];
		image.src = item.src;
		image.alt = item.alt;
		modal.dataset.galleryMode = item.mode || 'default';
		if (caption) caption.textContent = item.alt;
		setZoom(false);
	};

	const open = (group, startIndex) => {
		activeItems = buildItems(group);
		if (!activeItems.length) return;
		lastFocus = document.activeElement;
		show(startIndex);
		modal.hidden = false;
		document.body.classList.add('gallery-open');
		modal.querySelector('.gallery-modal-close')?.focus();
	};

	const close = () => {
		modal.hidden = true;
		document.body.classList.remove('gallery-open');
		modal.removeAttribute('data-gallery-mode');
		image.removeAttribute('src');
		image.style.transform = '';
		image.style.transition = '';
		activeItems = [];
		setZoom(false);
		lastFocus?.focus?.();
	};

	triggers.forEach((trigger) => {
		trigger.addEventListener('click', () => {
			const group = trigger.getAttribute('data-gallery-group') || 'default';
			const groupTriggers = triggers.filter((item) => (item.getAttribute('data-gallery-group') || 'default') === group);
			open(group, Math.max(0, groupTriggers.indexOf(trigger)));
		});
	});
	modal.querySelectorAll('[data-gallery-close]').forEach((el) => el.addEventListener('click', close));
	modal.querySelector('[data-gallery-prev]')?.addEventListener('click', () => show(index - 1));
	modal.querySelector('[data-gallery-next]')?.addEventListener('click', () => show(index + 1));
	document.addEventListener('keydown', (event) => {
		if (modal.hidden) return;
		if (event.key === 'Escape') {
			if (zoomed) setZoom(false);
			else close();
		}
		if (event.key === 'ArrowLeft' && !zoomed) show(index - 1);
		if (event.key === 'ArrowRight' && !zoomed) show(index + 1);
	});

	stage?.addEventListener('dblclick', (event) => {
		event.preventDefault();
		setZoom(!zoomed);
	});

	const beginPan = (clientX, clientY, id = null) => {
		if (!zoomed) return false;
		dragging = true;
		pointerId = id;
		dragStartX = clientX;
		dragStartY = clientY;
		panOriginX = panX;
		panOriginY = panY;
		stage?.classList.add('is-panning');
		return true;
	};

	const movePan = (clientX, clientY) => {
		if (!dragging || !zoomed) return;
		panX = panOriginX + (clientX - dragStartX);
		panY = panOriginY + (clientY - dragStartY);
		clampPan();
		applyTransform(false);
	};

	const endPan = () => {
		dragging = false;
		pointerId = null;
		stage?.classList.remove('is-panning');
	};

	stage?.addEventListener('pointerdown', (event) => {
		if (!zoomed || event.button !== 0 || event.pointerType === 'touch') return;
		event.preventDefault();
		stage.setPointerCapture?.(event.pointerId);
		beginPan(event.clientX, event.clientY, event.pointerId);
	});
	stage?.addEventListener('pointermove', (event) => {
		if (!dragging || event.pointerType === 'touch') return;
		if (pointerId !== null && event.pointerId !== pointerId) return;
		event.preventDefault();
		movePan(event.clientX, event.clientY);
	});
	stage?.addEventListener('pointerup', (event) => {
		if (event.pointerType === 'touch') return;
		endPan();
	});
	stage?.addEventListener('pointercancel', (event) => {
		if (event.pointerType === 'touch') return;
		endPan();
	});

	const onTouchStart = (event) => {
		if (modal.hidden || !event.touches?.[0]) return;
		const touch = event.touches[0];
		touchStartX = touch.clientX;
		touchStartY = touch.clientY;
		touchDeltaX = 0;
		touchDeltaY = 0;
		swiping = true;
		if (zoomed) beginPan(touch.clientX, touch.clientY);
	};

	const onTouchMove = (event) => {
		if (!swiping || !event.touches?.[0]) return;
		const touch = event.touches[0];
		touchDeltaX = touch.clientX - touchStartX;
		touchDeltaY = touch.clientY - touchStartY;
		if (zoomed) {
			event.preventDefault();
			movePan(touch.clientX, touch.clientY);
			return;
		}
		if (Math.abs(touchDeltaX) > Math.abs(touchDeltaY) && Math.abs(touchDeltaX) > 12) {
			event.preventDefault();
		}
	};

	const onTouchEnd = (event) => {
		if (!swiping) return;
		swiping = false;
		const moved = Math.hypot(touchDeltaX, touchDeltaY) >= 48;
		if (zoomed) {
			endPan();
			if (!moved) {
				const now = Date.now();
				if (now - lastTapAt < 320) {
					event.preventDefault();
					setZoom(false);
					lastTapAt = 0;
				} else {
					lastTapAt = now;
				}
			} else {
				lastTapAt = 0;
			}
			touchDeltaX = 0;
			touchDeltaY = 0;
			return;
		}

		if (moved) {
			if (Math.abs(touchDeltaX) >= Math.abs(touchDeltaY)) {
				if (touchDeltaX < 0) show(index + 1);
				else show(index - 1);
			}
			touchDeltaX = 0;
			touchDeltaY = 0;
			lastTapAt = 0;
			return;
		}

		const now = Date.now();
		if (now - lastTapAt < 320) {
			event.preventDefault();
			setZoom(true);
			lastTapAt = 0;
		} else {
			lastTapAt = now;
		}
		touchDeltaX = 0;
		touchDeltaY = 0;
	};

	swipeArea?.addEventListener('touchstart', onTouchStart, { passive: true });
	swipeArea?.addEventListener('touchmove', onTouchMove, { passive: false });
	swipeArea?.addEventListener('touchend', onTouchEnd);
	swipeArea?.addEventListener('touchcancel', () => {
		swiping = false;
		touchDeltaX = 0;
		touchDeltaY = 0;
		endPan();
	});
}

function initConfirmModal() {
	const modal = document.getElementById('confirm-modal');
	const messageEl = document.getElementById('confirm-modal-message');
	const titleEl = document.getElementById('confirm-modal-title');
	const okBtn = document.getElementById('confirm-modal-ok');
	if (!modal || !messageEl || !okBtn) return;

	let pendingForm = null;
	let lastFocus = null;

	const close = () => {
		modal.hidden = true;
		document.body.classList.remove('confirm-open');
		pendingForm = null;
		lastFocus?.focus?.();
		lastFocus = null;
	};

	const open = (form, message, title) => {
		pendingForm = form;
		lastFocus = document.activeElement;
		titleEl.textContent = title || 'Confirmar';
		messageEl.textContent = message;
		okBtn.textContent = title || 'Confirmar';
		modal.hidden = false;
		document.body.classList.add('confirm-open');
		okBtn.focus();
	};

	document.addEventListener('submit', (event) => {
		const form = event.target;
		if (!(form instanceof HTMLFormElement)) return;
		if (form.dataset.confirmAccepted === '1') {
			delete form.dataset.confirmAccepted;
			return;
		}
		const message = form.getAttribute('data-confirm');
		if (!message) return;
		event.preventDefault();
		event.stopPropagation();
		const title = form.getAttribute('data-confirm-title') || (
			/cancel/i.test(message) ? 'Cancelar' : 'Eliminar'
		);
		open(form, message, title);
	}, true);

	okBtn.addEventListener('click', () => {
		const form = pendingForm;
		if (!form) {
			close();
			return;
		}
		form.dataset.confirmAccepted = '1';
		close();
		if (typeof form.requestSubmit === 'function') {
			form.requestSubmit();
		} else {
			form.submit();
		}
	});

	modal.querySelectorAll('[data-confirm-dismiss]').forEach((el) => {
		el.addEventListener('click', close);
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && !modal.hidden) close();
	});
}

function initNoticeModal() {
	const modal = document.getElementById('notice-modal');
	if (!modal || modal.dataset.bound === '1') return;
	modal.dataset.bound = '1';

	const close = () => {
		modal.hidden = true;
		document.body.classList.remove('confirm-open');
	};

	document.body.classList.add('confirm-open');
	modal.querySelector('[data-notice-dismiss].btn')?.focus();

	modal.querySelectorAll('[data-notice-dismiss]').forEach((el) => {
		el.addEventListener('click', close);
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && !modal.hidden) {
			event.preventDefault();
			close();
		}
	});
}

function initRoleDefaultNavigation() {
	const back = document.getElementById('header-back');
	const roleHome = back?.getAttribute('data-role-home') || '/';
	let homePath = '/';
	try {
		homePath = new URL(roleHome, window.location.origin).pathname;
	} catch {
		homePath = '/';
	}

	const isAtRoleHome = () => {
		const path = window.location.pathname;
		if (homePath === '/') return path === '/';
		return path === homePath || path.startsWith(`${homePath}/`);
	};

	const goRoleHome = (replace = false) => {
		if (isAtRoleHome() && !window.location.search) return;
		if (replace) window.location.replace(roleHome);
		else window.location.assign(roleHome);
	};

	// Botón del menú: siempre a la página por defecto del rol.
	back?.addEventListener('click', (event) => {
		event.preventDefault();
		goRoleHome(false);
	});

	// Varios "atrás" del navegador en poco tiempo → home del rol.
	const key = 'hhs_back_burst';
	const nav = performance.getEntriesByType?.('navigation')?.[0];
	const isBackNav = nav?.type === 'back_forward'
		|| (window.performance?.navigation?.type === 2);
	if (isBackNav) {
		const now = Date.now();
		let burst = { n: 0, t: 0 };
		try {
			burst = JSON.parse(sessionStorage.getItem(key) || '{"n":0,"t":0}');
		} catch {}
		if (now - (burst.t || 0) < 2200) burst.n = (burst.n || 0) + 1;
		else burst.n = 1;
		burst.t = now;
		sessionStorage.setItem(key, JSON.stringify(burst));

		const auth = document.body.dataset.auth;
		const path = window.location.pathname;
		const badForUser = auth === 'user' && (path === '/login' || path.startsWith('/contrasena'));
		const badForGuest = auth === 'guest' && (
			path === '/bienvenida'
			|| path.startsWith('/admin')
			|| path.startsWith('/mi-perfil')
			|| path.startsWith('/mis-reservas')
			|| path.startsWith('/reservar')
			|| path.startsWith('/simulador')
		);

		if (burst.n >= 2 || badForUser || badForGuest) {
			sessionStorage.removeItem(key);
			goRoleHome(true);
		}
	} else {
		sessionStorage.removeItem(key);
	}
}

function csrfToken() {
	return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function syncCsrfTokens() {
	const token = csrfToken();
	if (!token) return;
	document.querySelectorAll('input[name="_token"]').forEach((input) => {
		input.value = token;
	});
}

function initSessionFreshness() {
	window.addEventListener('pageshow', (event) => {
		if (event.persisted) {
			window.location.reload();
			return;
		}
		syncCsrfTokens();
	});
	syncCsrfTokens();
}

function initPasswordToggles() {
	const eyeOpen = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 5c-5 0-9.3 3.1-11 7 1.7 3.9 6 7 11 7s9.3-3.1 11-7c-1.7-3.9-6-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-2.5A2.5 2.5 0 1 0 12 9a2.5 2.5 0 0 0 0 5z"/></svg>';
	const eyeOff = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3.1 4.5 4.5 3.1 20.9 19.5 19.5 20.9l-2.4-2.4A12.6 12.6 0 0 1 12 19c-5 0-9.3-3.1-11-7a13.2 13.2 0 0 1 4.5-5.1L3.1 4.5zM12 7a5 5 0 0 1 4.9 4.1l-1.6-1.6A2.5 2.5 0 0 0 12 9a2.5 2.5 0 0 0-.9 4.8L9.5 12A5 5 0 0 1 12 7zm9.6 2.3A13 13 0 0 1 23 12c-1.7 3.9-6 7-11 7-.9 0-1.7-.1-2.5-.3l1.7-1.7c.3 0 .5.1.8.1a5 5 0 0 0 5-5c0-.3 0-.5-.1-.8l3.7-3.7z"/></svg>';

	document.querySelectorAll('input[type="password"]').forEach((input) => {
		if (input.dataset.passwordToggleBound === '1') return;
		input.dataset.passwordToggleBound = '1';

		let wrap = input.closest('.password-field');
		if (!wrap) {
			wrap = document.createElement('div');
			wrap.className = 'password-field';
			input.parentNode.insertBefore(wrap, input);
			wrap.appendChild(input);
		}

		if (wrap.querySelector('.password-toggle')) return;

		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'password-toggle';
		button.setAttribute('aria-label', 'Mostrar contraseña');
		button.setAttribute('aria-pressed', 'false');
		button.innerHTML = eyeOpen;

		button.addEventListener('click', () => {
			const show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			button.setAttribute('aria-pressed', show ? 'true' : 'false');
			button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
			button.innerHTML = show ? eyeOff : eyeOpen;
			input.focus({ preventScroll: true });
		});

		wrap.appendChild(button);
	});
}

initReveal();
initNavigation();
initLoader();
initSessionFreshness();
initPasswordToggles();
initCarousels();
initPanelsAndFilters();
initPhotoPicker();
initAvailability();
initHours();
initSmallInteractions();
initAiStudio();
initBookingWizard();
initGalleryLightbox();
initConfirmModal();
initNoticeModal();
