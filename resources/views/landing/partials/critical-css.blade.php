<style>
    html { background: #fff; }
    body.landing-page { margin: 0; min-width: 320px; overflow-x: hidden; background: #fff; color: #101114; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; line-height: 1.5; letter-spacing: 0; -webkit-font-smoothing: antialiased; }
    .landing-page *, .landing-page *::before, .landing-page *::after { box-sizing: border-box; }
    .landing-page a { color: inherit; }
    .landing-shell { width: min(1200px, calc(100% - 40px)); margin: 0 auto; }
    .landing-page img, .landing-page video { max-width: 100%; }
    .landing-mobile-toggle { position: fixed; inset: auto auto 0 0; opacity: 0; pointer-events: none; }
    .landing-nav { position: sticky; top: 0; z-index: 40; background: rgba(255, 255, 255, 0.92); border-bottom: 1px solid rgba(230, 232, 236, 0.9); }
    .landing-nav-inner { min-height: 76px; display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 24px; }
    .brand-link { display: inline-flex; align-items: center; gap: 10px; color: #101114; font-size: 1.12rem; font-weight: 950; text-decoration: none; }
    .brand-link img { width: 38px; height: 38px; border-radius: 12px; object-fit: cover; }
    .landing-menu { justify-self: center; display: inline-flex; align-items: center; gap: 4px; padding: 5px; border: 1px solid #e6e8ec; border-radius: 999px; background: #fff; }
    .landing-menu a { padding: 10px 14px; border-radius: 999px; color: #303642; font-size: .9rem; font-weight: 750; text-decoration: none; }
    .landing-nav-actions { display: inline-flex; align-items: center; gap: 10px; }
    .landing-menu-open, .landing-menu-close, .landing-mobile-backdrop, .landing-mobile-drawer { display: none; }
    .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0 22px; border: 1px solid transparent; border-radius: 999px; font-weight: 850; font-size: .95rem; line-height: 1; text-decoration: none; }
    .btn--sm { min-height: 40px; padding-inline: 16px; font-size: .88rem; }
    .btn--primary { background: #ff6b00; color: #fff; }
    .btn--ghost { background: #fff; color: #101114; border-color: #e6e8ec; }
    .btn--dark { background: #101114; color: #fff; }
    .landing-hero { padding: clamp(72px, 10vw, 128px) 0 clamp(58px, 9vw, 96px); background: #fff; overflow: hidden; }
    .hero-layout { display: grid; grid-template-columns: minmax(0, 1.02fr) minmax(360px, .78fr); align-items: center; gap: clamp(38px, 7vw, 90px); }
    .hero-content { max-width: 690px; }
    .hero-badge { display: inline-flex; align-items: center; gap: 10px; width: fit-content; padding: 8px 12px 8px 8px; border: 1px solid #ffd8bd; border-radius: 999px; background: #fff7f1; color: #78401d; font-size: .9rem; font-weight: 800; }
    .hero-badge span { display: inline-flex; padding: 5px 9px; border-radius: 999px; background: #ff6b00; color: #fff; font-size: .72rem; letter-spacing: .06em; }
    .hero-content h1 { max-width: 780px; margin: 22px 0 18px; color: #101114; font-size: clamp(3rem, 7.2vw, 6.15rem); line-height: .94; letter-spacing: 0; }
    .hero-content h1 strong { color: #ff6b00; }
    .hero-content p { max-width: 560px; margin: 0; color: #5d6675; font-size: clamp(1.08rem, 2vw, 1.35rem); }
    .action-row, .hero-trust { display: flex; flex-wrap: wrap; gap: 12px; }
    .action-row { margin-top: 28px; }
    .hero-trust { margin-top: 24px; }
    .hero-trust span { display: inline-flex; align-items: center; gap: 8px; color: #303642; font-size: .95rem; font-weight: 800; }
    .hero-phone-demo { justify-self: center; width: min(100%, 420px); padding: 12px; border: 1px solid #e6e8ec; border-radius: 38px; background: #fff; box-shadow: 0 24px 70px rgba(16, 17, 20, .08); }
    .hero-phone-frame { position: relative; aspect-ratio: 9 / 18.6; overflow: hidden; border: 10px solid #111; border-radius: 32px; background: #111; }
    .hero-phone-notch { position: absolute; top: 10px; left: 50%; z-index: 2; width: 90px; height: 22px; transform: translateX(-50%); border-radius: 999px; background: #111; }
    .hero-phone-video, .hero-phone-fallback, .hero-phone-fallback img { width: 100%; height: 100%; display: block; object-fit: cover; }
    @media (max-width: 1080px) {
        .hero-layout { grid-template-columns: 1fr; text-align: center; }
        .hero-content { margin-inline: auto; }
        .hero-content p, .action-row, .hero-trust { justify-content: center; margin-inline: auto; }
    }
    @media (max-width: 860px) {
        .landing-shell { width: min(100% - 28px, 1200px); }
        .landing-nav-inner { grid-template-columns: auto 1fr auto; min-height: 68px; }
        .landing-menu, .landing-nav-actions .login-link { display: none; }
        .landing-menu-open { display: inline-grid; width: 42px; height: 42px; place-items: center; border: 1px solid #e6e8ec; border-radius: 50%; background: #fff; cursor: pointer; }
        .landing-menu-open span, .landing-menu-open span::before, .landing-menu-open span::after { content: ""; display: block; width: 18px; height: 2px; border-radius: 999px; background: #101114; }
        .landing-menu-open span { position: relative; }
        .landing-menu-open span::before, .landing-menu-open span::after { position: absolute; left: 0; }
        .landing-menu-open span::before { top: -6px; }
        .landing-menu-open span::after { top: 6px; }
    }
    @media (max-width: 640px) {
        .landing-hero { padding-top: 48px; }
        .hero-content h1 { font-size: clamp(2.75rem, 15vw, 4.25rem); }
        .landing-nav-actions { justify-self: end; }
        .landing-nav-actions .btn { min-width: 126px; width: auto; }
        .hero-phone-demo { width: min(100%, 340px); border-radius: 30px; }
        .hero-phone-frame { border-width: 8px; border-radius: 26px; }
    }
</style>
