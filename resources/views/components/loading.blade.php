<div id="app-loader"
    style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center;">
    <div class="spinner-loading-rounded">
        <svg class="spinner-svg" viewBox="0 0 50 50">
            <circle class="spinner-circle" cx="25" cy="25" r="20"></circle>
        </svg>
    </div>
</div>

<style>
    #app-loader {
        background-color: #ffffff;
        opacity: 1;
    }

    html[data-bs-theme="dark"] #app-loader {
        background-color: #121212;
    }

    html[data-bs-theme="light"] #app-loader {
        background-color: #ffffff;
    }

    #app-loader.is-hiding {
        opacity: 0;
        transition: opacity .25s ease;
    }
</style>

<script>
    (function() {
        const LOADER_ID = 'app-loader';

        function el() {
            return document.getElementById(LOADER_ID);
        }

        function show() {
            const loader = el();
            if (!loader) return;

            loader.classList.remove('is-hiding');
            loader.style.display = 'flex';
            loader.style.opacity = '1';
        }

        function hide() {
            const loader = el();
            if (!loader) return;

            // fade out
            loader.classList.add('is-hiding');

            window.setTimeout(() => {
                // fully remove
                loader.style.display = 'none';
                loader.classList.remove('is-hiding');
            }, 260);
        }

        // ✅ Normal full page load: hide after everything is loaded
        window.addEventListener('load', () => {
            requestAnimationFrame(hide);
        });

        // ✅ BFCache restore (back/forward)
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) hide();
        });

        // ✅ Livewire navigate: show when navigating, hide when navigated
        document.addEventListener('livewire:navigating', () => show());
        document.addEventListener('livewire:navigated', () => hide());

        // ✅ Safety: if Livewire initializes after a partial swap, ensure not stuck
        document.addEventListener('livewire:load', () => hide());

        // optional manual control
        window.AppLoader = {
            show,
            hide
        };
    })();
</script>
