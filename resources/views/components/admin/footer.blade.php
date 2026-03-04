<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="text-sm-end d-none d-sm-block">
                © {{ date('Y') }} Design & Develop by Itrend Solution.
            </div>
        </div>
    </div>
</footer>

<button id="back-to-top" class="btn btn-primary back-to-top-btn" style="display:none;">
    <i class="bx bx-up-arrow-alt fs-3 pt-2"></i>
</button>
<style>
    .back-to-top-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 999;
        width: 48px;
        height: 48px;
        padding: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        animation: backToTopBounce 2s infinite;
    }

    /* subtle jump animation */
    @keyframes backToTopBounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-6px);
        }
    }

    /* optional hover polish */
    .back-to-top-btn:hover {
        animation-play-state: paused;
        transform: translateY(-4px);
    }
</style>
