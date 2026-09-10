<div id="appLoader" class="loader-overlay">
    <div class="loader-stage">
        <div class="loader-spin-wrap">
            <svg class="loader-svg" viewBox="0 0 220 220" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="110" cy="110" r="92" stroke="#E8A33D" stroke-width="1.5" stroke-opacity="0.18" />
                <g class="loader-arc">
                    <circle cx="110" cy="110" r="92" stroke="#E8A33D" stroke-width="2.5" stroke-linecap="round"
                        stroke-dasharray="140 38 60 50" stroke-dashoffset="30" />
                    <circle cx="110" cy="18" r="3" fill="#E8A33D" />
                </g>
                <g class="loader-arc2">
                    <circle cx="110" cy="110" r="98" stroke="#E8A33D" stroke-width="1" stroke-linecap="round"
                        stroke-opacity="0.65" stroke-dasharray="4 14 8 20" />
                </g>
            </svg>
            <div class="loader-badge">
                <div class="loader-mono"><span class="loader-i">I</span><span class="loader-m">M</span></div>
            </div>
        </div>

        <h1 class="loader-title">Inventory Manager</h1>
        <p class="loader-sub">Solar Stock Control</p>
    </div>

    <p class="loader-msg">Loading, please wait...</p>
</div>

<style>
.loader-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background-color: #F6F4EF;
    background-image: radial-gradient(rgba(28, 35, 51, 0.05) 1.2px, transparent 1.2px);
    background-size: 24px 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 1;
    transition: opacity 0.45s ease;
}
.loader-overlay.hidden {
    opacity: 0;
    pointer-events: none;
}
.loader-overlay.removed { display: none; }
.loader-stage { text-align: center; margin-bottom: 40px; }
.loader-spin-wrap {
    position: relative;
    width: 190px;
    height: 190px;
    margin: 0 auto 26px;
}
.loader-svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}
.loader-arc {
    animation: loaderSpin 2.4s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    transform-origin: center;
}
.loader-arc2 {
    animation: loaderSpin2 3.2s linear infinite;
    transform-origin: center;
}
@keyframes loaderSpin { to { transform: rotate(360deg); } }
@keyframes loaderSpin2 { to { transform: rotate(360deg); } }
.loader-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100px;
    height: 100px;
    background: #1C2333;
    border: 2px solid #E8A33D;
    border-radius: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 28px -6px rgba(28, 35, 51, 0.16), 0 4px 12px -2px rgba(28, 35, 51, 0.08);
    animation: loaderBreathe 4s ease-in-out infinite;
}
@keyframes loaderBreathe {
    0%, 100% { opacity: 0.85; transform: translate(-50%, -50%) scale(1); }
    50% { opacity: 1; transform: translate(-50%, -50%) scale(1.02); }
}
.loader-mono {
    font-family: 'Sora', sans-serif;
    font-weight: 800;
    font-size: 34px;
    line-height: 1;
    letter-spacing: -1px;
    display: flex;
}
.loader-i { color: #E8A33D; }
.loader-m { color: #FFFFFF; }
.loader-title {
    font-family: 'Sora', sans-serif;
    font-weight: 700;
    color: #1C2333;
    font-size: 26px;
    letter-spacing: 0.03em;
    line-height: 1.2;
    margin: 0;
}
@supports not (font-family: 'Sora') { .loader-title, .loader-mono { font-family: 'Arial Black', Arial, sans-serif; } }
.loader-sub {
    margin: 6px 0 0;
    font-family: 'Plus Jakarta Sans', 'Inter', Arial, sans-serif;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: #767C74;
}
.loader-msg {
    font-family: 'Plus Jakarta Sans', 'Inter', Arial, sans-serif;
    font-size: 13px;
    font-weight: 500;
    color: #9AA0A4;
    animation: loaderPulse 1.6s ease-in-out infinite;
}
@keyframes loaderPulse { 0%, 100% { opacity: 0.55; } 50% { opacity: 1; } }
@media (prefers-reduced-motion: reduce) {
    .loader-arc, .loader-arc2, .loader-badge, .loader-msg { animation: none; }
}
</style>

<script>
(function(){
    function hideLoader(){
        var el = document.getElementById('appLoader');
        if (!el) return;
        el.classList.add('hidden');
        setTimeout(function(){ el.classList.add('removed'); }, 550);
    }
    // Full page load ke baad hide; safety timeout agar load event kat ho jaye
    if (document.readyState === 'complete') { hideLoader(); }
    else {
        window.addEventListener('load', function(){ setTimeout(hideLoader, 350); });
        setTimeout(hideLoader, 6000); // maximum 6 sec, phir zaroor hide
    }
})();
</script>