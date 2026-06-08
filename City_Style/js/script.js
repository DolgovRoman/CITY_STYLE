const formatPrice = (value) => `${Math.round(value).toLocaleString("ru-RU")} ₽`;

function getBuilderData() {
    if (window.OUTFIT_BUILDER_DATA && typeof window.OUTFIT_BUILDER_DATA === "object") {
        return window.OUTFIT_BUILDER_DATA;
    }
    return { top: [], bottom: [], shoes: [] };
}

const builderState = { top: 0, bottom: 0, shoes: 0 };

function setMediaFrame(frame, url, alt) {
    if (!frame) return;
    const img = frame.querySelector(".media-frame__img");
    if (!url) {
        frame.classList.add("is-empty");
        if (img) {
            img.removeAttribute("src");
            img.alt = "";
        }
        return;
    }
    frame.classList.remove("is-empty");
    if (img) {
        img.src = url;
        img.alt = alt || "";
    }
}

function setCarouselMedia(card, item) {
    const frame = card.querySelector('[data-role="frame"]');
    const link = card.querySelector('[data-role="link"]');
    if (link && item && item.url) {
        link.href = item.url;
    }
    setMediaFrame(frame, item && item.img ? item.img : "", item ? item.name : "");
}

function setOutfitLayer(slot, item) {
    const layer = document.querySelector(`[data-outfit-layer="${slot}"]`);
    const img = layer?.querySelector("[data-outfit-img]");
    const url = item && item.img ? item.img : "";
    if (!layer || !img) return;
    if (!url) {
        layer.classList.add("is-empty");
        img.removeAttribute("src");
        img.alt = "";
        return;
    }
    layer.classList.remove("is-empty");
    img.src = url;
    img.alt = item.name || "";
}

function updateOutfitLookHint() {
    const look = document.getElementById("outfitLook");
    const hint = document.getElementById("outfitLookHint");
    const stack = document.getElementById("outfitLookStack");
    const note = document.getElementById("outfitLookNote");
    if (!look || !hint) return;
    const filled = look.querySelectorAll(".outfit-slot:not(.is-empty)").length;
    const hasAny = filled > 0;
    look.classList.toggle("outfit-look--active", hasAny);
    hint.hidden = hasAny;
    if (stack) stack.hidden = !hasAny;
    if (note) note.hidden = filled < 2;
}

function renderBuilder() {
    const DATA = getBuilderData();
    const cards = document.querySelectorAll("[data-carousel]");
    if (!cards.length) return;

    cards.forEach((card) => {
        const key = card.dataset.carousel;
        const list = DATA[key] || [];
        if (!list.length) return;

        if (builderState[key] >= list.length) builderState[key] = 0;
        if (builderState[key] < 0) builderState[key] = list.length - 1;

        const item = list[builderState[key]];
        const nameEl = card.querySelector('[data-role="name"]');
        const priceEl = card.querySelector('[data-role="price"]');
        if (nameEl) nameEl.textContent = item.name;
        if (priceEl) priceEl.textContent = item.priceFmt || formatPrice(item.price);
        setCarouselMedia(card, item);

        const summary = document.querySelector(`[data-summary="${key}"]`);
        if (summary) summary.textContent = item.name;
    });

    const totalEl = document.querySelector("[data-total]");
    if (totalEl) {
        let total = 0;
        ["top", "bottom", "shoes"].forEach((key) => {
            const list = DATA[key] || [];
            if (list.length) total += Number(list[builderState[key]]?.price || 0);
        });
        totalEl.textContent = formatPrice(total);
    }

    ["top", "bottom", "shoes"].forEach((key) => {
        const list = DATA[key] || [];
        const item = list.length ? list[builderState[key]] : null;
        setOutfitLayer(key, item);
        const idInput = document.querySelector(`[data-outfit-id="${key}"]`);
        if (idInput) {
            idInput.value = item && item.id ? String(item.id) : "0";
        }
    });
    updateOutfitLookHint();
}

function initBuilderEvents() {
    document.querySelectorAll("[data-carousel]").forEach((card) => {
        const key = card.dataset.carousel;
        card.querySelectorAll(".nav-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
                const DATA = getBuilderData();
                const list = DATA[key] || [];
                if (!list.length) return;
                const step = btn.dataset.action === "next" ? 1 : -1;
                builderState[key] = (builderState[key] + step + list.length) % list.length;
                renderBuilder();
            });
        });
    });
}

function initResponsiveNav() {
    const configs = [
        { key: "site", bodyClass: "site-nav-open", panelId: "site-nav-panel" },
        { key: "staff", bodyClass: "staff-nav-open", panelId: "staff-sidebar" },
    ];

    configs.forEach(({ key, bodyClass, panelId }) => {
        const toggle = document.querySelector(`[data-nav-toggle="${key}"]`);
        const overlay = document.querySelector(`[data-nav-overlay="${key}"]`);
        const panel = document.getElementById(panelId);
        if (!toggle) return;

        const setOpen = (open) => {
            document.body.classList.toggle(bodyClass, open);
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
            toggle.setAttribute("aria-label", open ? "Закрыть меню" : "Открыть меню");
            if (overlay) overlay.hidden = !open;
        };

        toggle.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            setOpen(!document.body.classList.contains(bodyClass));
        });

        if (overlay) {
            overlay.addEventListener("click", (e) => {
                e.stopPropagation();
                setOpen(false);
            });
        }

        if (panel) {
            panel.addEventListener("click", (e) => e.stopPropagation());
            panel.querySelectorAll("a[href], button").forEach((el) => {
                el.addEventListener("click", () => setOpen(false));
            });
        }
    });

    document.addEventListener("keydown", (e) => {
        if (e.key !== "Escape") return;
        if (document.body.classList.contains("site-nav-open")) {
            document.body.classList.remove("site-nav-open");
            const t = document.querySelector('[data-nav-toggle="site"]');
            const o = document.querySelector('[data-nav-overlay="site"]');
            if (t) t.setAttribute("aria-expanded", "false");
            if (o) o.hidden = true;
        }
        if (document.body.classList.contains("staff-nav-open")) {
            document.body.classList.remove("staff-nav-open");
            const t = document.querySelector('[data-nav-toggle="staff"]');
            const o = document.querySelector('[data-nav-overlay="staff"]');
            if (t) t.setAttribute("aria-expanded", "false");
            if (o) o.hidden = true;
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 980 && document.body.classList.contains("site-nav-open")) {
            document.body.classList.remove("site-nav-open");
            const t = document.querySelector('[data-nav-toggle="site"]');
            const o = document.querySelector('[data-nav-overlay="site"]');
            if (t) t.setAttribute("aria-expanded", "false");
            if (o) o.hidden = true;
        }
        if (window.innerWidth > 960 && document.body.classList.contains("staff-nav-open")) {
            document.body.classList.remove("staff-nav-open");
            const t = document.querySelector('[data-nav-toggle="staff"]');
            const o = document.querySelector('[data-nav-overlay="staff"]');
            if (t) t.setAttribute("aria-expanded", "false");
            if (o) o.hidden = true;
        }
    });
}

function initReveal() {
    const els = document.querySelectorAll("[data-reveal]");
    if (!els.length) return;

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        els.forEach((el) => el.classList.add("is-visible"));
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    io.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: "0px 0px -32px 0px" }
    );

    els.forEach((el) => {
        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight * 0.92) {
            el.classList.add("is-visible");
        }
        io.observe(el);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initResponsiveNav();
    initReveal();
    if (document.querySelector("[data-carousel]")) {
        initBuilderEvents();
        renderBuilder();
    }
});
