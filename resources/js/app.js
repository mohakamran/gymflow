import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import QRCode from 'qrcode';
import { chart } from './charts';

Alpine.plugin(collapse);

/**
 * Theme: 'light' | 'dark' | 'system'. Stored per browser; applied before paint by partials/head.
 */
Alpine.store('theme', {
    mode: localStorage.getItem('theme') || 'system',

    set(mode) {
        this.mode = mode;
        localStorage.setItem('theme', mode);
        this.apply();
    },

    toggle() {
        this.set(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
    },

    apply() {
        const dark = this.mode === 'dark' || (this.mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    },
});

/**
 * Toasts. Push from anywhere with: window.toast('Saved', 'success')
 */
Alpine.store('toasts', {
    items: [],
    counter: 0,

    push(message, type = 'success', timeout = 4500) {
        const id = ++this.counter;
        this.items.push({ id, message, type, visible: true });
        if (timeout) {
            setTimeout(() => this.dismiss(id), timeout);
        }
    },

    dismiss(id) {
        const toast = this.items.find((item) => item.id === id);
        if (toast) {
            toast.visible = false;
            setTimeout(() => (this.items = this.items.filter((item) => item.id !== id)), 300);
        }
    },
});

window.toast = (message, type = 'success') => Alpine.store('toasts').push(message, type);

/**
 * Preview an image file input before upload.
 */
Alpine.data('imagePreview', (initial = null) => ({
    preview: initial,
    removed: false,

    pick(event) {
        const [file] = event.target.files;
        if (file) {
            this.preview = URL.createObjectURL(file);
            this.removed = false;
        }
    },

    clear() {
        this.preview = null;
        this.removed = true;
        this.$refs.input.value = '';
    },
}));

Alpine.data('chart', chart);

/**
 * Renders a QR code (as SVG) for a member's check-in payload.
 */
Alpine.data('qrCode', (payload, size = 200) => ({
    async init() {
        this.$el.innerHTML = await QRCode.toString(payload, { type: 'svg', margin: 1, width: size, errorCorrectionLevel: 'M', color: { dark: '#000000', light: '#ffffff' } });
    },
}));

/**
 * Debounced member search used by pickers (check-in, payments, class bookings).
 */
Alpine.data('memberPicker', (url, initial = null) => ({
    query: '',
    results: [],
    selected: initial,
    open: false,
    loading: false,
    timer: null,

    search() {
        clearTimeout(this.timer);
        if (this.query.trim().length < 1) {
            this.results = [];
            return;
        }
        this.timer = setTimeout(async () => {
            this.loading = true;
            const response = await fetch(`${url}?q=${encodeURIComponent(this.query)}`, { headers: { Accept: 'application/json' } });
            this.results = response.ok ? await response.json() : [];
            this.loading = false;
            this.open = true;
        }, 200);
    },

    choose(member) {
        this.selected = member;
        this.open = false;
        this.query = '';
        this.$dispatch('member-selected', member);
    },
}));

/**
 * Copy text to the clipboard (falls back to execCommand on non-secure origins).
 */
window.copyToClipboard = async (text) => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            const area = Object.assign(document.createElement('textarea'), { value: text });
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            document.execCommand('copy');
            area.remove();
        }

        return true;
    } catch {
        return false;
    }
};

Alpine.data('copyButton', (text, label = 'Copied') => ({
    copied: false,

    async copy() {
        if (await window.copyToClipboard(text)) {
            this.copied = true;
            window.toast(`${label} copied to clipboard`, 'success');
            setTimeout(() => (this.copied = false), 1600);
        } else {
            window.toast('Copy failed — select the text and copy it manually', 'error');
        }
    },
}));

window.Alpine = Alpine;

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => Alpine.store('theme').apply());

Alpine.start();
