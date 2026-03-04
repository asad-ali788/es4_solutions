!(function (s) {
    "use strict";
    var e,
        t = localStorage.getItem("language"),
        a = "en";
    function n(e) {
        document.getElementById("header-lang-img") &&
            ("en" == e
                ? (document.getElementById("header-lang-img").src =
                    "assets/images/flags/us.jpg")
                : "sp" == e
                    ? (document.getElementById("header-lang-img").src =
                        "assets/images/flags/spain.jpg")
                    : "gr" == e
                        ? (document.getElementById("header-lang-img").src =
                            "assets/images/flags/germany.jpg")
                        : "it" == e
                            ? (document.getElementById("header-lang-img").src =
                                "assets/images/flags/italy.jpg")
                            : "ru" == e &&
                            (document.getElementById("header-lang-img").src =
                                "assets/images/flags/russia.jpg"),
                localStorage.setItem("language", e),
                null == (t = localStorage.getItem("language")) && n(a),
                s.getJSON("assets/lang/" + t + ".json", function (e) {
                    s("html").attr("lang", t),
                        s.each(e, function (e, t) {
                            "head" === e && s(document).attr("title", t.title),
                                s("[key='" + e + "']").text(t);
                        });
                }));
    }
    function r() {
        for (
            var e = document
                .getElementById("topnav-menu-content")
                .getElementsByTagName("a"),
            t = 0,
            s = e.length;
            t < s;
            t++
        )
            "nav-item dropdown active" ===
                e[t].parentElement.getAttribute("class") &&
                (e[t].parentElement.classList.remove("active"),
                    null !== e[t].nextElementSibling &&
                    e[t].nextElementSibling.classList.remove("show"));
    }
    function c(e) {
        1 == s("#light-mode-switch").prop("checked") &&
            "light-mode-switch" === e
            ? (s("html").removeAttr("dir"),
                s("#dark-mode-switch").prop("checked", !1),
                s("#rtl-mode-switch").prop("checked", !1),
                s("#dark-rtl-mode-switch").prop("checked", !1),
                "assets/css/bootstrap.min.css" !=
                s("#bootstrap-style").attr("href") &&
                s("#bootstrap-style").attr(
                    "href",
                    "assets/css/bootstrap.min.css"
                ),
                s("html").attr("data-bs-theme", "light"),
                "assets/css/app.min.css" != s("#app-style").attr("href") &&
                s("#app-style").attr("href", "assets/css/app.min.css"),
                sessionStorage.setItem("is_visited", "light-mode-switch"))
            : 1 == s("#dark-mode-switch").prop("checked") &&
                "dark-mode-switch" === e
                ? (s("html").removeAttr("dir"),
                    s("#light-mode-switch").prop("checked", !1),
                    s("#rtl-mode-switch").prop("checked", !1),
                    s("#dark-rtl-mode-switch").prop("checked", !1),
                    s("html").attr("data-bs-theme", "dark"),
                    "assets/css/bootstrap.min.css" !=
                    s("#bootstrap-style").attr("href") &&
                    s("#bootstrap-style").attr(
                        "href",
                        "assets/css/bootstrap.min.css"
                    ),
                    "assets/css/app.min.css" != s("#app-style").attr("href") &&
                    s("#app-style").attr("href", "assets/css/app.min.css"),
                    sessionStorage.setItem("is_visited", "dark-mode-switch"))
                : 1 == s("#rtl-mode-switch").prop("checked") &&
                    "rtl-mode-switch" === e
                    ? (s("#light-mode-switch").prop("checked", !1),
                        s("#dark-mode-switch").prop("checked", !1),
                        s("#dark-rtl-mode-switch").prop("checked", !1),
                        "assets/css/bootstrap-rtl.min.css" !=
                        s("#bootstrap-style").attr("href") &&
                        s("#bootstrap-style").attr(
                            "href",
                            "assets/css/bootstrap-rtl.min.css"
                        ),
                        "assets/css/app-rtl.min.css" != s("#app-style").attr("href") &&
                        s("#app-style").attr("href", "assets/css/app-rtl.min.css"),
                        s("html").attr("dir", "rtl"),
                        s("html").attr("data-bs-theme", "light"),
                        sessionStorage.setItem("is_visited", "rtl-mode-switch"))
                    : 1 == s("#dark-rtl-mode-switch").prop("checked") &&
                    "dark-rtl-mode-switch" === e &&
                    (s("#light-mode-switch").prop("checked", !1),
                        s("#rtl-mode-switch").prop("checked", !1),
                        s("#dark-mode-switch").prop("checked", !1),
                        "assets/css/bootstrap-rtl.min.css" !=
                        s("#bootstrap-style").attr("href") &&
                        s("#bootstrap-style").attr(
                            "href",
                            "assets/css/bootstrap-rtl.min.css"
                        ),
                        "assets/css/app-rtl.min.css" != s("#app-style").attr("href") &&
                        s("#app-style").attr("href", "assets/css/app-rtl.min.css"),
                        s("html").attr("dir", "rtl"),
                        s("html").attr("data-bs-theme", "dark"),
                        sessionStorage.setItem("is_visited", "dark-rtl-mode-switch"));
    }
    function l() {
        document.webkitIsFullScreen ||
            document.mozFullScreen ||
            document.msFullscreenElement ||
            (console.log("pressed"),
                s("body").removeClass("fullscreen-enable"));
    }
    s("#side-menu").metisMenu(),
        s("#vertical-menu-btn").on("click", function (e) {
            e.preventDefault(),
                s("body").toggleClass("sidebar-enable"),
                992 <= s(window).width()
                    ? s("body").toggleClass("vertical-collpsed")
                    : s("body").removeClass("vertical-collpsed");
        }),
        s("#sidebar-menu a").each(function () {
            var e = window.location.href.split(/[?#]/)[0];
            this.href == e &&
                (s(this).addClass("active"),
                    s(this).parent().addClass("mm-active"),
                    s(this).parent().parent().addClass("mm-show"),
                    s(this).parent().parent().prev().addClass("mm-active"),
                    s(this).parent().parent().parent().addClass("mm-active"),
                    s(this).parent().parent().parent().parent().addClass("mm-show"),
                    s(this)
                        .parent()
                        .parent()
                        .parent()
                        .parent()
                        .parent()
                        .addClass("mm-active"));
        }),
        s(document).ready(function () {
            var e;
            0 < s("#sidebar-menu").length &&
                0 < s("#sidebar-menu .mm-active .active").length &&
                300 <
                (e = s("#sidebar-menu .mm-active .active").offset().top) &&
                ((e -= 300),
                    s(".vertical-menu .simplebar-content-wrapper").animate(
                        { scrollTop: e },
                        "slow"
                    ));
        }),
        s(".navbar-nav a").each(function () {
            var e = window.location.href.split(/[?#]/)[0];
            this.href == e &&
                (s(this).addClass("active"),
                    s(this).parent().addClass("active"),
                    s(this).parent().parent().addClass("active"),
                    s(this).parent().parent().parent().addClass("active"),
                    s(this).parent().parent().parent().parent().addClass("active"),
                    s(this)
                        .parent()
                        .parent()
                        .parent()
                        .parent()
                        .parent()
                        .addClass("active"),
                    s(this)
                        .parent()
                        .parent()
                        .parent()
                        .parent()
                        .parent()
                        .parent()
                        .addClass("active"));
        }),
        s('[data-bs-toggle="fullscreen"]').on("click", function (e) {
            e.preventDefault(),
                s("body").toggleClass("fullscreen-enable"),
                document.fullscreenElement ||
                    document.mozFullScreenElement ||
                    document.webkitFullscreenElement
                    ? document.cancelFullScreen
                        ? document.cancelFullScreen()
                        : document.mozCancelFullScreen
                            ? document.mozCancelFullScreen()
                            : document.webkitCancelFullScreen &&
                            document.webkitCancelFullScreen()
                    : document.documentElement.requestFullscreen
                        ? document.documentElement.requestFullscreen()
                        : document.documentElement.mozRequestFullScreen
                            ? document.documentElement.mozRequestFullScreen()
                            : document.documentElement.webkitRequestFullscreen &&
                            document.documentElement.webkitRequestFullscreen(
                                Element.ALLOW_KEYBOARD_INPUT
                            );
        }),
        document.addEventListener("fullscreenchange", l),
        document.addEventListener("webkitfullscreenchange", l),
        document.addEventListener("mozfullscreenchange", l),
        s(".right-bar-toggle").on("click", function (e) {
            s("body").toggleClass("right-bar-enabled");
        }),
        s(document).on("click", "body", function (e) {
            0 < s(e.target).closest(".right-bar-toggle, .right-bar").length ||
                s("body").removeClass("right-bar-enabled");
        }),
        (function () {
            if (document.getElementById("topnav-menu-content")) {
                for (
                    var e = document
                        .getElementById("topnav-menu-content")
                        .getElementsByTagName("a"),
                    t = 0,
                    s = e.length;
                    t < s;
                    t++
                )
                    e[t].onclick = function (e) {
                        "#" === e.target.getAttribute("href") &&
                            (e.target.parentElement.classList.toggle("active"),
                                e.target.nextElementSibling.classList.toggle(
                                    "show"
                                ));
                    };
                window.addEventListener("resize", r);
            }
        })(),
        [].slice
            .call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            .map(function (e) {
                return new bootstrap.Tooltip(e);
            }),
        [].slice
            .call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            .map(function (e) {
                return new bootstrap.Popover(e);
            }),
        [].slice
            .call(document.querySelectorAll(".offcanvas"))
            .map(function (e) {
                return new bootstrap.Offcanvas(e);
            }),
        window.sessionStorage &&
        ((e = sessionStorage.getItem("is_visited"))
            ? (s(".right-bar input:checkbox").prop("checked", !1),
                s("#" + e).prop("checked", !0))
            : "rtl" === s("html").attr("dir") &&
                "dark" === s("html").attr("data-bs-theme")
                ? (s("#dark-rtl-mode-switch").prop("checked", !0),
                    s("#light-mode-switch").prop("checked", !1),
                    sessionStorage.setItem("is_visited", "dark-rtl-mode-switch"),
                    c(e))
                : "rtl" === s("html").attr("dir")
                    ? (s("#rtl-mode-switch").prop("checked", !0),
                        s("#light-mode-switch").prop("checked", !1),
                        sessionStorage.setItem("is_visited", "rtl-mode-switch"),
                        c(e))
                    : "dark" === s("html").attr("data-bs-theme")
                        ? (s("#dark-mode-switch").prop("checked", !0),
                            s("#light-mode-switch").prop("checked", !1),
                            sessionStorage.setItem("is_visited", "dark-mode-switch"),
                            c(e))
                        : sessionStorage.setItem("is_visited", "light-mode-switch")),
        s(
            "#light-mode-switch, #dark-mode-switch, #rtl-mode-switch, #dark-rtl-mode-switch"
        ).on("change", function (e) {
            c(e.target.id);
        }),
        s("#password-addon").on("click", function () {
            0 < s(this).siblings("input").length &&
                ("password" == s(this).siblings("input").attr("type")
                    ? s(this).siblings("input").attr("type", "input")
                    : s(this).siblings("input").attr("type", "password"));
        }),
        null != t && t !== a && n(t),
        s(".language").on("click", function (e) {
            n(s(this).attr("data-lang"));
        }),
        s(window).on("load", function () {
            s("#status").fadeOut(), s("#preloader").delay(350).fadeOut("slow");
        }),
        Waves.init(),
        s("#checkAll").on("change", function () {
            s(".table-check .form-check-input").prop(
                "checked",
                s(this).prop("checked")
            );
        }),
        s(".table-check .form-check-input").change(function () {
            s(".table-check .form-check-input:checked").length ==
                s(".table-check .form-check-input").length
                ? s("#checkAll").prop("checked", !0)
                : s("#checkAll").prop("checked", !1);
        });
})(jQuery);


// toaster message time out
$(document).ready(function () {
    setTimeout(function () {
        $("#flash-message").fadeOut("slow");
    }, 2000); // 2 seconds
});


// header search operation
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault(); // Prevent default Enter behavior (e.g., form submit on some browsers)
                this.form.submit(); // Submit the form manually
            }
        });
    }
});
// Save sidebar state whenever user clicks the toggle button
jQuery("#vertical-menu-btn").on("click", function () {
    if (window.innerWidth >= 992) {
        const isCollapsed = jQuery("body").hasClass("vertical-collpsed");
        localStorage.setItem("sidebar-collapsed", isCollapsed);
    } else {
        localStorage.setItem("sidebar-collapsed", "false");
    }
});

// ✅ On page load, apply saved state
document.addEventListener("DOMContentLoaded", function () {
    const isCollapsed = localStorage.getItem("sidebar-collapsed") === "true";
    if (isCollapsed && window.innerWidth >= 992) {
        document.body.classList.add("sidebar-enable", "vertical-collpsed");
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    const themeIcon = document.getElementById('theme-icon');
    const htmlEl = document.documentElement;

    // Load saved theme from localStorage
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        htmlEl.setAttribute('data-bs-theme', savedTheme);
        themeIcon.className = savedTheme === 'dark' ? 'bx bx-sun' : 'bx bx-moon';
    }

    // Toggle theme on click
    themeToggleBtn.addEventListener('click', () => {
        const currentTheme = htmlEl.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        htmlEl.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        // 🔥 Transition effect
        themeIcon.classList.add('fade-out');

        setTimeout(() => {
            // swap icon after fade out
            themeIcon.className = newTheme === 'dark' ? 'bx bx-sun' : 'bx bx-moon';
            themeIcon.classList.remove('fade-out');
            themeIcon.classList.add('fade-in');

            // clean up fade-in after transition
            setTimeout(() => {
                themeIcon.classList.remove('fade-in');
            }, 300);
        }, 300);
    });
});

document.addEventListener('livewire:init', () => {
    Livewire.on('show-toast', ({
        type,
        message
    }) => {
        showToast(type, message);
    });
});

// Auto append spinner to submit buttons (global)
// add data-loading-false to the button you dont need the spinner
// Use data-loading-text="Updating..." to pass custom spinner message 
document.addEventListener('submit', function (e) {
    const form = e.target;
    const btn = e.submitter || form.querySelector('button[type="submit"]:not([disabled])');
    if (!btn) return;

    // Opt-out
    if (btn.hasAttribute('data-loading-false')) return;

    // Defer so inline onsubmit/confirm runs first
    queueMicrotask(() => {
        // If confirm() returned false or any handler called preventDefault()
        if (e.defaultPrevented) return;

        if (btn.disabled) return;
        btn.disabled = true;

        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }

        const text = btn.dataset.loadingText || btn.textContent.trim();
        btn.classList.add('d-inline-flex', 'align-items-center', 'justify-content-center');
        btn.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            <span>${text}</span>
        `;
    });
}); // no "true"


// show broken image whereever its broken
const GLOBAL_IMAGE_FALLBACK = "/assets/images/avatar-broken.png";
document.addEventListener(
    "error",
    (e) => {
        const img = e.target;
        if (!(img instanceof HTMLImageElement)) return;

        // Prevent infinite loop
        if (img.dataset.fallbackApplied === "1") return;
        img.dataset.fallbackApplied = "1";

        img.src = img.dataset.fallback || GLOBAL_IMAGE_FALLBACK;
    },
    true // capture phase required for img error
);


function btnLoad(btn, text = 'Processing...', disable = true) {
    if (disable) {
        if (btn.disabled) return;
        btn.disabled = true;
    }

    btn.innerHTML =
        `<span class="spinner-border spinner-border-sm me-2"></span>${text}`;

    if (disable && btn.form) {
        btn.form.submit();
    }
}