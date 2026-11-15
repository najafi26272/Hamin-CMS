/* جاوااسکریپت سایت عمومی هامین (بدون وابستگی) */
(function () {
    'use strict';

    // ---------- منوی موبایل و زیرمنوها ----------
    const nav = document.querySelector('.site-nav');
    const toggle = document.querySelector('.nav-toggle');

    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open);
        });
    }

    document.querySelectorAll('.has-dropdown > button').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const item = btn.parentElement;
            const open = !item.classList.contains('open');
            document.querySelectorAll('.has-dropdown.open').forEach((el) => el.classList.remove('open'));
            item.classList.toggle('open', open);
            btn.setAttribute('aria-expanded', open);
        });
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.has-dropdown')) {
            document.querySelectorAll('.has-dropdown.open').forEach((el) => el.classList.remove('open'));
        }
    });

    // ---------- اسلایدر ----------
    document.querySelectorAll('[data-slider]').forEach((slider) => {
        const slides = slider.querySelectorAll('.slide');
        const dots = slider.querySelectorAll('.slider-dots button');
        if (slides.length < 2) return;

        let index = 0;
        let timer = null;

        const go = (i) => {
            index = (i + slides.length) % slides.length;
            slides.forEach((s, n) => s.classList.toggle('active', n === index));
            dots.forEach((d, n) => d.classList.toggle('active', n === index));
        };
        const start = () => { stop(); timer = setInterval(() => go(index + 1), 6000); };
        const stop = () => timer && clearInterval(timer);

        dots.forEach((dot, n) => dot.addEventListener('click', () => { go(n); start(); }));
        slider.querySelector('.slider-arrow.next')?.addEventListener('click', () => { go(index + 1); start(); });
        slider.querySelector('.slider-arrow.prev')?.addEventListener('click', () => { go(index - 1); start(); });
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);

        // کشیدن با انگشت در موبایل (راست‌چین: کشیدن به راست = بعدی)
        let x0 = null;
        slider.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; stop(); }, {passive: true});
        slider.addEventListener('touchend', (e) => {
            if (x0 === null) return;
            const dx = e.changedTouches[0].clientX - x0;
            if (Math.abs(dx) > 40) go(index + (dx > 0 ? 1 : -1));
            x0 = null;
            start();
        });

        start();
    });

    // ---------- کارت‌های صوتی ----------
    const format = (s) => {
        if (!isFinite(s)) return '0:00';
        const m = Math.floor(s / 60);
        return m + ':' + String(Math.floor(s % 60)).padStart(2, '0');
    };

    const cards = document.querySelectorAll('[data-audio-card]');

    cards.forEach((card) => {
        const audio = card.querySelector('audio');
        const btn = card.querySelector('.audio-play');
        const wave = card.querySelector('.wave');
        const time = card.querySelector('.audio-time');
        const current = card.querySelector('.audio-current'); // فقط در پخش‌کننده صفحه مطلب
        if (!audio || !btn) return;

        // در پخش‌کننده بزرگ: زمان سپری‌شده جدا و مدت کل ثابت نمایش داده می‌شود
        if (current) {
            audio.addEventListener('timeupdate', () => (current.textContent = format(audio.currentTime)));
        }

        btn.addEventListener('click', () => {
            if (audio.paused) {
                // فقط یک صدا در هر لحظه پخش شود
                cards.forEach((other) => {
                    const a = other.querySelector('audio');
                    if (a && a !== audio && !a.paused) a.pause();
                });
                audio.play();
            } else {
                audio.pause();
            }
        });

        audio.addEventListener('play', () => card.classList.add('playing'));
        audio.addEventListener('pause', () => card.classList.remove('playing'));
        audio.addEventListener('loadedmetadata', () => time && (time.textContent = format(audio.duration)));
        audio.addEventListener('timeupdate', () => {
            const p = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;
            wave && wave.style.setProperty('--progress', p + '%');
            if (time && !current) time.textContent = format(audio.paused ? audio.duration : audio.currentTime);
        });
        audio.addEventListener('ended', () => wave && wave.style.setProperty('--progress', '0%'));

        // جابه‌جایی با کلیک روی موج (موج چپ‌به‌راست است)
        wave?.addEventListener('click', (e) => {
            if (!audio.duration) return;
            const r = wave.getBoundingClientRect();
            audio.currentTime = ((e.clientX - r.left) / r.width) * audio.duration;
        });
    });

    // ---------- مدت صوت یا ویدیو در «مشخصات اثر» ----------
    const durationBox = document.querySelector('[data-media-duration]');
    const mainMedia = document.querySelector('.post-article audio, .post-article video');

    if (durationBox && mainMedia) {
        const show = () => {
            const s = Math.round(mainMedia.duration);
            if (!isFinite(s)) return;
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            durationBox.textContent = (h ? h + ':' + String(m).padStart(2, '0') : m) + ':' + String(s % 60).padStart(2, '0');
        };
        mainMedia.readyState >= 1 ? show() : mainMedia.addEventListener('loadedmetadata', show);
    }

    // ---------- کپی لینک ----------
    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const label = btn.querySelector('span');
            try {
                await navigator.clipboard.writeText(btn.dataset.copy);
            } catch (e) {
                window.prompt('لینک را کپی کنید:', btn.dataset.copy);
                return;
            }
            btn.classList.add('copied');
            if (label) label.textContent = 'کپی شد';
            setTimeout(() => {
                btn.classList.remove('copied');
                if (label) label.textContent = 'کپی لینک';
            }, 2000);
        });
    });
})();
