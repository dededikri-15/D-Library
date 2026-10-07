import './bootstrap';

function uiMessage(key, fallback) {
    return document.documentElement.dataset[`ui${key}`] || fallback;
}

/*
 * Frontend Vanilla JS.
 *
 * Semua perilaku ditulis dengan atribut data- di Blade, bukan dengan
 *.querySelector per halaman. Jadi satu berkas ini cukup untuk semua view:
 * menambah elemen baru cukup dengan menulis data-toast / data-nav-toggle
 * di Blade, tanpa menyentuh JS.
 */

/**
 * Menu hamburger untuk layar kecil. Ikon memakai SVG, bukan karakter Unicode
 * seperti "☰": glyph itu tampil berbeda di tiap sistem operasi dan sering
 * tidak sejajar dengan teks di sebelahnya.
 */
function initNavToggle() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const menu = document.querySelector('[data-nav-menu]');
    if (!toggle || !menu) return;

    const ICON_OPEN =
        '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>';
    const ICON_CLOSE = '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>';

    const setIcon = (isOpen) => {
        const icon = toggle.querySelector('[data-nav-icon]');
        if (icon) icon.innerHTML = isOpen ? ICON_CLOSE : ICON_OPEN;
    };

    toggle.addEventListener('click', () => {
        const isOpen = !menu.hidden;
        menu.hidden = isOpen;
        toggle.setAttribute('aria-expanded', String(!isOpen));
        setIcon(!isOpen);
    });

    // Menu ikut tertutup saat layar kembali ke desktop.
    const desktop = window.matchMedia('(min-width: 1024px)');
    desktop.addEventListener('change', (event) => {
        if (!event.matches) return;
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        setIcon(false);
    });
}

/*
 * Dark / light mode.
 *
 * Preferensi disimpan di localStorage supaya pilihan user bertahan setelah
 * pindah halaman. Kalau belum pernah memilih, ikut preferensi sistem operasi.
 *
 * Class `.dark` pada <html> sudah dipasang oleh script inline di layout
 * sebelum CSS dimuat. Fungsi di sini hanya menjaga sinkronisasi setelah
 * halaman selesai render, bukan memuat CSS.
 */

const THEME_KEY = 'perpustakaan-theme';

const ICON_SUN =
    '<circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-linejoin="round" ' +
    'd="M12 2.5v2m0 15v2m9.5-9.5h-2m-15 0h-2m16.19-6.69-1.42 1.42M6.73 17.27l-1.42 1.42' +
    'm12.36 0-1.42 1.42m9.52-3.19-1.42-1.42M6.73 6.73 5.31 5.31"/>';
const ICON_MOON = '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 15.6A9.72 9.72 0 0 1 8.4 2.25a9.72 9.72 0 1 0 13.35 13.35Z"/>';

function readStoredTheme() {
    try {
        const value = localStorage.getItem(THEME_KEY);
        return value === 'dark' || value === 'light' ? value : null;
    } catch (e) {
        // Mode privat / storage diblokir: lanjut ke preferensi sistem.
        return null;
    }
}

function storeTheme(theme) {
    try {
        localStorage.setItem(THEME_KEY, theme);
    } catch (e) {
        // Tidak bisa menyimpan. Tema tetap berlaku di halaman ini saja.
    }
}

function prefersDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.dataset.theme = theme;
}

function syncThemeToggles(theme) {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        // Ikon ditulis lewat CSS, bukan JS, supaya tetap benar saat JS dimatikan.
        // Yang diubah di sini hanya nama tombol untuk pembaca layar.
        const label =
            theme === "dark"
                ? button.dataset.lightLabel
                : button.dataset.darkLabel;
        if (label) {
            button.setAttribute("aria-label", label);
            button.setAttribute("title", label);
        }
        button.dataset.themeState = theme;
    });
}

function initTheme() {
    const current = readStoredTheme() ?? (prefersDark() ? 'dark' : 'light');

    // Menyamakan ulang dengan hasil bacaan: script inline sudah melakukan hal
    // sama, tapi diulang agar halaman yang dirender tanpa layout ini (mis. view
    // yang diparsialkan) tetap konsisten.
    applyTheme(current);
    syncThemeToggles(current);

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
            applyTheme(next);
            storeTheme(next);
            syncThemeToggles(next);
        });
    });

    // Ikuti perubahan setelan sistem selama user belum memilih sendiri.
    // Kalau user sudah menekan tombol, pilihan mereka yang menang.
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    system.addEventListener('change', (event) => {
        if (readStoredTheme() !== null) return;
        const next = event.matches ? 'dark' : 'light';
        applyTheme(next);
        syncThemeToggles(next);
    });
}

/**
 * Toast (Task 14.5).
 *
 * Notifikasi melayang di pojok. Dua sumbernya:
 * 1. Flash message `session('status')` yang dirender Blade di dalam wilayah.
 * 2. `window.toast.success(...)` dan sejenisnya, untuk hasil aksi AJAX
 *    (dipakai di Task 14.7).
 *
 * Toast tidak pernah ditulis sebagai string HTML di sini. `app.js` mengklon
 * isi `<template>` yang Blade render dari `<x-alert>`, lalu hanya mengisi teksnya.
 * Kalau markup-nya ditulis ulang di JavaScript, warna status dan ikon cepat
 * melenceng dari versi Blade tanpa error apa pun.
 *
 * Atribut:
 * - [data-toast-region] wilayah penampung
 * - [data-toast-template="<variant>"] cetakan untuk tiap varian
 * - [data-toast] elemen toast
 * - [data-toast-close] tombol tutup
 * - data-auto-dismiss + opsional data-toast-duration="<ms>"
 */
const TOAST_DURATION = 5000;
const TOAST_LEAVE_FALLBACK = 400;

function initToasts() {
    const region = document.querySelector('[data-toast-region]');
    const max = Math.max(Number(region?.dataset.toastMax) || 0, 1);

    const templates = new Map(
        Array.from(document.querySelectorAll('[data-toast-template]')).map((template) => [
            template.dataset.toastTemplate,
            template.content,
        ])
    );

    const dismiss = (toast) => {
        if (!toast.isConnected || toast.dataset.toastLeaving) return;

        toast.dataset.toastLeaving = '1';
        toast.classList.add('toast-leaving');

        // `animationend` adalah jalur utama; setTimeout hanya cadangan untuk
        // browser yang tidak menjalankan animasi sama sekali.
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
        window.setTimeout(() => toast.remove(), TOAST_LEAVE_FALLBACK);
    };

    const schedule = (toast) => {
        if (!toast.hasAttribute('data-auto-dismiss')) return;

        const duration = Number(toast.dataset.toastDuration || TOAST_DURATION);
        if (!Number.isFinite(duration) || duration <= 0) return;

        let timer = null;
        const start = () => {
            timer = window.setTimeout(() => dismiss(toast), duration);
        };
        const stop = () => {
            window.clearTimeout(timer);
            timer = null;
        };

        start();

        // Notifikasi yang sedang dibaca atau disorot mouse tidak boleh hilang
        // di tengah dibaca.
        toast.addEventListener('mouseenter', stop);
        toast.addEventListener('mouseleave', start);
        toast.addEventListener('focusin', stop);
        toast.addEventListener('focusout', start);
    };

    const attach = (toast) => {
        toast.querySelector('[data-toast-close]')?.addEventListener('click', () => dismiss(toast));
        schedule(toast);
    };

    // Toast yang paling lama dilepas duluan supaya wilayah tidak pernah
    // memanjang melewati layar.
    const trim = () => {
        if (!region) return;

        const active = Array.from(region.querySelectorAll(':scope > [data-toast]'));
        active.slice(0, Math.max(active.length - max, 0)).forEach(dismiss);
    };

    document.querySelectorAll('[data-toast]').forEach(attach);
    trim();

    const push = (variant, message, options = {}) => {
        if (!region) return null;

        const template = templates.get(variant) ?? templates.get('info');
        if (!template) return null;

        // `template.content`, bukan elemen `<template>`-nya: isi template
        // berada di document fragment terpisah, jadi `querySelector` pada
        // elemen `<template>` tidak akan pernah menemukannya.
        const toast = template.cloneNode(true).querySelector('[data-toast]');
        if (!toast) return null;
        const body = toast.querySelector('[data-toast-body]');
        // `textContent`, bukan `innerHTML`: pesan bisa berisi judul buku atau
        // nama pengguna yang isinya sepenuhnya dikendalikan user.
        if (body) body.textContent = message;

        if (options.duration !== undefined) toast.dataset.toastDuration = options.duration;
        if (!options.autoDismiss) toast.removeAttribute('data-auto-dismiss');

        region.append(toast);
        attach(toast);
        trim();

        return toast;
    };

    // Dipakai view atau modul JS lain, misalnya setelah fetch di Task 14.7.
    window.toast = {
        success: (message, options) => push('success', message, options),
        error: (message, options) => push('error', message, options),
        warning: (message, options) => push('warning', message, options),
        info: (message, options) => push('info', message, options),
    };
}

/**
 * Konfirmasi tindakan (Task 14.4) + cegah submit ganda.
 *
 * Dua hal yang sama-sama terjadi pada event `submit`:
 * 1. `form[data-confirm]` — minta izin dulu lewat satu dialog konfirmasi.
 * 2. `form[data-submit-once]` — matikan tombol setelah terkirim, supaya tidak
 *    menghasilkan dua record di database.
 *
 * Keduanya sengaja ditangani di SATU listener delegasi. Kalau dipisah jadi dua
 * listener, listener submit-once tetap jalan walaupun konfirmasi sudah
 * dibatalkan, dan tombolnya berakhir mati dengan tulisan "Memproses..."
 * padahal form-nya tidak pernah terkirim.
 *
 * Dialog konfirmasi hanya satu untuk seluruh halaman (ada di layout), bukan
 * satu per tombol, karena isinya selalu sama bentuknya: judul + pertanyaan.
 *
 * Kalau JavaScript mati, `data-confirm` diabaikan dan form terkirim langsung —
 * sama seperti sebelumnya, dan sama seperti bentuk yang dipakai proyek mana pun
 * yang mengandalkan JS. Pengaman sesungguhnya tetap ada di server: `authorize()`
 * dan validasi Form Request.
 */
function initSubmitGuards() {
    const modal = document.getElementById('konfirmasi-tindakan');
    const message = modal?.querySelector('[data-confirm-message]') ?? null;
    const heading = modal?.querySelector('[data-modal-title]') ?? null;
    const accept = modal?.querySelector('[data-confirm-accept]') ?? null;

    // Form yang sedang menunggu jawaban. Disimpan di variabel lokal, bukan di
    // atribut DOM, supaya tidak bisa dibaca atau diubah dari luar.
    let pending = null;

    if (modal && message && accept) {
        accept.addEventListener('click', () => {
            const form = pending;
            pending = null;
            modal.close();

            if (!form) return;

            // Benderanya dibaca sekali oleh listener di bawah lalu dibuang.
            // Tanpa itu, `requestSubmit()` akan membuka dialog lagi.
            form.dataset.confirmed = '1';
            form.requestSubmit();
        });

        // Batal, Escape, atau klik area gelap = batal.
        modal.addEventListener('close', () => {
            pending = null;
        });
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.dataset.confirm && form.dataset.confirmed !== '1') {
            event.preventDefault();

            if (modal && message && accept) {
                pending = form;

                // `textContent`, bukan `innerHTML`: pesannya disusun dari data
                // database (judul buku, nama pengguna) yang isinya bebas.
                message.textContent = form.dataset.confirm;
                if (heading && form.dataset.confirmTitle) {
                    heading.textContent = form.dataset.confirmTitle;
                }

                // Menutup dulu menjaga `showModal()` tidak melempar error kalau
                // somehow dialognya masih terbuka.
                if (modal.open) modal.close();
                modal.showModal();
            } else if (!window.confirm(form.dataset.confirm)) {
                // Dialog tidak ada (mis. view tanpa layout). Tetap tanya, supaya
                // aksi destruktif tidak pernah berjalan tanpa persetujuan.
                return;
            } else {
                form.dataset.confirmed = '1';
                form.requestSubmit();
            }

            return;
        }

        delete form.dataset.confirmed;

        if (!form.hasAttribute('data-submit-once')) return;

        const button = form.querySelector('[type="submit"]');
        if (!button || button.disabled) return;

        button.disabled = true;
        button.textContent = 'Memproses...';
    });
}

/**
 * AJAX form submission (Task 14.7).
 *
 * Hanya form yang menandai `data-ajax` yang diambil alih. Form lain tetap
 * dikirim seperti biasa — reload halaman masih cara yang benar untuk
 * "simpan buku", "hapus peminjaman", dan sejenisnya, karena hasil aksinya
 * mengubah isi halaman secara mendasar.
 *
 * Yang diubah di sini sengaja sempit: aksi kecil yang hanya mengubah
 * beberapa hal di layar. Memuat ulang seluruh halaman detail buku untuk
 * menambahkan satu baris favorit tidak sepadan dengan kehilangan posisi
 * scroll, teks yang sedang dibaca, dan ratusan KB transfer.
 *
 * Empat hal yang harus benar, dan tiga di antaranya tidak terlihat dari kode
 * yang terlihat berhasil:
 *
 * 1. Listener-nya DELEGATED ke `document`, bukan ditempel ke tiap form.
 *    Urutan dua listener di elemen yang sama bisa diatur; urutan listener di
 *    `form` dan listener di `document` sama sekali tidak bisa. Event
 *    `submit` selalu visiting target-nya lebih dulu, jadi listener di `form`
 *    berjalan mendahului listener konfirmasi di `document` apa pun urutannya
 *    dipanggil. Kalau listener AJAX menempel ke form, form yang punya
 *    `data-ajax` sekaligus `data-confirm` akan terkirim sebelum dialog pernah
 *    muncul. Dua listener di `document` yang sama urutannya DIJAMIN mengikuti
 *    urutan pendaftaran — itu yang membuat `initSubmitGuards()` harus dipanggil
 *    lebih dulu.
 *
 * 2. Fallback native hanya untuk error JARING, dan hanya kalau form memintanya.
 *    Koneksi bisa putus SETELAH server selesai memproses request. Kalau
 *    `catch` juga menangkap kegagalan yang terjadi SESUDAH server menjawab —
 *    JSON yang tidak bisa di-parse, `applyAjaxResult` yang melempar — lalu
 *    memanggil `form.submit()`, user mengirim aksi yang sama dua kali. Jadi flag
 *    `reachedServer` di bawah: fetch yang sudah dijawab server tidak pernah
 *    menyentuh fallback. Fallback juga gated `data-ajax-fallback` — favorit
 *    menandainya karena toggle-nya idempoten (menambah dua kali tetap satu
 *    baris, menghapus yang tidak ada tetap tidak ada), sedangkan form lain
 *    yang belum diaudit tidak boleh diam-diam mengirim ulang request.
 *
 * 3. 419 = token CSRF basi. Ini bukan "coba lagi" — session sudah berganti,
 *    jadi token yang sama akan ditolak terus. Satu-satunya jalan keluar adalah
 *    reload, dan itu harus disampaikan, bukan diganti spinner yang berputar
 *    selamanya.
 *
 * 4. Form yang sama melakukan dua aksi berbeda. `form[data-favorite-toggle]`
 *    menukar `action` DAN field `_method` (POST -> DELETE) sekaligus. Kalau
 *    hanya `action` yang ditukar, klik kedua akan mengirim POST ke endpoint
 *    hapus, dan server akan membalas "route tidak cocok".
 *
 * 5. Tombol harus dinonaktifkan selama request. Tanpa itu, klik ganda mengirim
 *    dua request; untuk favorit yang idempoten tidak merusak apa pun, tapi
 *    untuk aksi apa pun yang lain akan jadi dobel.
 */
function initAjaxForms() {
    // Delegated ke `document` dan didaftarkan SETELAH `initSubmitGuards()`.
    // Dua listener di `document` yang sama dijamin jalan sesuai urutan
    // pendaftaran, jadi konfirmasi selalu sempat membatalkan lebih dulu.
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.matches('form[data-ajax]')) return;

        // `data-confirm` (Task 14.4) sudah `preventDefault()` di listener
        // yang lebih dulu. Kalau iya, jangan kirim apa pun — form akan
        // di-submit ulang lewat `requestSubmit()` setelah dialog ditutup, dan
        // saat itu benderanya sudah terpasang.
        if (event.defaultPrevented) return;

        event.preventDefault();

        const button = form.querySelector('[type="submit"]');

        // Label tombol SENGAJA tidak diganti jadi "Memproses..." di sini.
        // Aksi favorit selesai dalam hitungan milidetik, jadi tulisan itu
        // berkedip sebelum hilang — dan berkedip tanpa alasan karena
        // request-nya sukses. Yang tetap: `disabled` + `aria-busy`, supaya
        // teknologi bantu tahu tombolnya sedang tidak bisa diklik.
        if (button) {
            button.disabled = true;
            setBusy(button, true);
        }

        // Kapanpun server sudah menjawab, semua error sesudah titik ini adalah
        // masalah kita (parsing, render) atau masalah user (validasi) — bukan
        // bukti request-nya gagal terkirim. Fallback native tidak boleh jalan
        // di kedua kasus itu.
        let reachedServer = false;

        window
            .ajax(form.action, {
                method: form.method || 'POST',
                // `FormData` diambil dari form sendiri supaya field
                // `_token` dan `_method` ikut terkirim tanpa ditulis ulang
                // di sini. Kalau daftar field-nya ditulis manual, field
                // baru yang ditambahkan nanti akan terlupa — dan karena
                // `_token` ikut hilang, servernya membalas 419.
                body: new FormData(form),
            })
            .then(async (response) => {
                reachedServer = true;

                // 419: session/CSRF tidak cocok. Retry dengan token yang
                // sama pasti gagal juga, jadi reload adalah satu-satunya
                // penyelesainya.
                if (response.status === 419) {
                    window.toast?.error(
                        uiMessage(
                            "SessionExpired",
                            "Your session expired. Reload the page.",
                        ),
                    );
                    return;
                }

                if (response.status === 401) {
                    window.toast?.error(
                        uiMessage(
                            "LoginRequired",
                            "You need to sign in first.",
                        ),
                    );
                    return;
                }

                if (!response.ok) {
                    const detail = await response
                        .json()
                        .then((body) => body.message || firstErrorMessage(body.errors))
                        .catch(() => null);

                    window.toast?.error(
                        detail ||
                            uiMessage(
                                "ActionFailed",
                                "The action failed. Please try again.",
                            ),
                    );
                    return;
                }

                const body = await response.json();

                applyAjaxResult(form, body);
                window.toast?.success(
                    body.message ?? uiMessage("Saved", "Changes saved."),
                );
            })
            .catch(() => {
                if (reachedServer) {
                    // Server sudah sempat memproses request ini; mengirim ulang
                    // berisiko jadi aksi ganda. User hanya perlu diberi tahu dan
                    // diberi kesempatan mencoba lagi.
                    window.toast?.error(
                        uiMessage(
                            "ResponseUnreadable",
                            "The server response could not be read. Try again or reload the page.",
                        ),
                    );
                    return;
                }

                if (!form.hasAttribute('data-ajax-fallback')) {
                    window.toast?.error(
                        uiMessage(
                            "NetworkError",
                            "Connection problem. The form was not submitted.",
                        ),
                    );
                    return;
                }

                // Offline, DNS gagal, atau koneksi terputus sebelum response.
                //
                // Kirim ulang lewat jalur native. `submit()` — bukan
                // `requestSubmit()` — dipakai SADARIAN: `requestSubmit()`
                // memicukan event submit lagi, yang akan membuat listener
                // `data-confirm` (Task 14.4) membuka dialog kedua di atas
                // fallback yang sedang berjalan. `submit()` melewati event,
                // jadi browser mengirim form apa adanya.
                form.dataset.ajaxFallback = '1';
                window.toast?.warning(
                    uiMessage(
                        "NetworkFallback",
                        "Connection problem. Submitting without AJAX...",
                    ),
                );
                form.submit();
            })
            .finally(() => {
                // Tombol dinyalakan kembali supaya user bisa mencoba lagi
                // kalau request-nya memang gagal dan form tidak terkirim.
                if (button && !form.dataset.ajaxFallback) {
                    button.disabled = false;
                    setBusy(button, false);
                }
            });
    });
}

/**
 * Pesan pertama dari array `errors` Laravel untuk ditampilkan sebagai toast.
 *
 * `errors` berbentuk `{"email": ["Email wajib diisi."]}`, dan `toast.error()`
 * butuh string. Tanpa ini yang tampil adalah `[object Object]`, yang tidak
 * informatif dan tidak enak dibaca.
 *
 * @param  mixed  $errors
 */
function firstErrorMessage(errors) {
    if (Array.isArray(errors)) return errors[0] ?? null;
    if (errors && typeof errors === 'object') {
        const first = Object.values(errors)[0];
        if (Array.isArray(first)) return first[0] ?? null;
        if (typeof first === 'string') return first;
    }
    return null;
}

/**
 * Terapkan hasil aksi AJAX ke bagian halaman yang berubah.
 *
 * Dipisah dari pengiriman request supaya "apa yang berubah kalau sukses"
 * bisa dibaca tanpa harus menelusuri rantai `.then()` yang panjang.
 */
function applyAjaxResult(form, body) {
    if (form.matches('[data-favorite-toggle]')) {
        applyFavoriteResult(form, body);
    }
}

/**
 * Toggling favorit harus menukar action, method, ikon, DAN label sekaligus.
 *
 * Keempatnya. Kalau action dan method tidak ikut berubah, klik berikutnya
 * dikirim ke endpoint yang salah. Kalau ikon tidak berubah, database benar
 * tapi tampilannya berbohong — dan di sini tidak ada reload yang akan
 * membetulkan itu.
 */
function applyFavoriteResult(form, body) {
    const isFavorite = body.is_favorite === true;
    const button = form.querySelector('[data-favorite-button]');
    const label = form.querySelector('[data-favorite-label]');
    const icon = form.querySelector('[data-favorite-icon]');
    const methodField = form.querySelector('input[name="_method"]');

    // action + method. `POST` berarti tambah, `DELETE` berarti hapus.
    if (isFavorite) {
        form.action = form.dataset.destroyUrl;
        form.method = 'POST';

        if (methodField) {
            methodField.value = 'DELETE';
        } else {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = '_method';
            field.value = 'DELETE';
            form.appendChild(field);
        }
    } else {
        form.action = form.dataset.storeUrl;
        form.method = 'POST';

        // Field-nya DIHAPUS, bukan diisi string kosong. `_method=""` membuat
        // Symfony method override menghasilkan method request kosong, dan
        // router tidak punya route dengan method itu -> 405. Menghapus field
        // membiarkan POST apa adanya, persis seperti form yang dirender
        // ulang oleh server.
        methodField?.remove();
    }

    if (label) {
        // Label diambil dari `data-*` di tombol, bukan ditulis ulang di sini.
        label.textContent = isFavorite
            ? (button?.dataset.labelRemove ?? label.textContent)
            : (button?.dataset.labelAdd ?? label.textContent);
    }

    if (icon) {
        icon.setAttribute('fill', isFavorite ? 'currentColor' : 'none');
    }

    if (button) {
        button.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
        button.classList.toggle('text-overdue', isFavorite);
    }
}

/**
 * Loading indicator (Task 14.8).
 *
 * Tiga aturan yang dipegang fungsi-fungsi di sini, dan ketiganya muncul dari
 * kesalahan yang pernah nyata, bukan dari teoritis:
 *
 * 1. MARKUPNYA TIDAK DITULIS DI SINI. `showLoading()` versi lama membangun
 *    skeleton sebagai string `innerHTML` — hasil salinan dari
 *    `<x-loading-skeleton>` yang isinya sudah berbeda (versi JS tidak punya
 *    caption, tidak punya judul baris). Dua sumber untuk satu tampilan, dan
 *    mengubah komponen Blade tidak akan mengubah panel pencarian. Sekarang
 *    markupnya dirender Blade di dalam `<template>`, dan JS hanya mengklon.
 *
 * 2. `aria-busy` bukan hiasan. Tanpa itu, screen reader menganggap isi panel
 *    masih hasil pencarian sebelumnya yang valid, lalu membacakan isi basi itu
 *    ke pengguna yang sedang mengetik.
 *
 * 3. Ada yang TERLIHAT, bukan hanya `sr-only`. Aturan global
 *    `prefers-reduced-motion` mematikan `animate-pulse`, jadi skeleton yang
 *    hanya berdenyut akan berubah menjadi blok abu-abu diam yang terbaca
 *    sebagai "halaman rusak". Caption teksnya tidak ikut hilang.
 */

/**
 * Klon skeleton dari `<template>` yang sudah dirender Blade.
 *
 * Kalau template-nya tidak ada (halaman tanpa panel yang memuat data), fungsi
 * ini mengembalikan `null` alih-alih melempar: tidak ada loading state
 * berarti tidak ada yang perlu ditampilkan, dan itu bukan kondisi yang perlu
 * ditangani secara khusus di tiap pemanggil.
 *
 * @param {ParentNode} [root]
 * @returns {Element | null}
 */
function cloneSkeleton(root = document) {
    const template = root.querySelector('[data-search-skeleton]');

    return template ? template.content.firstElementChild.cloneNode(true) : null;
}

/**
 * Tandai elemen sebagai sedang sibuk memuat.
 *
 * Cuma `aria-busy` — tidak ada perubahan tampilan. Ini disengaja:
 *
 * - Tombol sudah punya `disabled:opacity-60` di `.btn` (resources/css/app.css),
 *   jadi menambahkan `opacity-60` dari sini berarti dua sumber untuk hal yang
 *   sama, dan tombol yang redup dobel terbaca rusak.
 * - Panel pencarian tidak boleh diredupkan: isinya sudah berupa skeleton
 *   bernada rendah, dan menambahkan opasitas di atasnya membuat warna
 *   abu-abunya nyaris tidak terlihat.
 *
 * Fungsi ini ada supaya `aria-busy` tidak ditulis manual di lima tempat
 * dengan lima ejaan berbeda.
 */
function setBusy(element, isBusy) {
    if (!element) return;

    element.setAttribute('aria-busy', isBusy ? 'true' : 'false');
}

/**
 * Pembaca PDF (Task 11.2).
 *
 * Yang dikerjakan di sini HANYA memindahkan viewport viewer ke halaman lain
 * dengan mengubah bagian setelah tanda pagar pada URL. Berkas PDF-nya sendiri
 * tidak diunduh ulang, karena `books.file` punya rate limit 30 permintaan per
 * menit — memuat ulang berkasnya setiap pindah halaman akan menghabiskan kuota
 * itu hanya untuk satu sesi baca.
 *
 * Kenapa tidak melacak posisi scroll secara otomatis? Viewer PDF bawaan browser
 * berjalan di dalam plugin terpisah yang tidak bisa dibaca dari halaman ini.
 * Menyebutkan nomor halaman yang tidak diketahui berarti menebak, dan
 * hasil tebakan yang salah akan membuat fitur "lanjut membaca" membuka halaman
 * yang tidak sesuai. Karena itu posisi disimpan lewat tombol, bukan diam-diam.
 *
 * Kalau JavaScript dimatikan, form GET di toolbar tetap bekerja: halamannya
 * dimuat ulang penuh dengan `?page=N`.
 */
function initReader() {
    const form = document.querySelector('[data-reader-jump]');
    const input = document.querySelector('[data-reader-page]');
    const target = document.querySelector('[data-reader-target]');
    const saveField = document.querySelector('[data-reader-save]');

    if (!form || !input || !target) return;

    const fileUrl = target.dataset.readerFileUrl;
    if (!fileUrl) return;

    const max = input.max ? Number(input.max) : null;

    // Penjepitan ulang di sisi user. Server juga menjepit (RecordReading),
    // tapi di sini umpan baliknya instan tanpa menunggu request.
    const resolvePage = () => {
        let page = Number.parseInt(input.value, 10);

        if (!Number.isFinite(page) || page < 1) page = 1;
        if (max && page > max) page = max;

        input.value = String(page);
        return page;
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const page = resolvePage();

        // Hanya bagian fragment yang berubah, jadi browser tidak meminta
        // ulang berkasnya.
        target.setAttribute('data', `${fileUrl}#page=${page}`);

        // Sinkronkan input "Simpan posisi" supaya tombolnya menyimpan halaman
        // yang baru saja dibuka, bukan yang lama.
        if (saveField) saveField.value = String(page);

        // Perbarui address bar tanpa memuat ulang halaman, supaya tombol
        // "Kembali" dan tombol refresh melakukan hal yang sama dengan yang
        // sedang dilakukan user sekarang.
        const url = new URL(window.location.href);
        url.searchParams.set('page', String(page));
        window.history.replaceState({}, '', url);
    });
}

/**
 * Dropdown (Task 14.2).
 *
 * Dipakai untuk menu akun di topbar dan elemen serupa. Atribut:
 * - [data-dropdown] pada tombol pemicu
 * - [data-dropdown-menu] pada panel, letaknya tepat di sebelah pemicu
 *
 * Status buka/tutup disimpan di atribut `hidden` — bukan class — supaya JS
 * tidak perlu tahu class apa yang dipakai tiap view, dan supaya menu tetap
 * tertutup sebelum JS selesai dimuat.
 *
 * Menu mengikuti pola APG: satu listener `click`/`keydown` di level dokumen
 * (bukan satu per dropdown), hanya satu menu boleh terbuka, dan fokus dikembalikan
 * ke pemicu saat menu ditutup lewat Escape.
 */
function initDropdowns() {
    const registry = [];

    document.querySelectorAll('[data-dropdown]').forEach((trigger, index) => {
        const menu =
            trigger.parentElement?.querySelector('[data-dropdown-menu]') ??
            document.getElementById(trigger.getAttribute('aria-controls') || '');
        if (!menu) return;

        const items = () =>
            Array.from(
                menu.querySelectorAll('[data-dropdown-item], a[href], button:not([disabled])')
            ).filter((item) => item.getAttribute('aria-disabled') !== 'true');

        const close = () => {
            if (menu.hidden) return;
            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        };

        const open = (focus = null) => {
            registry.forEach((entry) => {
                if (entry.menu !== menu) entry.close();
            });

            menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');

            const list = items();
            if (focus === 'first') list[0]?.focus();
            if (focus === 'last') list.at(-1)?.focus();
        };

        if (!menu.id) menu.id = `app-dropdown-${index + 1}`;
        trigger.setAttribute('aria-haspopup', 'menu');
        trigger.setAttribute('aria-controls', menu.id);
        trigger.setAttribute('aria-expanded', 'false');
        menu.hidden = true;

        registry.push({ trigger, menu, close, open });

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            menu.hidden ? open() : close();
        });

        // Panah atas/bawah pada pemicu membuka menu sekaligus langsung melompat
        // ke item pertama/terakhir, seperti menu native.
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                open('first');
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                open('last');
            }
        });

        menu.addEventListener('keydown', (event) => {
            const list = items();
            const current = list.indexOf(document.activeElement);

            if (event.key === 'Tab') {
                // Biarkan fokus pindah sendiri, tapi menu ikut menutup supaya
                // tidak ada panel melayang yang tidak lagi relevan.
                close();
                return;
            }

            const moves = {
                ArrowDown: current + 1,
                ArrowUp: current - 1,
                Home: 0,
                End: list.length - 1,
            };

            if (!(event.key in moves) || list.length === 0) return;

            event.preventDefault();
            list[(moves[event.key] + list.length) % list.length].focus();
        });

        // Memilih item (link atau tombol) langsung menutup dropdown.
        menu.addEventListener('click', (event) => {
            if (event.target.closest('a[href], button')) close();
        });
    });

    if (registry.length === 0) return;

    document.addEventListener('click', (event) => {
        registry.forEach((entry) => {
            if (entry.menu.hidden) return;
            if (entry.menu.contains(event.target) || entry.trigger.contains(event.target)) return;
            entry.close();
        });
    });

    // Escape ditangani di level dokumen supaya tetap bekerja walau fokus masih
    // di pemicu (menu dibuka lewat klik, bukan lewat keyboard).
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        registry.forEach((entry) => {
            if (entry.menu.hidden) return;
            entry.close();
            entry.trigger.focus();
        });
    });
}

/**
 * Modal (Task 14.3).
 *
 * Elemennya `<dialog>` asli, bukan overlay buatan sendiri. Browser yang
 * menangani focus trap, Escape, `inert` konten di belakang, dan pengembalian
 * fokus ke tombol pembuka — bagian yang paling sering ditulis salah kalau
 * dikerjakan manual.
 *
 * Atribut:
 * - <dialog data-modal> untuk dialognya
 * - [data-modal-open="#id"] pada tombol pembuka
 * - [data-modal-close] pada tombol penutup di dalam dialog
 *
 * Pembuka ditangani lewat delegasi (satu listener di dokumen), bukan satu
 * listener per tombol, supaya tombol yang ditambahkan kemudian tetap bekerja.
 */
function initModals() {
    const modals = Array.from(document.querySelectorAll('dialog[data-modal]'));
    if (modals.length === 0) return;

    const syncScrollLock = () => {
        document.documentElement.classList.toggle('modal-open', modals.some((modal) => modal.open));
    };

    modals.forEach((modal) => {
        const close = () => {
            if (modal.open) modal.close();
        };

        modal.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', close);
        });

        // Panel dialog memenuhi seluruh isinya, jadi klik di area gelap di
        // luar panel sampai ke elemen <dialog> itu sendiri.
        modal.addEventListener('click', (event) => {
            if (event.target === modal) close();
        });

        // Escape ditangani browser; event ini hanya untuk melepas kunci scroll.
        modal.addEventListener('close', syncScrollLock);
    });

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal-open]');
        if (!opener) return;

        const id = (opener.dataset.modalOpen || '').replace('#', '');
        const modal = id ? document.getElementById(id) : null;
        if (!modal || modal.tagName !== 'DIALOG') return;

        event.preventDefault();

        // Hanya satu dialog yang boleh jadi top layer. Kalau tidak, backdrop-nya
        // menumpuk dan tombol Escape hanya menutup yang paling atas.
        modals.forEach((other) => {
            if (other !== modal && other.open) other.close();
        });

        modal.showModal();
        syncScrollLock();
    });
}

/**
 * Sidebar navigasi utama (Task 14.1).
 *
 * Sidebar bisa di-collapse di desktop dan jadi drawer di mobile.
 * Atribut:
 * - [data-sidebar-toggle] pada tombol collapse
 * - [data-sidebar] pada elemen sidebar
 */
function initSidebar() {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const closeButton = document.querySelector('[data-sidebar-close]');

    if (!toggle || !sidebar || !backdrop) return;

    const desktop = window.matchMedia('(min-width: 1024px)');

    // Elemen yang boleh menerima fokus di dalam drawer, di urutan kemunculan.
    // Dihitung ulang tiap kali drawer dibuka: isinya bisa berubah (tautan
    // sesuai role, tombol Keluar) dan yang di-trap harus yang benar-benar ada.
    const focusables = () =>
        Array.from(
            sidebar.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
            ),
        ).filter((element) => element.offsetParent !== null || element === closeButton);

    const updateToggle = (expanded, label) => {
        toggle.setAttribute('aria-expanded', String(expanded));
        toggle.setAttribute('aria-label', label);
        toggle.title = label;
    };

    const setDrawerOpen = (open) => {
        sidebar.classList.toggle('-translate-x-full', !open);
        sidebar.inert = !open;
        sidebar.setAttribute('aria-hidden', String(!open));
        backdrop.hidden = !open;
        document.body.classList.toggle('overflow-hidden', open);
        updateToggle(open, open ? 'Tutup sidebar' : 'Buka sidebar');

        // Fokus HARUS pindah ke dalam drawer saat terbuka (Task 14.9).
        //
        // Tanpa ini, menekan burger tidak menghasilkan apa pun yang terbaca
        // screen reader, dan menekan Tab berikutnya langsung mendarat di
        // tautan-tautan di belakang drawer — yang justru terlihat karena
        // drawer cuma menutup 256px dari layar. Drawer ini `<aside>`, bukan
        // `<dialog>`, jadi browser tidak memberi FocusTrap dan `inert` gratis
        // seperti di Task 14.3. Karena itu keduanya ditulis manual.
        if (open) {
            (closeButton ?? focusables()[0])?.focus();
        }
    };

    const syncViewport = () => {
        if (desktop.matches) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.inert = false;
            sidebar.setAttribute('aria-hidden', 'false');
            backdrop.hidden = true;
            document.body.classList.remove('overflow-hidden');
            updateToggle(!document.body.classList.contains('sidebar-collapsed'), 'Ciutkan atau perluas sidebar');
            return;
        }

        document.body.classList.remove('sidebar-collapsed');
        setDrawerOpen(false);
    };

    // Menutup drawer SELALU harus mengembalikan fokus ke tombol burger.
    //
    // Kalau tidak, fokus yang sedang aktif hilang entah ke mana setelah
    // drawer menutup: satu klik di backdrop berarti pengguna keyboard
    // kehilangan posisi dan harus Tab dari awal dokumen lagi. Jadi semua jalur
    // menutup (backdrop, tombol tutup, Escape) lewat satu fungsi ini, bukan
    // tiga salinan yang masing-masing bisa lupa.
    const closeAndRestoreFocus = () => {
        setDrawerOpen(false);
        toggle.focus();
    };

    toggle.addEventListener('click', () => {
        if (desktop.matches) {
            const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
            updateToggle(!isCollapsed, isCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar');
            return;
        }

        setDrawerOpen(sidebar.classList.contains('-translate-x-full'));
    });

    backdrop.addEventListener('click', closeAndRestoreFocus);

    closeButton?.addEventListener('click', closeAndRestoreFocus);

    // Tautan di dalam drawer menutupnya, tapi TIDAK mengembalikan fokus:
    // klik tautan berarti pindah halaman, dan `toggle.focus()` di sini akan
    // battles dengan fokus baru milik halaman tujuan.
    sidebar.addEventListener('click', (event) => {
        if (!desktop.matches && event.target.closest('a')) setDrawerOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (desktop.matches || sidebar.classList.contains('-translate-x-full')) return;

        if (event.key === 'Escape') {
            closeAndRestoreFocus();
            return;
        }

        // Focus trap. Tanpa ini, Tab dari tautan terakhir di drawer
        // melanjutkan ke isi halaman di sebelahnya, yang tertutup backdrop tapi
        // tetap bisa difokuskan dan tetap bisa diaktifkan. Drawer yang
        // "terbuka" tapi bisa dijangkau keyboard lewat isi halaman di
        // belakangnya bukan drawer, melainkan bug.
        if (event.key !== 'Tab') return;

        const items = focusables();

        if (items.length === 0) return;

        const first = items[0];
        const last = items[items.length - 1];
        const active = document.activeElement;

        if (event.shiftKey && (active === first || !sidebar.contains(active))) {
            event.preventDefault();
            last.focus();
            return;
        }

        if (!event.shiftKey && (active === last || !sidebar.contains(active))) {
            event.preventDefault();
            first.focus();
        }
    });

    let desktopMode = desktop.matches;
    const handleViewportChange = () => {
        if (desktop.matches === desktopMode) return;

        desktopMode = desktop.matches;
        syncViewport();
    };

    desktop.addEventListener("change", handleViewportChange);
    window.addEventListener("resize", handleViewportChange);
    syncViewport();
}

/**
 * Search interaction (Task 14.6).
 *
 * Pratinjau hasil pencarian katalog yang muncul di bawah input, tanpa reload
 * halaman. Tombol "Cari" tetap ada dan tetap bekerja: panel ini murni
 * tambahan, bukan pengganti form. Kalau JS mati atau gagal, user masih bisa
 * menekan Enter dan mendapat halaman hasil lengkap.
 *
 * Kontrak atribut:
 * - [data-search-input]   input pencarian (wajib)
 * - [data-search-results] panel hasil (wajib)
 * - [data-search-url]     endpoint JSON (opsional, default books.search)
 * - [data-search-min]     panjang minimum, default 2
 *
 * Empat hal yang mudah salah dan sengaja ditangani di sini:
 *
 * 1. `AbortController`. Tanpa itu, request lama bisa selesai DULUAN lalu menimpa
 *    hasil request baru yang lebih Relevan — user mengetik "and" lalu "andi",
 *    respons "and" tiba belakangan dan mengganti pratinjau yang sebenarnya
 *    cocok. Membatalkan request yang sudah tidak relevan menghapus kelas bug
 *    ini tanpa perlu membandingkan nomor urut.
 *
 * 2. Tidak ada `innerHTML`. Versi sebelumnya menaruh HTML dari server lewat
 *    `innerHTML`. Kalau endpoint suatu saat ikut mengembalikan HTML, itu
 *    celah XSS. Di sini teks masuk lewat `textContent` dan `href` diset
 *    sebagai properti URL, bukan markup.
 *
 * 3. Stale response vs. input. Kalau user menekan Escape atau mengosongkan
 *    input saat request masih jalan, response-nya tidak boleh membuka panel
 *    kembali. Karena itu `term` dicocokkan ulang dengan `input.value` sebelum
 *    hasil dirender.
 *
 * 4. Aksesibilitas keyboard. Panel adalah listbox: panah atas/bawah
 *    memindahkan sorotan, Enter membuka, Escape menutup. Tanpa ini, panel
 *    hanya bisa dipakai dengan mouse.
 */
function initSearch() {
    const input = document.querySelector('[data-search-input]');
    const results = document.querySelector('[data-search-results]');

    if (!input || !results) return;

    const minLength = Number(input.dataset.searchMin) || 2;
    const endpoint = input.dataset.searchUrl;

    let debounceTimer;
    let controller = null;
    let options = [];
    let activeIndex = -1;

    function closePanel() {
        results.hidden = true;
        setBusy(results, false);
        options = [];
        activeIndex = -1;
        input.removeAttribute('aria-expanded');
        input.removeAttribute('aria-activedescendant');
    }

    function highlight(index) {
        options.forEach((option, position) => {
            const isActive = position === index;
            option.classList.toggle('is-active', isActive);
            option.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        activeIndex = index;

        if (index < 0) {
            input.removeAttribute('aria-activedescendant');
            return;
        }

        input.setAttribute('aria-activedescendant', options[index].id);
        options[index].scrollIntoView({ block: 'nearest' });
    }

    /**
     * Tampilkan loading state di panel.
     *
     * Dipanggil SEBELUM `fetch` yang belum tentu selesai dalam satu frame.
     * Kalau tidak ada apa pun yang berubah selama 300 ms debounce + waktu
     * jaringan, panel terlihat beku: pengguna mengetik, tidak terjadi apa-apa,
     * lalu mengira kotak pencariannya rusak dan menekan Enter (yang memicu
     * reload penuh). Jadi yang ditunjukkan bukan spinner tanpa konteks, tapi
     * bentuk isi yang AKAN datang — panel dengan tinggi yang hampir sama
     * membuat hasil yang tiba kemudian tidak menggeser layout.
     */
    function renderLoading() {
        const skeleton = cloneSkeleton();

        results.replaceChildren();

        if (skeleton) {
            results.appendChild(skeleton);
        } else {
            // Tanpa template, jangan tampilkan panel kosong yang berskala nol:
            // itu lebih membingungkan daripada tidak menampilkan apa pun.
            results.hidden = true;
            return;
        }

        setBusy(results, true);
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    /**
     * Membangun panel hasil. Semua teks masuk lewat `textContent`.
     */
    function render(payload) {
        results.replaceChildren();
        setBusy(results, false);
        options = [];
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');

        const list = payload.results ?? [];

        if (list.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'search-message';
            empty.textContent = 'Tidak ada buku yang cocok.';
            results.appendChild(empty);
            results.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            return;
        }

        const listbox = document.createElement('ul');
        listbox.className = 'search-list';
        listbox.setAttribute('role', 'listbox');
        listbox.id = 'search-results-list';

        list.forEach((item, index) => {
            const link = document.createElement('a');
            link.className = 'search-item';
            link.href = item.url;
            link.id = `search-result-${index}`;
            link.setAttribute('role', 'option');
            link.setAttribute('aria-selected', 'false');

            const heading = document.createElement('span');
            heading.className = 'search-item-title';
            heading.textContent = item.title;

            const meta = document.createElement('span');
            meta.className = 'search-item-meta';
            meta.textContent = item.author ?? 'Penulis tidak dicantumkan';

            const body = document.createElement('span');
            body.className = 'search-item-body';
            body.append(heading, meta);

            link.appendChild(body);

            if (item.is_borrowed) {
                const badge = document.createElement('span');
                badge.className = 'search-item-status';
                badge.textContent = item.status_label;
                link.appendChild(badge);
            }

            listbox.appendChild(link);
            options.push(link);
        });

        results.appendChild(listbox);
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        input.setAttribute('aria-controls', listbox.id);
    }

    function renderMessage(text) {
        results.replaceChildren();
        setBusy(results, false);
        const message = document.createElement('p');
        message.className = 'search-message';
        message.textContent = text;
        results.appendChild(message);
        options = [];
        activeIndex = -1;
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function fetchResults(term) {
        controller?.abort();
        controller = new AbortController();

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('q', term);

        renderLoading();

        fetch(url, {
            signal: controller.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })
            .then((response) => {
                // 429 harus dibedakan dari error lain: itu batas rate, bukan
                // kegagalan. Menyuruh user "gagal memuat" saat yang sebenarnya
                // hanya "terlalu cepat" membuat mereka mengira situsnya rusak.
                if (response.status === 429) {
                    throw Object.assign(new Error('throttled'), { throttled: true });
                }

                if (!response.ok) {
                    throw new Error('request failed');
                }

                return response.json();
            })
            .then((payload) => {
                // Request yang sudah dibatalkan atau input yang sudah berubah
                // tidak boleh menimpa apa yang sudah tampil.
                if (controller.signal.aborted || input.value.trim() !== term) return;

                render(payload);
            })
            .catch((error) => {
                if (error.name === 'AbortError') return;
                if (controller.signal.aborted || input.value.trim() !== term) return;

                renderMessage(
                    error.throttled
                        ? uiMessage(
                              "SearchThrottled",
                              "You are searching too quickly. Please try again shortly.",
                          )
                        : uiMessage(
                              "SearchFailed",
                              "Could not load results. Press Enter to search.",
                          ),
                );
            });
    }

    input.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);

        const term = input.value.trim();

        if (term.length < minLength) {
            controller?.abort();
            closePanel();
            return;
        }

        debounceTimer = window.setTimeout(() => fetchResults(term), 300);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePanel();
            return;
        }

        if (options.length === 0) return;

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();

            const step = event.key === 'ArrowDown' ? 1 : -1;
            const next = activeIndex + step;

            // Sorotan berputar: dari hasil terakhir naik kembali ke awal, dan
            // sebaliknya. Tanpa itu, orang yang sudah mengetik beberapa huruf
            // lalu memakai mouse akan merasa panelnya macet karena tidak ada
            // cara naik lagi setelah menyentuh hasil paling bawah.
            highlight(next < 0 ? options.length - 1 : next % options.length);
            return;
        }

        if (event.key === 'Enter' && activeIndex >= 0) {
            // Enter hanya dicegah kalau ada hasil yang disorot. Tanpa
            // sorotan, Enter dibiarkan berjalan ke submit form supaya user
            // tetap bisa melakukan pencarian penuh lewat tombol atau Enter.
            event.preventDefault();
            window.location.assign(options[activeIndex].href);
        }
    });

    // Klik di luar menutup panel. Menempel di `document` dengan
    // `pointerdown` (bukan `click`) supaya panel sudah tertutup sebelum
    // elemen di bawahnya menerima klik — kalau memakai `click`, satu klik
    // bisa membatalkan navigasi yang memang diklik user.
    document.addEventListener('pointerdown', (event) => {
        if (results.hidden) return;
        if (results.contains(event.target) || input === event.target) return;

        closePanel();
    });

    input.form?.addEventListener('reset', () => closePanel());
}

/**
 * AJAX/fetch helper (Task 14.7).
 *
 * Fungsi global untuk request AJAX dengan CSRF token otomatis.
 */
window.ajax = function (url, options = {}) {
    const defaults = {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
    };

    return fetch(url, { ...defaults, ...options });
};

/**
 * Quick add untuk kategori/penulis/penerbit di form buku.
 *
 * Alur: user klik tombol "+" di samping dropdown → modal muncul → isi nama →
 * submit via AJAX → item baru dibuat di server → dropdown di-refresh → item
 * baru otomatis terpilih.
 *
 * Kenapa AJAX bukan form biasa: user tidak perlu meninggalkan halaman form buku.
 * Kalau redirect, semua isi form yang sudah diketik hilang.
 *
 * Dua hal yang harus benar di sini, dan keduanya soal "user tidak diberi
 * tahu apa yang terjadi":
 *
 * 1. Response BELUM harus dicek dulu. `fetch` tidak melempar error untuk 422,
 *    jadi `if (data.id && data.name)` yang dulu dipakai akan diam saja saat
 *    validasi gagal — modal tetap terbuka, tidak ada pesan, tombol kembali
 *    seperti semula. User mengira tombolnya tidak nyambung, lalu mengulang
 *    percobaan dengan nama yang sama.
 *
 * 2. Pesan error ditulis INLINE di dalam modal, bukan lewat `window.toast`.
 *    Toast hidup di aliran dokumen biasa, sedangkan `<dialog>` yang terbuka
 *    ada di top layer — toast selalu tertutup backdrop dan tidak terlihat.
 */
function initQuickAdd() {
    const forms = document.querySelectorAll('[data-quick-form]');
    if (forms.length === 0) return;

    forms.forEach((form) => {
        const error = form.querySelector('[data-quick-error]');

        // Pesan error dari server selalu di dalam modal, jadi `textContent`
        // cukup: isinya berasal dari `$errors` Laravel, bukan dari markup.
        const showError = (message) => {
            if (!error) return;

            error.textContent = message;
            error.hidden = message === null || message === '';
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const targetId = form.dataset.quickTarget;
            const select = document.getElementById(targetId);
            if (!select) return;

            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton?.textContent || '';

            showError('');

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = uiMessage("Saving", "Saving...");
            }

            fetch(form.action, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                    Accept: "application/json",
                },
                body: formData,
            })
                .then(async (response) => {
                    const data = await response.json().catch(() => null);

                    if (!response.ok) {
                        showError(
                            firstErrorMessage(data?.errors) ||
                                data?.message ||
                                uiMessage(
                                    "SaveFailed",
                                    "Could not save. Please try again.",
                                ),
                        );
                        return;
                    }

                    if (!data?.id || !data?.name) {
                        showError(
                            uiMessage(
                                "IncompleteResponse",
                                "The server response is incomplete. Please try again.",
                            ),
                        );
                        return;
                    }

                    const option = document.createElement("option");
                    option.value = data.id;
                    option.textContent = data.name;
                    select.appendChild(option);
                    select.value = data.id;

                    form.reset();
                    form.closest("dialog[data-modal]")?.close();
                })
                .catch(() =>
                    showError(
                        uiMessage(
                            "NetworkError",
                            "Connection problem. The form was not submitted.",
                        ),
                    ),
                )
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                });
        });
    });
}

function initGlobalStatusAndClock() {
    document.querySelectorAll("[data-live-clock]").forEach((clock) => {
        const timezone = clock.dataset.timezone || "Asia/Jakarta";
        const locale =
            clock.dataset.locale || document.documentElement.lang || "id";
        const date = clock.querySelector("[data-clock-date]");
        const time = clock.querySelector("[data-clock-time]");
        const dateFormat = new Intl.DateTimeFormat(locale, {
            weekday: "long",
            day: "2-digit",
            month: "long",
            year: "numeric",
            timeZone: timezone,
        });
        const timeFormat = new Intl.DateTimeFormat(locale, {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: false,
            timeZone: timezone,
        });

        const update = () => {
            const now = new Date();
            if (date) date.textContent = dateFormat.format(now);
            if (time) time.textContent = timeFormat.format(now);
            clock.dateTime = now.toISOString();
        };

        update();
        window.setInterval(update, 1000);
    });

    document
        .querySelectorAll("[data-system-indicator]")
        .forEach((indicator) => {
            const dot = indicator.querySelector("[data-status-dot]");
            const ping = indicator.querySelector("[data-status-ping]");
            const label = indicator.querySelector("[data-status-label]");
            const onlineLabel = indicator.dataset.onlineLabel || "Sistem Aktif";
            const offlineLabel =
                indicator.dataset.offlineLabel || "Sistem Offline";
            let checking = false;

            const update = (online) => {
                indicator.dataset.state = online ? "online" : "offline";
                indicator.classList.toggle("text-available", online);
                indicator.classList.toggle("text-overdue", !online);
                dot?.classList.remove(
                    "bg-secondary",
                    "bg-available",
                    "bg-overdue",
                );
                dot?.classList.add(online ? "bg-available" : "bg-overdue");
                ping?.toggleAttribute("hidden", !online);
                if (label)
                    label.textContent = online ? onlineLabel : offlineLabel;
            };

            const check = async () => {
                if (checking) return;
                checking = true;

                try {
                    const response = await fetch(indicator.dataset.healthUrl, {
                        headers: { Accept: "application/json" },
                        cache: "no-store",
                        credentials: "same-origin",
                    });
                    update(response.ok);
                } catch {
                    update(false);
                } finally {
                    checking = false;
                }
            };

            check();
            window.setInterval(check, 30000);
        });
}

function initBackToTop() {
    const button = document.querySelector("[data-back-to-top]");
    if (!button) return;

    const threshold = Number(button.dataset.threshold) || 400;
    let isVisible = false;

    const updateVisibility = () => {
        const shouldShow = window.scrollY > threshold;
        if (shouldShow === isVisible) return;

        isVisible = shouldShow;
        button.hidden = !shouldShow;
    };

    window.addEventListener("scroll", updateVisibility, { passive: true });

    button.addEventListener("click", () => {
        const behavior = window.matchMedia("(prefers-reduced-motion: reduce)")
            .matches
            ? "auto"
            : "smooth";

        window.scrollTo({ top: 0, behavior });
    });

    updateVisibility();
}

/**
 * Pratinjau foto profil sebelum diunggah.
 *
 * Tanpa ini, satu-satunya cara tahu foto yang dipilih benar adalah menyimpan
 * dulu, lalu menunggu halaman dimuat ulang — dan kalau salah, satu langkah
 * sia-sia terbuang. `URL.createObjectURL` membaca berkas langsung dari
 * komputer, jadi tidak ada upload ke server sama sekali.
 *
 * `revokeObjectURL` wajib dipanggil begitu objek selesai dipakai: setiap
 * `createObjectURL` menahan salinan berkas di memori browser sampai di-revoke,
 * dan mengulanginya tiap user memilih foto bisa menahan banyak berkas besar.
 *
 * JS mati bukan masalah: `form` tetap mengirim apa adanya ke server, hanya
 * pratinjau yang tidak muncul.
 */
function initAvatarPreview() {
    const input = document.querySelector("[data-avatar-input]");
    const preview = document.querySelector("[data-avatar-preview]");

    if (!input || !preview) return;

    input.addEventListener("change", () => {
        const file = input.files?.[0];

        // Memilih ulang berkas yang sama TIDAK memicu event `change` kalau
        // input tidak dikosongkan lebih dulu. Men_assign ulang `value`
        // dengan string kosong membuat input menerima event itu lagi, jadi
        // "hapus foto lalu pilih berkas yang sama" ikut terpakai.
        input.value = "";

        if (!file) return;

        if (!file.type.startsWith("image/")) return;

        const url = URL.createObjectURL(file);
        const image = new Image();
        image.src = url;
        image.alt = "";
        image.className = "h-full w-full object-cover";

        image.addEventListener("load", () => URL.revokeObjectURL(url));
        image.addEventListener("error", () => URL.revokeObjectURL(url));

        preview.replaceChildren(image);
    });
}

/**
 * Lonceng notifikasi (Task 22.x).
 *
 * Membuka lonceng diartikan "sudah saya lihat": begitu panel terbuka, seluruh
 * notifikasi ditandai sudah dibaca lewat fetch, sehingga angka pada lonceng
 * hilang di saat yang sama. Kenapa bukan memuat ulang halaman? Karena refresh
 * justru menutup panel yang baru saja dibuka — angkanya memang hilang, tapi
 * penggunanya harus mengklik lonceng kedua kalinya untuk melihat isinya.
 *
 * Listener dipasang SETELAH `initDropdowns()` pada tombol pemicu yang sama.
 * Kedua listener sinkron dan dijalankan sesuai urutan pendaftaran, jadi saat
 * handler ini berjalan, menu sudah berada dalam keadaan terbuka (`menu.hidden`
 * sudah false). Klik yang justru MENUTUP panel terbaca dari `menu.hidden`
 * yang tetap true dan tidak memicu apa-apa.
 *
 * Kalau fetch gagal, angka sengaja dibiarkan: notifikasi belum tentu sudah
 * dibaca, dan tombol "Tandai semua sudah dibaca" di dalam panel tetap
 * tersedia sebagai jalur biasa (form POST tanpa JS).
 */
function initNotificationBell() {
    document.querySelectorAll('[data-notification-bell]').forEach((root) => {
        const trigger = root.querySelector('[data-dropdown]');
        const menu = root.querySelector('[data-dropdown-menu]');

        if (!trigger || !menu) return;

        trigger.addEventListener('click', () => {
            if (menu.hidden) return;

            // Tanpa badge berarti memang tidak ada yang belum dibaca —
            // tidak ada alasan memukul server di setiap pembukaan panel.
            if (!root.querySelector('[data-notification-badge]')) return;

            window
                .ajax(root.dataset.readAllUrl, { method: 'POST' })
                .then((response) => {
                    if (response.ok) clearNotificationUnreadState(root);
                })
                .catch(() => {
                    //
                });
        });
    });
}

/**
 * Bersihkan penanda "belum dibaca" di DOM setelah server mengonfirmasi.
 *
 * Gaya barisnya (latar, tebal judul, titik, teks pembaca layar) semuanya
 * mengikuti atribut `data-notification-unread` — lihat blok notifikasi di
 * `app.css`. Karena itu cukup satu `removeAttribute` per baris, tanpa JS
 * perlu tahu class Tailwind apa pun.
 */
function clearNotificationUnreadState(root) {
    root.querySelector('[data-notification-badge]')?.remove();
    root.querySelectorAll('[data-notification-unread]').forEach((item) => {
        item.removeAttribute('data-notification-unread');
    });
    root.querySelector('[data-notification-count]')?.remove();
    root.querySelector('[data-notification-mark-all]')?.remove();

    const text = root.querySelector('[data-notification-unread-text]');
    if (text) text.textContent = root.dataset.emptyText;
}

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initNavToggle();
    initToasts();
    // `initSubmitGuards()` SEBELUM `initAjaxForms()` — urutannya syarat, bukan
    // gaya penulisan. Dua-duanya delegated ke `document`, dan dua listener di
    // `document` yang sama dijalankan sesuai urutan pendaftaran. Kalau dibalik,
    // listener AJAX membatalkan `submit` lebih dulu, `defaultPrevented` sudah
    // true saat konfirmasi berjalan, dan dialognya tidak pernah muncul.
    initSubmitGuards();
    initAjaxForms();
    initReader();
    initDropdowns();
    initNotificationBell();
    initModals();
    initSidebar();
    initSearch();
    initQuickAdd();
    initGlobalStatusAndClock();
    initBackToTop();
    initAvatarPreview();
});
