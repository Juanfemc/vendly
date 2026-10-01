<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('meta_title', 'Vendly Panel')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/vendly-logo.svg') }}">
    <link rel="shortcut icon" href="{{ asset('images/vendly-logo.svg') }}">
    <style>
        :root {
            --vendly-brand: #ff6a00;
            --vendly-brand-dark: #111111;
            --vendly-brand-soft: #fff3e8;
            --vendly-brand-tint: #fed7aa;
            --vendly-brand-focus: rgba(255, 106, 0, 0.13);
            --vendly-brand-shadow: rgba(255, 106, 0, 0.22);
            --vendly-brand-contrast: #ffffff;
        }

        /* Vendly Suite admin refresh: visual layer only, existing flows preserved. */
        :root {
            --admin-bg: #f3f7fb;
            --admin-surface: #ffffff;
            --admin-surface-soft: #f8fafc;
            --admin-line: #dfe7ef;
            --admin-text: #07142f;
            --admin-muted: #61708a;
            --admin-orange: #ff6a00;
            --admin-orange-dark: #e85d00;
            --admin-orange-soft: #fff0e6;
            --admin-shadow: 0 16px 40px rgba(8, 20, 47, .07);
            --admin-radius: 12px;
        }

        body {
            background: var(--admin-bg) !important;
            color: var(--admin-text);
        }

        .container {
            grid-template-columns: 248px minmax(0, 1fr);
            gap: 0;
            background: var(--admin-bg);
        }

        .sidebar {
            width: 248px;
            padding: 22px 14px !important;
            background: rgba(255, 255, 255, .96) !important;
            border-right: 1px solid var(--admin-line) !important;
            box-shadow: 10px 0 34px rgba(8, 20, 47, .035);
        }

        .sidebar-brand-title,
        .sidebar-store-text strong,
        .sidebar-plan-text strong {
            color: var(--admin-text) !important;
        }

        .sidebar-brand-subtitle,
        .sidebar-store-text span,
        .sidebar-plan-text span {
            color: var(--admin-muted) !important;
            letter-spacing: 0;
            text-transform: none;
        }

        .sidebar-notification-link,
        .sidebar-store-card,
        .sidebar-plan-card {
            border: 1px solid var(--admin-line) !important;
            background: var(--admin-surface) !important;
            color: var(--admin-text) !important;
            box-shadow: 0 10px 22px rgba(8, 20, 47, .045);
        }

        .sidebar-nav {
            scrollbar-color: rgba(255, 106, 0, .65) rgba(8, 20, 47, .06);
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(8, 20, 47, .06);
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 106, 0, .65);
        }

        .sidebar-section {
            border-top-color: #eef2f7 !important;
        }

        .sidebar-section-label {
            color: #94a3b8 !important;
            letter-spacing: .08em;
        }

        .sidebar-nav-link,
        .sidebar-menu-group summary,
        .sidebar .sidebar-submenu a {
            color: var(--admin-text) !important;
            border-radius: var(--admin-radius);
        }

        .sidebar-nav-link:hover,
        .sidebar-menu-group summary:hover,
        .sidebar .sidebar-submenu a:hover,
        .sidebar-store-card:hover,
        .sidebar-plan-card:hover {
            background: var(--admin-orange-soft) !important;
            color: var(--admin-orange) !important;
            transform: none !important;
        }

        .sidebar-nav-link.is-active,
        .sidebar-menu-group[open] summary,
        .sidebar .sidebar-submenu a.is-active {
            background: var(--admin-orange-soft) !important;
            color: var(--admin-orange) !important;
            box-shadow: inset 3px 0 0 var(--admin-orange) !important;
        }

        .sidebar-nav-icon {
            color: currentColor !important;
        }

        .sidebar-submenu {
            border-left-color: #edf2f7 !important;
        }

        .sidebar-plan-icon {
            background: var(--admin-orange) !important;
            color: #ffffff !important;
        }

        .sidebar-plan-mini,
        .notification-badge {
            background: var(--admin-orange) !important;
            color: #ffffff !important;
        }

        .sidebar-logout-button {
            color: var(--admin-muted) !important;
            border: 1px solid transparent;
        }

        .sidebar-logout-button:hover {
            background: #fff1f1 !important;
            color: #dc2626 !important;
        }

        .sidebar-notification-dot {
            background: var(--admin-orange) !important;
            box-shadow: 0 0 0 3px #ffffff !important;
        }

        .main {
            padding: clamp(18px, 2.4vw, 28px) !important;
        }

        .admin-topbar {
            margin-bottom: 18px;
            padding: 10px;
            border: 1px solid var(--admin-line);
            border-radius: 18px;
            background: rgba(255, 255, 255, .86);
            box-shadow: 0 10px 26px rgba(8, 20, 47, .045);
        }

        .notification-toggle,
        .notification-dropdown,
        .card,
        .list-card,
        .resource-card,
        .panel-empty,
        .dashboard-welcome-card,
        .dashboard-progress-card,
        .dashboard-products-panel,
        .product-editor-card,
        .product-editor-preview-panel,
        .products-toolbar,
        .products-summary-card,
        .products-grid-card,
        .categories-console-card,
        .categories-form-card,
        .categories-table-card,
        .catalog-settings-card,
        .catalog-url-card,
        .payment-method,
        .payments-panel__notice,
        .payment-method__action,
        .template-card,
        .coupon-card,
        .landing-editor-card,
        .store-landing-card {
            border: 1px solid var(--admin-line) !important;
            border-radius: var(--admin-radius) !important;
            background: var(--admin-surface) !important;
            box-shadow: var(--admin-shadow) !important;
        }

        .header {
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .header h2,
        .products-console-title h1,
        .categories-console-title h1,
        .product-editor-hero h2,
        .catalog-settings-title h2,
        .payments-panel__title,
        .payment-method__title {
            color: var(--admin-text) !important;
            font-size: clamp(28px, 3.4vw, 42px) !important;
            letter-spacing: -.02em;
        }

        .btn,
        .products-action,
        .categories-action,
        .product-editor-submit,
        .onboarding-actions .btn {
            background: var(--admin-orange) !important;
            border-color: var(--admin-orange) !important;
            color: #ffffff !important;
            border-radius: var(--admin-radius) !important;
            box-shadow: 0 12px 24px rgba(255, 106, 0, .22) !important;
        }

        .btn:hover,
        .products-action:hover,
        .categories-action:hover,
        .product-editor-submit:hover {
            background: var(--admin-orange-dark) !important;
            transform: translateY(-1px);
        }

        .btn-secondary,
        .products-action--secondary,
        .categories-action--secondary,
        .product-editor-actions .btn-secondary {
            background: #ffffff !important;
            border-color: var(--admin-line) !important;
            color: var(--admin-text) !important;
            box-shadow: 0 10px 22px rgba(8, 20, 47, .06) !important;
        }

        .btn-danger {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
            color: #ffffff !important;
            box-shadow: 0 12px 24px rgba(220, 38, 38, .18) !important;
        }

        input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="color"]),
        select,
        textarea,
        .products-search-field input {
            border: 1px solid #d8e2ec !important;
            border-radius: var(--admin-radius) !important;
            background: #ffffff !important;
            color: var(--admin-text) !important;
            box-shadow: none !important;
        }

        input:focus,
        select:focus,
        textarea:focus,
        .products-search-field input:focus {
            outline: none !important;
            border-color: var(--admin-orange) !important;
            box-shadow: 0 0 0 4px rgba(255, 106, 0, .13) !important;
        }

        table,
        .dashboard-subscription-table,
        .products-table,
        .categories-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        thead th,
        .products-table th,
        .categories-table th {
            background: #f4f7fb !important;
            color: #526078 !important;
            font-weight: 800 !important;
        }

        tbody tr,
        .products-table tr,
        .categories-table tr {
            border-color: #edf2f7 !important;
        }

        .resource-badge--active,
        .resource-badge--success,
        .products-status--available,
        .categories-status--active {
            border-color: #bbf7d0 !important;
            background: #dcfce7 !important;
            color: #166534 !important;
        }

        .resource-badge--warning,
        .products-status--warning {
            border-color: #fed7aa !important;
            background: #fff7ed !important;
            color: #c2410c !important;
        }

        .resource-badge--danger,
        .resource-badge--inactive,
        .products-status--soldout,
        .categories-status--inactive {
            border-color: #fecaca !important;
            background: #fee2e2 !important;
            color: #991b1b !important;
        }

        .products-tab.is-active,
        .categories-tab.is-active,
        .dashboard-segment.is-active,
        .dashboard-segment:hover {
            background: var(--admin-orange-soft) !important;
            color: var(--admin-orange) !important;
            box-shadow: inset 0 0 0 1px rgba(255, 106, 0, .38) !important;
        }

        .products-tab:hover,
        .categories-tab:hover {
            color: var(--admin-orange) !important;
        }

        .products-search-icon,
        .categories-search-icon,
        .dashboard-kicker,
        .product-editor-preview-panel__head span,
        .payments-panel__eyebrow,
        .catalog-settings-back,
        .catalog-link-action,
        .catalog-count-badge {
            color: var(--admin-orange) !important;
        }

        .catalog-settings-title p,
        .catalog-card-title p,
        .payments-panel__subtitle,
        .payment-method__copy,
        .payment-method__action-copy {
            color: var(--admin-muted) !important;
        }

        .catalog-card-icon,
        .catalog-social-icon,
        .payments-panel__notice-icon,
        .payment-method__icon,
        .product-editor-card__icon {
            background: var(--admin-orange-soft) !important;
            color: var(--admin-orange) !important;
        }

        .catalog-plan-pill,
        .catalog-pro-pill,
        .payment-status,
        .product-editor-preview-badges span {
            background: var(--admin-orange-soft) !important;
            color: var(--admin-orange) !important;
            border: 1px solid rgba(255, 106, 0, .24) !important;
        }

        .catalog-settings-back:hover {
            background: var(--admin-orange-soft) !important;
        }

        .dashboard-action-card--primary,
        .dashboard-stat-card--sales {
            background: linear-gradient(135deg, var(--admin-orange), #ff8a33) !important;
            border-color: rgba(255, 106, 0, .42) !important;
        }

        .dashboard-stat-card--sales .dashboard-stat-value,
        .dashboard-stat-card--sales .dashboard-stat-label,
        .dashboard-stat-card--sales .dashboard-stat-note {
            color: #ffffff !important;
        }

        .dashboard-progress-track span {
            background: linear-gradient(90deg, var(--admin-orange), #ff8a33) !important;
        }

        .admin-mobile-bottom-nav {
            border-top: 1px solid var(--admin-line) !important;
            background: rgba(255, 255, 255, .96) !important;
        }

        .admin-mobile-bottom-nav a,
        .admin-mobile-bottom-nav button {
            color: var(--admin-muted) !important;
        }

        .admin-mobile-bottom-nav .is-active {
            color: var(--admin-orange) !important;
        }

        @media (max-width: 900px) {
            .container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                background: #ffffff !important;
            }
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255, 106, 0, 0.08), transparent 34vw),
                linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
            color: #111827;
            overflow-x: clip;
        }

        * {
            box-sizing: border-box;
        }

        a,
        button,
        label,
        summary,
        [role="button"] {
            -webkit-tap-highlight-color: transparent;
        }

        a:focus:not(:focus-visible),
        button:focus:not(:focus-visible),
        label:focus:not(:focus-visible),
        summary:focus:not(:focus-visible),
        [role="button"]:focus:not(:focus-visible) {
            outline: none;
        }

        a:focus-visible,
        button:focus-visible,
        [role="button"]:focus-visible {
            outline: 2px solid rgba(255, 106, 0, 0.7);
            outline-offset: 3px;
        }

        img,
        table {
            max-width: 100%;
        }

        .container {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            align-items: start;
            width: 100%;
            max-width: 100vw;
            min-height: 100vh;
            overflow-x: clip;
        }

        .mobile-topbar {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .mobile-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #111827;
        }

        .mobile-brand img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: block;
        }

        .sidebar {
            width: 260px;
            position: sticky;
            top: 0;
            align-self: start;
            height: 100vh;
            height: 100dvh;
            background: linear-gradient(180deg, #111111 0%, #1a1a1a 100%);
            padding: 24px 18px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            box-sizing: border-box;
            flex-shrink: 0;
            overflow: hidden;
            overscroll-behavior: contain;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
            padding: 8px 10px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-brand img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            min-width: 0;
        }

        .sidebar-brand-title {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: #ffffff;
        }

        .sidebar-brand-subtitle {
            margin: 4px 0 0;
            font-size: 12px;
            color: #ff8a33;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .sidebar a {
            display: block;
            margin: 8px 0;
            padding: 10px 12px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            transition: background .2s ease, color .2s ease, transform .2s ease;
        }

        .sidebar a:hover {
            background: rgba(255, 106, 0, 0.14);
            color: #ffffff;
            transform: translateX(2px);
        }

        .sidebar-menu-group {
            margin: 8px 0;
        }

        .sidebar-menu-group summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            list-style: none;
            transition: background .2s ease, color .2s ease;
        }

        .sidebar-menu-group summary::-webkit-details-marker {
            display: none;
        }

        .sidebar-menu-group summary::after {
            content: "v";
            font-size: 16px;
            line-height: 1;
            transition: transform .2s ease;
        }

        .sidebar-menu-group[open] summary {
            background: rgba(255, 106, 0, 0.14);
            color: #ffffff;
        }

        .sidebar-menu-group[open] summary::after {
            transform: rotate(180deg);
        }

        .sidebar-submenu {
            display: grid;
            gap: 4px;
            margin: 6px 0 10px;
            padding-left: 10px;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .sidebar-submenu a {
            margin: 0;
            padding: 9px 12px;
            color: rgba(255, 255, 255, 0.78);
            font-size: 13px;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 0;
        }

        .sidebar,
        .sidebar * {
            min-width: 0;
        }

        .sidebar-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .sidebar-brand {
            flex: 1;
            min-width: 0;
            margin-bottom: 0;
            padding: 6px 2px;
            border-bottom: 0;
        }

        .sidebar-brand img {
            width: 40px;
            height: 40px;
        }

        .sidebar-brand-title {
            font-size: 18px;
            line-height: 1.1;
        }

        .sidebar-notification-link {
            position: relative;
            width: 38px;
            height: 38px;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            margin: 0 !important;
            padding: 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
        }

        .sidebar-notification-link svg {
            width: 18px;
            height: 18px;
        }

        .sidebar-notification-dot {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #ff6a00;
            box-shadow: 0 0 0 3px #151515;
        }

        .sidebar-nav {
            display: grid;
            gap: 6px;
            flex: 1 1 auto;
            min-height: 0;
            max-width: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 4px 0 10px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 106, 0, .6) rgba(255, 255, 255, .06);
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 7px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(255, 106, 0, .6);
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, .06);
        }

        .sidebar-section {
            display: grid;
            gap: 6px;
            min-width: 0;
            max-width: 100%;
            padding: 12px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-section:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .sidebar-section-label {
            margin: 0 0 2px;
            padding: 0 12px;
            color: rgba(255, 255, 255, 0.42);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .sidebar-nav-link,
        .sidebar-menu-group summary {
            min-height: 42px;
        }

        .sidebar-nav-link,
        .sidebar .sidebar-submenu a,
        .sidebar-menu-group summary {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            max-width: 100%;
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
            overflow: hidden;
            white-space: nowrap;
        }

        .sidebar-nav-link span,
        .sidebar .sidebar-submenu a,
        .sidebar-menu-group summary span:first-child,
        .sidebar-plan-text,
        .sidebar-store-text {
            min-width: 0;
            max-width: 100%;
        }

        .sidebar-nav-link > span,
        .sidebar .sidebar-submenu a,
        .sidebar-menu-group summary span:first-child {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-nav-link.is-active,
        .sidebar .sidebar-submenu a.is-active {
            background: rgba(255, 106, 0, 0.18);
            color: #ffffff;
            box-shadow: inset 3px 0 0 #ff6a00;
        }

        .sidebar-nav-icon {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            color: #ff8a33;
        }

        .sidebar-submenu {
            margin: 6px 0 0 14px;
            padding-left: 12px;
        }

        .sidebar .sidebar-submenu a {
            min-height: 36px;
            padding: 8px 10px;
            font-weight: 600;
        }

        .sidebar-plan-mini {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            min-height: 18px;
            margin-left: auto;
            padding: 2px 7px;
            border-radius: 999px;
            background: #8b7cff;
            color: #ffffff;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .03em;
        }

        .sidebar-menu-group summary span:first-child {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .sidebar-footer {
            flex: 0 0 auto;
            display: grid;
            gap: 10px;
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-store-card,
        .sidebar-plan-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.82);
            text-decoration: none;
        }

        .sidebar-store-card {
            justify-content: space-between;
        }

        .sidebar-store-card:hover,
        .sidebar-plan-card:hover {
            background: rgba(255, 106, 0, 0.14);
            transform: none;
        }

        .sidebar-store-text,
        .sidebar-plan-text {
            min-width: 0;
            display: grid;
            gap: 2px;
        }

        .sidebar-store-text strong,
        .sidebar-plan-text strong {
            overflow: hidden;
            color: #ffffff;
            font-size: 13px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-store-text span,
        .sidebar-plan-text span {
            overflow: hidden;
            color: rgba(255, 255, 255, 0.55);
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-plan-card {
            background: rgba(255, 106, 0, 0.13);
            border: 1px solid rgba(255, 106, 0, 0.18);
        }

        .sidebar-plan-icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: 10px;
            background: #ff6a00;
            color: #ffffff;
        }

        .sidebar-logout-form {
            margin: 0;
        }

        .sidebar-logout-button {
            width: 100%;
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 0;
            border-radius: 12px;
            background: transparent;
            color: rgba(255, 255, 255, 0.62);
            font-weight: 800;
            cursor: pointer;
            transition: background .2s ease, color .2s ease;
        }

        .sidebar-logout-button:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
        }

        .sidebar-logout-button svg,
        .sidebar-store-card svg,
        .sidebar-plan-card svg {
            width: 17px;
            height: 17px;
            flex: 0 0 auto;
        }

        .sidebar .sidebar-notification-link {
            margin: 0 !important;
            padding: 0 !important;
        }

        .sidebar .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            padding: 10px 12px;
        }

        .sidebar .sidebar-store-card,
        .sidebar .sidebar-plan-card {
            display: flex;
            margin: 0;
            padding: 11px 12px;
        }

        .sidebar .sidebar-plan-icon .sidebar-nav-icon {
            color: #ffffff;
        }

        .admin-mobile-bottom-nav {
            display: none;
        }

        .admin-mobile-bottom-nav svg {
            width: 20px;
            height: 20px;
            flex: 0 0 auto;
        }

        .main {
            padding: clamp(18px, 3vw, 30px);
            box-sizing: border-box;
            min-width: 0;
            width: auto;
            max-width: 100%;
            overflow-x: clip;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
            padding: clamp(18px, 3vw, 26px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.86);
            box-shadow: 0 18px 46px rgba(15, 23, 42, 0.06);
            backdrop-filter: blur(10px);
        }

        .header h2 {
            margin: 0;
            color: #0f172a;
            font-size: clamp(26px, 4vw, 40px);
            line-height: 1;
            letter-spacing: 0;
        }

        .admin-brand-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
            padding: 22px;
            border: 1px solid rgba(255, 106, 0, 0.18);
            border-radius: 20px;
            background:
                radial-gradient(circle at 12% 20%, rgba(255, 106, 0, 0.18), transparent 28%),
                linear-gradient(135deg, #151515 0%, #242424 58%, #ff6a00 180%);
            color: #ffffff;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.16);
        }

        .admin-brand-hero::after {
            content: "";
            position: absolute;
            inset: auto -60px -120px auto;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(255, 106, 0, 0.18);
            pointer-events: none;
        }

        .admin-brand-copy {
            position: relative;
            z-index: 1;
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-brand-mark {
            width: 50px;
            height: 50px;
            flex: 0 0 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 16px;
            background: #111111;
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.22);
        }

        .admin-brand-mark img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .admin-brand-eyebrow {
            margin: 0 0 4px;
            color: #ffb178;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .admin-brand-title {
            margin: 0;
            color: #ffffff;
            font-size: clamp(24px, 3vw, 34px);
            line-height: 1.08;
            letter-spacing: 0;
        }

        .admin-brand-text {
            max-width: 620px;
            margin: 8px 0 0;
            color: rgba(255, 255, 255, 0.76);
            line-height: 1.55;
        }

        .admin-brand-actions {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .admin-brand-actions .btn,
        .admin-brand-actions .btn-secondary {
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
        }

        @media (max-width: 760px) {
            .admin-brand-hero {
                align-items: stretch;
                flex-direction: column;
                padding: 18px;
                border-radius: 18px;
            }

            .admin-brand-copy {
                align-items: flex-start;
            }

            .admin-brand-mark {
                width: 44px;
                height: 44px;
                flex-basis: 44px;
                border-radius: 14px;
            }

            .admin-brand-actions,
            .admin-brand-actions .btn,
            .admin-brand-actions .btn-secondary {
                width: 100%;
            }
        }

        .admin-topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .notification-menu {
            position: relative;
        }

        .notification-toggle {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            min-height: 42px;
            padding: 9px 13px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #ffffff;
            color: #111827;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .notification-toggle svg {
            width: 18px;
            height: 18px;
        }

        .notification-badge {
            min-width: 22px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            background: #ff6a00;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            line-height: 1;
        }

        .notification-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: min(360px, calc(100vw - 40px));
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 22px 50px rgba(15, 23, 42, 0.16);
            z-index: 20;
        }

        .notification-dropdown[hidden] {
            display: none;
        }

        .notification-dropdown-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 8px 8px 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .notification-dropdown-head strong {
            font-size: 14px;
            color: #111827;
        }

        .notification-dropdown-head a,
        .notification-item a {
            color: #ff6a00;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        .notification-list {
            display: grid;
            gap: 8px;
            padding: 10px 0 0;
        }

        .notification-item {
            display: block;
            padding: 11px;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            background: #fff7ed;
            text-decoration: none;
            color: inherit;
        }

        .notification-item:hover {
            border-color: #fed7aa;
        }

        .notification-item strong {
            display: block;
            margin-bottom: 4px;
            color: #111827;
            font-size: 14px;
        }

        .notification-item span {
            display: block;
            color: #64748b;
            font-size: 13px;
            line-height: 1.35;
        }

        .notification-empty {
            padding: 18px 10px;
            color: #64748b;
            text-align: center;
            font-size: 14px;
        }

        .notification-card.is-unread {
            border-color: #fed7aa;
            background: #fffaf4;
        }

        .notification-card.is-read {
            opacity: 0.82;
        }

        .card,
        .list-card {
            background: rgba(255, 255, 255, 0.94);
            padding: 18px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            border-radius: 18px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.055);
        }

        .list-card {
            margin-bottom: 16px;
        }

        .panel-list {
            display: grid;
            gap: 16px;
        }

        .panel-empty {
            padding: 34px;
            border: 1px dashed #cbd5e1;
            border-radius: 20px;
            background: #ffffff;
            text-align: center;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.05);
        }

        .panel-empty h3 {
            margin: 0 0 8px;
            color: #111827;
            font-size: 20px;
        }

        .panel-empty p {
            margin: 0 0 18px;
            color: #6b7280;
            line-height: 1.5;
        }

        .resource-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: start;
            padding: 20px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.055);
        }

        .resource-card--with-media {
            grid-template-columns: minmax(120px, 180px) minmax(0, 1fr) auto;
        }

        .resource-card__media {
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 16px;
            overflow: hidden;
            background: #f3f4f6;
        }

        .resource-card__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .resource-card__main {
            min-width: 0;
        }

        .resource-card__header {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
        }

        .resource-card__title {
            margin: 0;
            color: #111827;
            font-size: 18px;
            line-height: 1.2;
        }

        .resource-card__subtitle {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .resource-card__description {
            margin: 12px 0 0;
            color: #4b5563;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .resource-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .resource-badge {
            min-height: 28px;
            padding: 7px 11px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e5e7eb;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .resource-badge--active,
        .resource-badge--success {
            background: #dcfce7;
            color: #166534;
        }

        .resource-badge--inactive,
        .resource-badge--danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .resource-badge--warning {
            background: #fef3c7;
            color: #92400e;
        }

        .resource-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        .resource-metric {
            min-width: 0;
            padding: 12px;
            border: 1px solid #e8edf4;
            border-radius: 14px;
            background: #f8fafc;
        }

        .resource-metric__label {
            display: block;
            margin-bottom: 6px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .resource-metric__value {
            color: #111827;
            font-size: 14px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .resource-metric__value--warning {
            color: #b45309;
        }

        .resource-metric__value--danger {
            color: #991b1b;
        }

        .resource-actions {
            min-width: 180px;
            display: grid;
            gap: 10px;
            justify-items: stretch;
        }

        .resource-actions form {
            margin: 0;
        }

        .resource-actions .btn {
            width: 100%;
        }

        .btn-warning {
            background: #f59e0b;
            color: #ffffff;
        }

        .btn-success {
            background: #16a34a;
            color: #ffffff;
        }

        .btn-muted {
            background: #6b7280;
            color: #ffffff;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 16px;
            background: #111827;
            color: white;
            border-radius: 12px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            line-height: 1.2;
            min-height: 42px;
            font-weight: 800;
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.12);
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(17, 24, 39, 0.16);
        }

        .btn-secondary {
            background: #ffffff;
            color: #111827;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
        }

        .btn-danger {
            background: #dc2626;
            color: #ffffff;
        }

        .delete-confirm-modal[hidden] {
            display: none;
        }

        .delete-confirm-modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        body.delete-confirm-open {
            overflow: hidden;
        }

        .delete-confirm-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(17, 24, 39, 0.58);
        }

        .delete-confirm-dialog {
            position: relative;
            width: min(100%, 420px);
            padding: 22px;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
        }

        .delete-confirm-dialog h2 {
            margin: 0 0 8px;
            color: #111827;
            font-size: 22px;
            line-height: 1.15;
        }

        .delete-confirm-dialog p {
            margin: 0;
            color: #4b5563;
            line-height: 1.55;
        }

        .delete-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .admin-pagination {
            overflow-x: auto;
        }

        .admin-pagination nav {
            display: flex;
            justify-content: center;
        }

        .admin-pagination .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
            flex-wrap: wrap;
        }

        .admin-pagination .page-link {
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #374151;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            text-decoration: none;
        }

        .admin-pagination .page-item.active .page-link {
            border-color: #4f46e5;
            background: #4f46e5;
            color: #ffffff;
        }

        .admin-pagination .page-item.disabled .page-link {
            opacity: 0.45;
            cursor: not-allowed;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        textarea.long-textarea {
            min-height: 180px;
            resize: vertical;
            line-height: 1.6;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-break: normal;
            text-align: left;
            direction: ltr;
        }

        .field-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }

        .order-filter-panel {
            display: grid;
            grid-template-columns: minmax(180px, 260px) minmax(0, 1fr);
            gap: 10px 14px;
            align-items: end;
        }

        .order-filter-panel .field-label {
            grid-column: 1 / -1;
            margin-bottom: 0;
        }

        .order-filter-panel select {
            margin-bottom: 0;
        }

        .order-filter-count {
            color: #6b7280;
            font-size: 14px;
            font-weight: 700;
            padding-bottom: 11px;
        }

        .product-search-panel {
            display: grid;
            gap: 10px;
        }

        .product-search-panel .field-label {
            margin: 0;
        }

        .product-search-panel__controls {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) auto auto;
            gap: 10px;
            align-items: center;
        }

        .product-search-panel__controls input {
            margin: 0;
        }

        .ai-assistant-panel {
            display: grid;
            gap: 12px;
            min-width: 0;
            max-width: 100%;
            margin: 0 0 16px;
            padding: 14px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
        }

        .ai-assistant-panel__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            min-width: 0;
        }

        .ai-assistant-panel__head > div {
            min-width: 0;
        }

        .ai-assistant-panel__head h3 {
            margin: 0;
            color: #111827;
            font-size: 16px;
        }

        .ai-assistant-panel__head p,
        .ai-assistant-status {
            margin: 4px 0 0;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.45;
        }

        .ai-assistant-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
            gap: 8px;
            align-items: center;
            min-width: 0;
            max-width: 100%;
        }

        .ai-assistant-actions .btn {
            width: 100%;
            min-width: 0;
            min-height: 36px;
            padding: 8px 12px;
            font-size: 13px;
            white-space: normal;
        }

        .ai-assistant-status.is-error {
            color: #991b1b;
        }

        .ai-assistant-credits,
        .ai-assistant-packages {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            color: #1f2937;
            font-size: 12px;
        }

        .ai-assistant-credits strong,
        .ai-assistant-packages span {
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #fff;
            padding: 5px 9px;
        }

        .ai-assistant-credits span {
            color: #64748b;
        }

        .ai-assistant-preview {
            display: grid;
            gap: 8px;
            min-width: 0;
            max-width: 100%;
        }

        .ai-assistant-preview[hidden] {
            display: none;
        }

        .ai-assistant-preview img {
            display: block;
            width: min(100%, 360px);
            max-width: 100%;
            max-height: 220px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            object-fit: cover;
        }

        .ai-assistant-preview p {
            margin: 0;
            color: #2563eb;
            font-size: 13px;
        }

        .ai-credit-admin-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .ai-credit-admin-form select {
            width: min(100%, 220px);
            min-height: 38px;
            margin: 0;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            color: #111827;
            font-size: 13px;
        }

        .rich-editor {
            margin-bottom: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
        }

        .rich-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .rich-toolbar button {
            width: auto;
            min-height: 34px;
            margin: 0;
            padding: 0 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            cursor: pointer;
            font-size: 13px;
        }

        .rich-content {
            min-height: 150px;
            padding: 12px;
            line-height: 1.6;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-break: normal;
            text-align: left;
            direction: ltr;
            outline: none;
        }

        .rich-content:empty::before {
            content: "Escribe caracteristicas, beneficios, materiales, garantias o cuidados del producto...";
            color: #9ca3af;
        }

        input[type="file"] {
            padding: 12px;
            border: 1px dashed #4f46e5;
            background: #eef2ff;
            color: #312e81;
        }

        input[type="file"]::file-selector-button {
            margin-right: 12px;
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            background: #4f46e5;
            color: #ffffff;
            cursor: pointer;
        }

        input[type="file"]::file-selector-button:hover {
            background: #4338ca;
        }

        .flash {
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 8px;
        }

        .flash.success {
            background: #dcfce7;
            color: #166534;
        }

        .flash.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .thumb {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            margin-bottom: 10px;
        }

        .product-image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
            gap: 12px;
            min-width: 0;
            max-width: 100%;
            margin: 0 0 18px;
        }

        .product-image-preview[hidden] {
            display: none;
        }

        .product-image-preview-item {
            min-width: 0;
            display: grid;
            gap: 6px;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
        }

        .product-image-preview-item img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 8px;
            background: #f3f4f6;
        }

        .product-image-preview-item span {
            overflow: hidden;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-editor-page {
            width: min(100%, 1120px);
            max-width: 100%;
            min-width: 0;
            margin: 0 auto;
            padding-bottom: 86px;
            overflow-x: clip;
        }

        .product-editor-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin: 0 0 24px;
            padding: 4px 0 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .product-editor-title {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 14px;
        }

        .product-editor-title > div {
            min-width: 0;
        }

        .product-editor-back {
            width: 40px;
            height: 40px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #ffffff;
            color: #111827;
            text-decoration: none;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .product-editor-back:hover {
            transform: translateX(-2px);
            border-color: #ff9a3d;
            box-shadow: 0 10px 24px rgba(255, 106, 0, .14);
        }

        .product-editor-back svg {
            width: 18px;
            height: 18px;
        }

        .product-editor-hero h2 {
            margin: 0;
            overflow-wrap: anywhere;
            color: #111827;
            font-size: clamp(24px, 3vw, 30px);
            letter-spacing: 0;
        }

        .product-editor-hero p {
            margin: 4px 0 0;
            overflow-wrap: anywhere;
            color: #0f766e;
            font-size: 14px;
            line-height: 1.5;
        }

        .product-editor-form {
            display: grid;
            gap: 22px;
            min-width: 0;
            max-width: 100%;
        }

        .product-editor-card {
            padding: 24px;
            min-width: 0;
            max-width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .06);
        }

        .product-editor-card__head {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            min-width: 0;
            margin-bottom: 22px;
        }

        .product-editor-card__head > div {
            min-width: 0;
        }

        .product-editor-card__icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: 13px;
            background: #fff3e8;
            color: #ff6a00;
        }

        .product-editor-card__icon svg {
            width: 20px;
            height: 20px;
        }

        .product-editor-card__head h3 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            line-height: 1.2;
        }

        .product-editor-card__head p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .product-editor-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            min-width: 0;
            max-width: 100%;
        }

        .product-editor-grid--three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .product-editor-grid--wide {
            align-items: end;
        }

        .product-editor-field {
            display: grid;
            gap: 8px;
            min-width: 0;
            margin-bottom: 18px;
        }

        .product-editor-grid .product-editor-field,
        .product-editor-grid--three .product-editor-field {
            margin-bottom: 0;
        }

        .product-editor-field label {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .product-editor-field label span {
            color: #ef4444;
        }

        .product-editor-field small,
        .product-editor-upgrade span,
        .product-editor-current-media span {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
        }

        .product-editor-field input,
        .product-editor-field select,
        .product-editor-field textarea {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            min-height: 48px;
            margin: 0;
            padding: 12px 15px;
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #ffffff;
            color: #0f172a;
            font-size: 14px;
            box-shadow: none;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .product-editor-field textarea {
            min-height: 132px;
            resize: vertical;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-break: normal;
            text-align: left;
            direction: ltr;
        }

        .product-editor-field input:focus,
        .product-editor-field select:focus,
        .product-editor-field textarea:focus,
        .product-editor-rich:focus-within {
            border-color: #ff8a1f;
            box-shadow: 0 0 0 4px rgba(255, 106, 0, .10);
            outline: none;
        }

        .product-editor-rich {
            margin: 0;
            min-width: 0;
            max-width: 100%;
            border-radius: 14px;
        }

        .product-editor-rich .rich-toolbar {
            background: #f8fafc;
            overflow-x: hidden;
        }

        .product-editor-rich .rich-content {
            min-height: 150px;
            max-width: 100%;
            overflow-wrap: break-word;
            word-break: normal;
            white-space: pre-wrap;
            text-align: left;
            direction: ltr;
            padding: 14px 15px;
        }

        .product-editor-note,
        .product-editor-upgrade {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            background: #fff7ed;
            color: #9a3412;
            line-height: 1.5;
        }

        .product-editor-current-media {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            max-width: 100%;
            margin-bottom: 18px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
        }

        .product-editor-current-media img {
            width: 76px;
            height: 76px;
            flex: 0 0 auto;
            object-fit: cover;
            border-radius: 14px;
            background: #e5e7eb;
        }

        .product-editor-current-media div {
            min-width: 0;
        }

        .product-editor-current-media strong,
        .product-editor-upgrade strong {
            display: block;
            color: #111827;
            font-size: 14px;
        }

        .product-editor-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
            gap: 12px;
            min-width: 0;
            max-width: 100%;
            margin-bottom: 18px;
        }

        .product-editor-gallery label {
            display: grid;
            gap: 8px;
            min-width: 0;
            color: #4b5563;
            font-size: 12px;
        }

        .product-editor-gallery img {
            width: 100%;
            height: 96px;
            object-fit: cover;
            border-radius: 14px;
            background: #f3f4f6;
        }

        .product-editor-gallery span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .product-editor-gallery input {
            width: auto;
            margin: 0;
        }

        .product-editor-upload-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            min-width: 0;
            max-width: 100%;
        }

        .product-editor-upload,
        .product-editor-upgrade {
            position: relative;
            min-height: 164px;
            min-width: 0;
            max-width: 100%;
            display: grid;
            place-items: center;
            gap: 8px;
            padding: 22px;
            border: 1.5px dashed #cbd5e1;
            border-radius: 18px;
            background: #fbfdff;
            color: #0f172a;
            text-align: center;
            cursor: pointer;
            transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
        }

        .product-editor-upload:hover,
        .product-editor-upload:focus-within {
            transform: translateY(-1px);
            border-color: #ff6a00;
            background: #fff7ed;
            box-shadow: 0 12px 28px rgba(255, 106, 0, .10);
        }

        .product-editor-upload--accent {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .product-editor-upload svg {
            width: 30px;
            height: 30px;
            color: #ff6a00;
        }

        .product-editor-upload strong {
            font-size: 14px;
        }

        .product-editor-upload span {
            color: #64748b;
            font-size: 12px;
        }

        .product-editor-upload input[type="file"] {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            border: 0;
            opacity: 0;
            pointer-events: none;
        }

        .product-editor-options {
            display: grid;
            gap: 12px;
            min-width: 0;
            max-width: 100%;
            margin-top: 18px;
        }

        .product-editor-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            min-width: 0;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
            cursor: pointer;
        }

        .product-editor-switch strong {
            display: block;
            overflow-wrap: anywhere;
            color: #0f172a;
            font-size: 14px;
        }

        .product-editor-switch small {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
        }

        .product-editor-switch input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .product-editor-switch i {
            width: 46px;
            height: 26px;
            position: relative;
            flex: 0 0 auto;
            border-radius: 999px;
            background: #cbd5e1;
            transition: background .18s ease;
        }

        .product-editor-switch i::after {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: #ffffff;
            box-shadow: 0 2px 6px rgba(15, 23, 42, .22);
            transition: transform .18s ease;
        }

        .product-editor-switch input:checked + i {
            background: #ff6a00;
        }

        .product-editor-switch input:checked + i::after {
            transform: translateX(20px);
        }

        .product-editor-actions {
            position: sticky;
            bottom: 0;
            z-index: 20;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            min-width: 0;
            max-width: 100%;
            margin-top: 4px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 18px 18px 0 0;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 -14px 34px rgba(15, 23, 42, .10);
            backdrop-filter: blur(14px);
        }

        .product-editor-page .product-editor-back {
            color: var(--vendly-brand);
        }

        .product-editor-page .product-editor-back:hover {
            border-color: var(--vendly-brand);
            box-shadow: 0 10px 24px var(--vendly-brand-shadow);
        }

        .product-editor-page .product-editor-hero p,
        .product-editor-page .product-editor-upload svg {
            color: var(--vendly-brand);
        }

        .product-editor-page .product-editor-card__icon,
        .product-editor-page .product-editor-upload:hover,
        .product-editor-page .product-editor-upload:focus-within,
        .product-editor-page .product-editor-note,
        .product-editor-page .product-editor-upgrade {
            background: var(--vendly-brand-soft);
        }

        .product-editor-page .product-editor-card__icon {
            color: var(--vendly-brand);
        }

        .product-editor-page .product-editor-field input:focus,
        .product-editor-page .product-editor-field select:focus,
        .product-editor-page .product-editor-field textarea:focus,
        .product-editor-page .product-editor-rich:focus-within {
            border-color: var(--vendly-brand);
            box-shadow: 0 0 0 4px var(--vendly-brand-focus);
        }

        .product-editor-page .product-editor-upload:hover,
        .product-editor-page .product-editor-upload:focus-within,
        .product-editor-page .product-editor-upload--accent {
            border-color: var(--vendly-brand);
        }

        .product-editor-page .product-editor-switch input:checked + i,
        .product-editor-page .product-editor-actions .btn:not(.btn-secondary) {
            background: var(--vendly-brand);
            color: var(--vendly-brand-contrast);
        }

        @media (max-width: 900px) {
            html {
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
                overflow-x: clip;
                overscroll-behavior-x: none;
            }

            body {
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
                overflow-x: clip;
                overscroll-behavior-x: none;
            }

            .mobile-topbar {
                display: flex;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            .container {
                display: block;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: hidden;
                overflow-x: clip;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: min(82vw, 320px);
                height: 100vh;
                height: 100dvh;
                max-height: 100dvh;
                align-self: auto;
                z-index: 60;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transform: none;
                transition: opacity .18s ease, visibility .18s ease;
                overflow: hidden;
                overscroll-behavior: contain;
                box-shadow: 14px 0 32px rgba(17, 24, 39, 0.14);
            }

            .sidebar.is-open {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
                transform: none;
            }

            .main {
                padding: 18px 16px 110px;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                overflow-x: hidden;
                overflow-x: clip;
            }

            .is-product-editor-route,
            body.has-product-editor {
                touch-action: pan-y;
            }

            .is-product-editor-route .container,
            .is-product-editor-route .main,
            .is-product-editor-route .product-editor-page,
            body.has-product-editor .container,
            body.has-product-editor .main,
            body.has-product-editor .product-editor-page {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: hidden;
                overflow-x: clip;
            }

            .admin-mobile-bottom-nav {
                position: fixed;
                left: 12px;
                right: 12px;
                width: auto;
                max-width: calc(100vw - 24px);
                bottom: max(12px, env(safe-area-inset-bottom));
                z-index: 54;
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 6px;
                padding: 8px;
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 22px;
                background: rgba(17, 17, 17, 0.96);
                box-shadow: 0 18px 44px rgba(17, 24, 39, 0.22);
                backdrop-filter: blur(18px);
            }

            .admin-mobile-bottom-nav a,
            .admin-mobile-bottom-nav button {
                min-width: 0;
                min-height: 56px;
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 5px;
                border: 0;
                border-radius: 16px;
                background: transparent;
                color: rgba(255, 255, 255, 0.72);
                font: inherit;
                font-size: 11px;
                font-weight: 800;
                line-height: 1.05;
                text-decoration: none;
                cursor: pointer;
            }

            .admin-mobile-bottom-nav a.is-active,
            .admin-mobile-bottom-nav button.is-active,
            .admin-mobile-bottom-nav a:hover,
            .admin-mobile-bottom-nav button:hover {
                background: rgba(255, 106, 0, 0.18);
                color: #ffffff;
            }

            .admin-mobile-bottom-nav svg {
                color: #ff8a33;
            }

            .product-editor-page {
                padding-bottom: 156px;
                scroll-padding-bottom: 156px;
            }

            .is-product-editor-route .main {
                padding-bottom: 128px;
            }

            body:has(.product-editor-page) .main {
                padding-bottom: 128px;
            }

            body.has-product-editor .main {
                padding-bottom: 128px;
            }

            .is-product-editor-route .product-editor-page {
                padding-bottom: 156px;
                scroll-padding-bottom: 156px;
            }

            body:has(.product-editor-page) .product-editor-page {
                padding-bottom: 156px;
                scroll-padding-bottom: 156px;
            }

            body.has-product-editor .product-editor-page {
                padding-bottom: 156px;
                scroll-padding-bottom: 156px;
            }

            body:has(.product-editor-page) .admin-mobile-bottom-nav,
            body.has-product-editor .admin-mobile-bottom-nav {
                min-height: auto;
                padding: 7px;
                border-radius: 18px;
            }

            body:has(.product-editor-page) .admin-mobile-bottom-nav a,
            body:has(.product-editor-page) .admin-mobile-bottom-nav button,
            body.has-product-editor .admin-mobile-bottom-nav a,
            body.has-product-editor .admin-mobile-bottom-nav button {
                min-height: 48px;
                gap: 4px;
                border-radius: 13px;
                font-size: 10px;
            }

            .product-editor-hero {
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
            }

            .product-editor-title {
                width: 100%;
                align-items: flex-start;
            }

            .product-editor-hero .btn {
                width: 100%;
                justify-content: center;
            }

            .product-editor-card {
                padding: 18px;
                border-radius: 16px;
            }

            .product-editor-card__head {
                margin-bottom: 18px;
            }

            .product-editor-grid,
            .product-editor-grid--three,
            .product-editor-upload-grid {
                grid-template-columns: 1fr;
            }

            .product-editor-upload,
            .product-editor-upgrade {
                min-height: 144px;
            }

            .product-editor-actions {
                position: static;
                right: auto;
                bottom: auto;
                left: auto;
                flex-wrap: wrap;
                margin-bottom: 12px;
                border-radius: 18px;
            }

            .product-editor-card,
            .product-editor-field,
            .product-editor-rich,
            .product-editor-upload,
            .product-editor-upgrade,
            .product-editor-actions {
                scroll-margin-bottom: 140px;
            }

            .product-editor-actions .btn {
                flex: 1;
                min-width: 0;
                justify-content: center;
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(17, 24, 39, 0.35);
                opacity: 0;
                pointer-events: none;
                transition: opacity .2s ease;
                z-index: 55;
            }

            .sidebar-backdrop.is-visible {
                opacity: 1;
                pointer-events: auto;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .card,
            .list-card {
                padding: 16px;
                border-radius: 14px;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .header .btn,
            .list-card .btn {
                width: 100%;
            }

            .product-editor-actions .btn {
                width: auto;
            }

            .list-card form[style*="display:inline-block"],
            .list-card a.btn[style*="display:inline-block"] {
                display: block !important;
                width: 100%;
                margin: 8px 0 0 !important;
            }

            .list-card form[style*="display:inline-block"] .btn {
                width: 100%;
            }

            .resource-card,
            .resource-card--with-media {
                grid-template-columns: 1fr;
            }

            .resource-card__media {
                max-height: 260px;
            }

            .resource-card__header {
                display: grid;
            }

            .resource-badges {
                justify-content: flex-start;
            }

            .resource-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .resource-actions {
                min-width: 0;
            }

            .admin-pagination nav {
                justify-content: flex-start;
            }

            .admin-pagination .pagination {
                justify-content: flex-start;
                flex-wrap: nowrap;
                min-width: max-content;
            }
        }

        @media (max-width: 560px) {
            .product-editor-page {
                padding-bottom: 156px;
                scroll-padding-bottom: 156px;
            }

            .product-editor-hero h2 {
                font-size: 22px;
            }

            .product-editor-hero p,
            .product-editor-card__head p {
                font-size: 12px;
            }

            .product-editor-card {
                padding: 14px;
                border-radius: 14px;
            }

            .product-editor-card__head {
                gap: 10px;
            }

            .product-editor-card__icon {
                width: 34px;
                height: 34px;
                border-radius: 11px;
            }

            .product-editor-card__head h3 {
                font-size: 17px;
            }

            .product-editor-field input,
            .product-editor-field select,
            .product-editor-field textarea {
                min-height: 46px;
                padding: 11px 12px;
                border-radius: 12px;
                font-size: 14px;
            }

            .product-editor-current-media {
                align-items: flex-start;
                padding: 10px;
            }

            .product-editor-current-media img {
                width: 62px;
                height: 62px;
                border-radius: 12px;
            }

            .product-editor-gallery {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .product-editor-gallery img {
                height: auto;
                aspect-ratio: 1;
            }

            .product-editor-upload,
            .product-editor-upgrade {
                min-height: 132px;
                padding: 18px 14px;
                border-radius: 15px;
            }

            .product-editor-switch {
                align-items: flex-start;
                gap: 12px;
                padding: 12px;
                border-radius: 14px;
            }

            .product-editor-actions {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 12px;
            }

            .product-editor-actions .btn {
                width: 100%;
            }

            .is-product-editor-route .product-editor-page,
            body.has-product-editor .product-editor-page {
                padding-bottom: 164px;
                scroll-padding-bottom: 164px;
            }
        }

        @media (max-width: 720px) {
            .mobile-topbar {
                padding: 12px 14px;
            }

            .mobile-brand {
                font-size: 14px;
            }

            .sidebar {
                width: min(88vw, 320px);
                padding: 18px 14px 24px;
            }

            .sidebar h3 {
                font-size: 18px;
                margin-bottom: 14px;
            }

            .sidebar a {
                margin: 6px 0;
                padding: 12px 12px;
                font-size: 14px;
            }

            .sidebar .sidebar-notification-link {
                width: 38px;
                height: 38px;
                margin: 0 !important;
                padding: 0 !important;
            }

            .sidebar .sidebar-nav-link,
            .sidebar .sidebar-store-card,
            .sidebar .sidebar-plan-card {
                margin: 0;
            }

            .sidebar-menu-group summary {
                padding: 12px;
                font-size: 14px;
            }

            .main {
                padding: 14px 12px 112px;
            }

            .is-product-editor-route .main,
            body.has-product-editor .main {
                padding-bottom: 132px;
            }

            .header {
                align-items: stretch;
                flex-direction: column;
                margin-bottom: 16px;
                gap: 10px;
            }

            .header h2 {
                font-size: 22px;
                line-height: 1.15;
            }

            .card,
            .list-card {
                padding: 14px;
                border-radius: 12px;
            }

            input,
            textarea,
            select {
                width: 100%;
                padding: 12px 10px;
                font-size: 16px;
            }

            .order-filter-panel {
                grid-template-columns: 1fr;
            }

            .order-filter-count {
                padding-bottom: 0;
            }

            .product-search-panel__controls {
                grid-template-columns: 1fr;
            }

            .product-search-panel__controls .btn {
                width: 100%;
            }

            textarea.long-textarea {
                min-height: 220px;
            }

            .thumb {
                width: 100%;
                height: auto;
                max-height: 240px;
                margin-bottom: 12px;
            }

            input[type="file"]::file-selector-button {
                width: 100%;
                margin: 0 0 10px;
            }

            .product-editor-upload input[type="file"]::file-selector-button {
                width: auto;
                margin: 0;
            }

            .delete-confirm-dialog {
                padding: 18px;
            }

            .delete-confirm-actions {
                flex-direction: column-reverse;
            }

            .resource-metrics {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .mobile-topbar {
                padding: 10px 12px;
            }

            .mobile-brand {
                font-size: 13px;
            }

            .sidebar {
                width: 92vw;
                padding: 16px 12px 22px;
            }

            .main {
                padding: 12px 10px 104px;
            }

            .is-product-editor-route .main,
            body.has-product-editor .main {
                padding-bottom: 126px;
            }

            .header h2 {
                font-size: 20px;
            }

            .card,
            .list-card {
                padding: 12px;
                border-radius: 10px;
            }

            .sidebar a,
            .btn {
                font-size: 13px;
            }

            .admin-mobile-bottom-nav {
                left: 8px;
                right: 8px;
                max-width: calc(100vw - 16px);
                bottom: max(8px, env(safe-area-inset-bottom));
                border-radius: 18px;
            }

            .admin-mobile-bottom-nav a,
            .admin-mobile-bottom-nav button {
                min-height: 52px;
                border-radius: 13px;
                font-size: 10px;
            }

            .admin-mobile-bottom-nav svg {
                width: 18px;
                height: 18px;
            }
        }
        .vendly-page-subtitle {
            margin: 5px 0 0;
            color: var(--admin-muted);
            font-size: 16px;
            line-height: 1.35;
        }

        .admin-topbar-store {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 8px 12px;
            border-radius: 12px;
            color: #07142f;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .admin-topbar-store svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
        }

        .vendly-table-card {
            overflow: hidden;
            border: 1px solid var(--admin-line);
            border-radius: 12px;
            background: #ffffff;
        }

        .orders-table-card {
            overflow: visible;
        }

        @media (min-width: 761px) {
            .orders-table-card .vendly-table-scroll {
                overflow: visible;
            }
        }

        .vendly-table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .vendly-data-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
            font-size: 14px;
        }

        .vendly-data-table th,
        .vendly-data-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #e8eef5;
            color: #07142f;
            text-align: left;
            vertical-align: middle;
        }

        .vendly-data-table th {
            background: #f6f8fb;
            color: #53627a;
            font-size: 13px;
            font-weight: 700;
        }

        .vendly-data-table tr:last-child td {
            border-bottom: 0;
        }

        .vendly-data-table td small {
            display: block;
            margin-top: 4px;
            color: var(--admin-muted);
            font-size: 12px;
        }

        .vendly-product-cell {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: inherit;
            text-decoration: none;
        }

        .vendly-product-thumb {
            width: 48px;
            height: 48px;
            display: inline-grid;
            place-items: center;
            flex: 0 0 auto;
            overflow: hidden;
            border: 1px solid #edf2f7;
            border-radius: 9px;
            background: #f6f8fb;
            color: var(--admin-orange);
            font-weight: 800;
        }

        .vendly-product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .vendly-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .vendly-status.is-success {
            background: #dcfce7;
            color: #008236;
        }

        .vendly-status.is-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .vendly-status.is-warning {
            background: #ffedd5;
            color: #ea580c;
        }

        .vendly-status.is-info {
            background: #dbeafe;
            color: #2563eb;
        }

        .vendly-row-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .vendly-row-actions a,
        .vendly-row-actions button {
            border: 0;
            background: transparent;
            color: var(--admin-orange);
            font: inherit;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .vendly-row-actions button {
            color: #dc2626;
            padding: 0;
        }

        .vendly-order-actions {
            position: relative;
        }

        .vendly-order-actions summary {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 7px 12px;
            border: 1px solid #dfe7ef;
            border-radius: 10px;
            background: #ffffff;
            color: #07142f;
            font-weight: 800;
            cursor: pointer;
            list-style: none;
        }

        .vendly-order-actions summary::-webkit-details-marker {
            display: none;
        }

        .vendly-order-actions[open] summary {
            border-color: rgba(255, 107, 0, .32);
            color: var(--admin-orange);
        }

        .vendly-order-actions > div {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 10;
            display: grid;
            gap: 10px;
            width: 260px;
            padding: 12px;
            border: 1px solid #dfe7ef;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 18px 40px rgba(8, 20, 47, .14);
        }

        .vendly-order-actions form {
            display: grid;
            gap: 8px;
            margin: 0;
        }

        .vendly-order-actions .btn {
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            font-size: 13px;
        }

        .vendly-order-detail-row td {
            padding-top: 0;
            background: #ffffff;
        }

        .vendly-order-detail {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 0 0 12px;
            color: #53627a;
            font-size: 12px;
        }

        .vendly-order-detail span {
            display: inline-flex;
            gap: 4px;
            padding: 7px 10px;
            border-radius: 999px;
            background: #f6f8fb;
        }

        .vendly-order-products {
            width: 100%;
            border-radius: 10px !important;
        }

        .products-console {
            gap: 22px !important;
        }

        .products-console-head {
            align-items: flex-start !important;
        }

        .products-console-title h1 {
            margin: 0;
        }

        .products-console-title p {
            margin: 6px 0 0;
            color: var(--admin-muted);
            font-size: 16px;
        }

        .products-toolbar {
            grid-template-columns: minmax(280px, 1fr) auto !important;
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .products-toolbar-actions .btn {
            min-height: 44px;
        }

        .order-filter-panel {
            display: flex !important;
            align-items: center;
            gap: 12px;
            max-width: 100%;
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .order-filter-panel select {
            max-width: 240px;
            min-height: 44px !important;
        }

        .order-filter-count {
            color: var(--admin-muted);
            font-size: 13px;
            font-weight: 700;
        }

        @media (max-width: 760px) {
            .vendly-data-table {
                min-width: 720px;
            }

            .vendly-order-actions > div {
                right: auto;
                left: 0;
            }

            .products-console-head,
            .products-actions,
            .products-toolbar,
            .order-filter-panel {
                align-items: stretch !important;
                flex-direction: column;
                grid-template-columns: 1fr !important;
            }

            .products-actions,
            .products-toolbar-actions {
                width: 100%;
            }

            .products-actions .products-action,
            .products-toolbar-actions .btn,
            .products-toolbar-actions .btn-secondary,
            .order-filter-panel select {
                width: 100%;
            }
        }
        /* Sidebar-only redesign based on the Vendly Suite reference. */
        .container {
            grid-template-columns: 260px minmax(0, 1fr) !important;
        }

        .sidebar {
            width: 260px !important;
            height: 100vh !important;
            height: 100dvh !important;
            display: flex !important;
            flex-direction: column !important;
            padding: 0 !important;
            border-right: 1px solid #d8e2ec !important;
            background: #ffffff !important;
            box-shadow: none !important;
            overflow: hidden !important;
        }

        .sidebar-top {
            flex: 0 0 auto;
            padding: 16px 18px 10px !important;
            border-bottom: 0 !important;
        }

        .sidebar-brand {
            gap: 12px !important;
            padding: 0 !important;
        }

        .sidebar-brand img {
            width: 44px !important;
            height: 44px !important;
            border-radius: 999px !important;
            background: #050505;
        }

        .sidebar-brand-title {
            color: #07142f !important;
            font-size: 22px !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            letter-spacing: -.02em !important;
        }

        .sidebar-brand-subtitle {
            margin-top: 4px !important;
            color: #74829a !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            letter-spacing: .14em !important;
            text-transform: uppercase !important;
        }

        .sidebar-nav {
            flex: 1 1 auto !important;
            display: flex !important;
            flex-direction: column !important;
            min-height: 0 !important;
            padding: 0 12px 10px !important;
            overflow-y: visible !important;
            overflow-x: hidden !important;
            scrollbar-width: none !important;
            scrollbar-color: transparent transparent !important;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 0 !important;
            height: 0 !important;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent !important;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            border-radius: 999px !important;
            background: rgba(255, 107, 0, .42) !important;
        }

        .sidebar-section {
            display: grid !important;
            gap: 2px !important;
            padding: 6px 0 8px !important;
            border-top: 0 !important;
        }

        .sidebar-section + .sidebar-section {
            margin-top: 0 !important;
        }

        .sidebar-section-label {
            margin: 0 0 4px !important;
            padding: 0 14px !important;
            color: #7a88a0 !important;
            font-size: 11px !important;
            font-weight: 900 !important;
            letter-spacing: .08em !important;
            text-transform: uppercase !important;
        }

        .sidebar-nav-link,
        .sidebar-menu-group summary,
        .sidebar .sidebar-submenu a,
        .sidebar-store-card,
        .sidebar-plan-card,
        .sidebar-logout-button {
            min-height: 36px !important;
            padding: 7px 14px !important;
            border: 1px solid transparent !important;
            border-radius: 10px !important;
            background: transparent !important;
            color: #07142f !important;
            font-size: 14px !important;
            font-weight: 650 !important;
            line-height: 1.2 !important;
            box-shadow: none !important;
            transform: none !important;
        }

        .sidebar-nav-link,
        .sidebar-menu-group summary,
        .sidebar-store-card,
        .sidebar-plan-card,
        .sidebar-logout-button {
            position: relative;
            display: flex !important;
            align-items: center !important;
            gap: 14px !important;
        }

        .sidebar-nav-link::before,
        .sidebar-menu-group > summary::before,
        .sidebar-store-card::before,
        .sidebar-plan-card::before,
        .sidebar-logout-button::before {
            content: "";
            position: absolute;
            left: -12px;
            top: 7px;
            bottom: 7px;
            width: 2px;
            border-radius: 999px;
            background: transparent;
        }

        .sidebar-nav-icon {
            width: 19px !important;
            height: 19px !important;
            color: currentColor !important;
            stroke-width: 2 !important;
        }

        .sidebar-nav-link:hover,
        .sidebar-menu-group summary:hover,
        .sidebar .sidebar-submenu a:hover,
        .sidebar-store-card:hover,
        .sidebar-plan-card:hover {
            background: #fff4ec !important;
            color: #ff6b00 !important;
        }

        .sidebar-nav-link.is-active,
        .sidebar-menu-group[open] summary,
        .sidebar .sidebar-submenu a.is-active {
            background: #fff1e8 !important;
            color: #ff6b00 !important;
            border-color: #ffe0cc !important;
            box-shadow: none !important;
        }

        .sidebar-nav-link.is-active::before,
        .sidebar-menu-group[open] > summary::before,
        .sidebar-store-card.is-active::before,
        .sidebar-plan-card.is-active::before {
            background: #ff6b00;
        }

        .sidebar-menu-group {
            margin: 0 !important;
        }

        .sidebar-menu-group summary {
            justify-content: space-between !important;
        }

        .sidebar-menu-group summary span:first-child {
            gap: 12px !important;
        }

        .sidebar-menu-group summary::after {
            content: "›" !important;
            color: currentColor;
            font-size: 20px !important;
            transform: rotate(0deg) !important;
        }

        .sidebar-menu-group[open] summary::after {
            transform: rotate(90deg) !important;
        }

        .sidebar-submenu {
            display: grid !important;
            gap: 2px !important;
            margin: 2px 0 4px 33px !important;
            padding: 0 0 0 10px !important;
            border-left: 1px solid #e8eef5 !important;
        }

        .sidebar .sidebar-submenu a {
            min-height: 30px !important;
            padding: 5px 10px !important;
            color: #516078 !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            white-space: normal !important;
        }

        .sidebar-plan-mini {
            margin-left: auto !important;
            min-height: 22px !important;
            padding: 3px 8px !important;
            border: 1px solid #ff6b00 !important;
            border-radius: 999px !important;
            background: #ffffff !important;
            color: #ff6b00 !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
            text-transform: none !important;
        }

        .sidebar-footer {
            position: sticky !important;
            bottom: 0 !important;
            z-index: 2 !important;
            margin-top: auto !important;
            padding-top: 8px !important;
            padding-bottom: 2px !important;
            border-top: 1px solid #dfe7ef !important;
            background: #ffffff !important;
        }

        .sidebar-store-card,
        .sidebar-plan-card {
            align-items: center !important;
            justify-content: flex-start !important;
        }

        .sidebar-store-text,
        .sidebar-plan-text {
            display: grid !important;
            gap: 2px !important;
            min-width: 0 !important;
        }

        .sidebar-store-text strong,
        .sidebar-plan-text strong {
            color: #07142f !important;
            font-size: 14px !important;
            font-weight: 650 !important;
            overflow: visible !important;
            text-overflow: clip !important;
            white-space: normal !important;
        }

        .sidebar-store-text span {
            color: #74829a !important;
            font-size: 12px !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        .sidebar-logout-button {
            width: 100% !important;
            color: #63728a !important;
            cursor: pointer !important;
        }

        .sidebar-logout-button:hover {
            background: #f8fafc !important;
            color: #07142f !important;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: min(86vw, 310px) !important;
                max-width: min(86vw, 310px) !important;
                height: 100dvh !important;
                padding: 0 !important;
                transform: translateX(-105%);
                transition: transform .24s ease !important;
                z-index: 60 !important;
            }

            .sidebar.is-open {
                transform: translateX(0) !important;
            }

            .sidebar-top {
                padding: 20px 18px 14px !important;
            }

            .sidebar-nav {
                padding: 4px 12px 20px !important;
                overflow-y: auto !important;
                scrollbar-width: thin !important;
                scrollbar-color: rgba(255, 107, 0, .42) transparent !important;
            }

            .sidebar-nav::-webkit-scrollbar {
                width: 6px !important;
            }

            .mobile-topbar {
                min-height: 62px;
                padding: 10px 14px !important;
            }

            .mobile-sidebar-toggle {
                width: 44px;
                height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #dfe7ef;
                border-radius: 12px;
                background: #ffffff;
                color: #07142f;
            }

            .mobile-sidebar-toggle svg {
                width: 22px;
                height: 22px;
            }
        }

        /* Responsive guardrails: keep admin pages inside the viewport and scroll only the content that needs it. */
        html,
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden !important;
        }

        .container,
        .main,
        .header,
        .admin-topbar,
        .card,
        .list-card,
        .resource-card,
        .panel-empty,
        .vendly-table-card,
        .products-console,
        .products-toolbar,
        .products-owner-panel,
        .catalog-settings-shell,
        .catalog-settings-form,
        .catalog-settings-card,
        .product-editor-page,
        .product-editor-main,
        .product-editor-card,
        .onboarding-card {
            min-width: 0 !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        .main {
            width: 100%;
            overflow-x: hidden !important;
        }

        .header,
        .admin-topbar,
        .resource-card__header,
        .products-console-head,
        .products-actions,
        .products-toolbar-actions,
        .catalog-card-head,
        .catalog-settings-hero,
        .payment-method,
        .payment-method__main {
            min-width: 0 !important;
        }

        .header > *,
        .admin-topbar > *,
        .resource-card__main,
        .resource-card__header > *,
        .products-console-title,
        .catalog-card-title,
        .catalog-card-title > div,
        .payment-method__main,
        .notification-menu {
            min-width: 0 !important;
        }

        .header h2,
        .header p,
        .vendly-page-subtitle,
        .resource-card__title,
        .resource-card__subtitle,
        .resource-card__description,
        .products-console-title h1,
        .products-console-title p,
        .catalog-card-title h3,
        .catalog-card-title p {
            overflow-wrap: anywhere;
        }

        .vendly-table-card {
            overflow: hidden !important;
        }

        .vendly-table-scroll,
        .dashboard-users-table-wrap,
        .dashboard-subscription-table-wrap {
            width: 100%;
            max-width: 100%;
            overflow-x: auto !important;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        .vendly-data-table,
        .dashboard-users-table,
        .dashboard-subscription-table {
            max-width: none !important;
        }

        .vendly-data-table th,
        .vendly-data-table td {
            white-space: nowrap;
        }

        .vendly-data-table td small,
        .vendly-order-detail,
        .vendly-order-products {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        @media (min-width: 901px) {
            .main {
                max-width: calc(100vw - 260px) !important;
            }
        }

        @media (max-width: 900px) {
            .mobile-topbar {
                display: flex !important;
            }

            .container {
                width: 100% !important;
                max-width: 100vw !important;
                grid-template-columns: minmax(0, 1fr) !important;
                overflow-x: hidden !important;
            }

            .main {
                width: 100% !important;
                max-width: 100vw !important;
                padding: 14px !important;
                padding-bottom: calc(92px + env(safe-area-inset-bottom, 0px)) !important;
            }

            .admin-topbar {
                flex-wrap: wrap;
                gap: 10px;
            }

            .notification-menu,
            .notification-toggle,
            .admin-topbar-store {
                max-width: 100%;
            }

            .notification-toggle span:not(.notification-badge),
            .admin-topbar-store span {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .header,
            .products-console-head,
            .products-actions,
            .catalog-settings-hero,
            .catalog-card-head,
            .products-owner-panel,
            .payment-method {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .btn,
            .btn-secondary,
            .products-action,
            .categories-action,
            .product-editor-submit,
            .catalog-link-action,
            .payment-method__action {
                width: 100%;
                max-width: 100%;
                justify-content: center;
                white-space: normal !important;
            }

            .vendly-data-table {
                min-width: 720px !important;
            }

            .admin-pagination {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .admin-pagination .pagination {
                min-width: max-content;
                justify-content: flex-start !important;
            }
        }

        @media (max-width: 640px) {
            .main {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }

            .card,
            .list-card,
            .resource-card,
            .panel-empty,
            .catalog-settings-card,
            .product-editor-card,
            .onboarding-card {
                padding-left: 14px !important;
                padding-right: 14px !important;
            }

            .catalog-settings-grid,
            .catalog-settings-grid--three,
            .catalog-color-grid,
            .catalog-palette-grid,
            .catalog-url-row,
            .catalog-media-layout,
            .products-toolbar {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
    </style>
</head>

@php
    $adminBodyClasses = collect([
        request()->is('admin/products/create') || request()->is('admin/products/*/edit') ? 'is-product-editor-route' : null,
    ])->filter()->implode(' ');
@endphp

<body @if($adminBodyClasses) class="{{ $adminBodyClasses }}" @endif>
    <div class="mobile-topbar">
        <div class="mobile-brand">
            <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly">
            <span>{{ auth()->user()->isAdmin() ? 'Vendly Admin' : 'Vendly Store' }}</span>
        </div>
        <button type="button" class="mobile-sidebar-toggle" data-sidebar-toggle aria-expanded="false" aria-controls="adminSidebar" aria-label="Abrir menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 6h16"></path>
                <path d="M4 12h16"></path>
                <path d="M4 18h16"></path>
            </svg>
        </button>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="container">
        @include('layouts.partials.admin-sidebar')

        <main class="main">
            @php
                $layoutNotifications = collect();
                $layoutUnreadNotifications = 0;

                if (\App\Models\StoreNotification::supportsTable()) {
                    $layoutUser = auth()->user();
                    $layoutQuery = \App\Models\StoreNotification::query()->latest();

                    if (! $layoutUser?->isAdmin()) {
                        $layoutStoreIds = $layoutUser?->stores()->pluck('stores.id') ?? collect();

                        if ($layoutUser?->store_id) {
                            $layoutStoreIds->push($layoutUser->store_id);
                        }

                        $layoutStoreIds = $layoutStoreIds->filter()->unique()->values();
                        $layoutQuery = $layoutStoreIds->isEmpty()
                            ? $layoutQuery->whereRaw('1 = 0')
                            : $layoutQuery->whereIn('store_id', $layoutStoreIds);
                    }

                    $layoutUnreadNotifications = (clone $layoutQuery)->whereNull('read_at')->count();
                    $layoutNotifications = (clone $layoutQuery)->whereNull('read_at')->take(5)->get();
                }

                $layoutUser = $layoutUser ?? auth()->user();
                $layoutStore = $layoutUser?->store ?? $layoutUser?->stores()->first();
                $layoutStoreUrl = $layoutStore?->slug ? app(\App\Services\StorefrontUrlService::class)->publicHome($layoutStore) : null;
            @endphp

            @if(\App\Models\StoreNotification::supportsTable())
                <div class="admin-topbar">
                    <div class="notification-menu">
                        <button type="button" class="notification-toggle" data-notification-toggle aria-expanded="false" aria-controls="notificationDropdown">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span>Notificaciones</span>
                            @if($layoutUnreadNotifications > 0)
                                <span class="notification-badge">{{ $layoutUnreadNotifications > 99 ? '99+' : $layoutUnreadNotifications }}</span>
                            @endif
                        </button>

                        <div class="notification-dropdown" id="notificationDropdown" data-notification-dropdown hidden>
                            <div class="notification-dropdown-head">
                                <strong>Actividad reciente</strong>
                                <a href="{{ route('admin.notifications.index') }}">Ver todas</a>
                            </div>

                            @if($layoutNotifications->isEmpty())
                                <div class="notification-empty">No tienes notificaciones nuevas.</div>
                            @else
                                <div class="notification-list">
                                    @foreach($layoutNotifications as $layoutNotification)
                                        <a class="notification-item" href="{{ route('admin.notifications.read', ['notification' => $layoutNotification->id, 'redirect' => $layoutNotification->action_url ?: route('admin.notifications.index', [], false)]) }}">
                                            <strong>{{ $layoutNotification->title }}</strong>
                                            <span>{{ $layoutNotification->message }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($layoutStore)
                        <a href="{{ $layoutStoreUrl ?: '#' }}" class="admin-topbar-store" @if($layoutStoreUrl) target="_blank" rel="noopener noreferrer" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 10h16"></path>
                                <path d="M5 10l1-5h12l1 5"></path>
                                <path d="M6 10v10h12V10"></path>
                                <path d="M9 20v-6h6v6"></path>
                            </svg>
                            <span>{{ $layoutStore->name }}</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @php
        $mobileNavUser = auth()->user();
        $mobileNavIsActive = fn (...$patterns) => collect($patterns)->contains(fn ($pattern) => request()->is($pattern));
        $mobileNavClass = fn (...$patterns) => $mobileNavIsActive(...$patterns) ? 'is-active' : '';
        $mobileNavIcon = function (string $name): string {
            $icons = [
                'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
                'cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h8.72a2 2 0 0 0 2-1.61L23 6H6"/>',
                'box' => '<path d="m21 8-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
                'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
                'more' => '<circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>',
                'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
                'store' => '<path d="M4 10h16"/><path d="M5 10l1-5h12l1 5"/><path d="M6 10v10h12V10"/><path d="M9 20v-6h6v6"/>',
                'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 13.5a7.8 7.8 0 0 0 0-3l2-1.5-2-3.5-2.4 1a8.2 8.2 0 0 0-2.6-1.5L13.7 2h-4l-.3 3A8.2 8.2 0 0 0 6.8 6.5l-2.4-1-2 3.5 2 1.5a7.8 7.8 0 0 0 0 3l-2 1.5 2 3.5 2.4-1a8.2 8.2 0 0 0 2.6 1.5l.3 3h4l.3-3a8.2 8.2 0 0 0 2.6-1.5l2.4 1 2-3.5-2-1.5z"/>',
                'tag' => '<path d="M20.59 13.41 12 22l-9-9V4h9l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
                'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
                'chart' => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
                'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
                'image' => '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
                'chat' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>',
                'external' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>',
                'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
            ];

            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? $icons['home']) . '</svg>';
        };
    @endphp

    <nav class="admin-mobile-bottom-nav" aria-label="Navegación rápida del panel">
        <a href="/dashboard" class="{{ $mobileNavClass('dashboard') }}">
            {!! $mobileNavIcon('home') !!}
            <span>Inicio</span>
        </a>

        @if($mobileNavUser?->isAdmin())
            <a href="/admin/stores" class="{{ $mobileNavClass('admin/stores*') }}">
                {!! $mobileNavIcon('store') !!}
                <span>Tiendas</span>
            </a>
        @else
            <a href="/admin/orders" class="{{ $mobileNavClass('admin/orders*') }}">
                {!! $mobileNavIcon('cart') !!}
                <span>Pedidos</span>
            </a>
        @endif

        <a href="/admin/products" class="{{ $mobileNavClass('admin/products*') }}">
            {!! $mobileNavIcon('box') !!}
            <span>Productos</span>
        </a>

        <button type="button" class="{{ $mobileNavClass('admin/onboarding', 'admin/store-settings', 'admin/categories*', 'admin/coupons*', 'admin/payments*', 'admin/templates*', 'admin/notifications*', 'admin/whatsapp*', 'profile') }}" data-sidebar-toggle aria-expanded="false" aria-controls="adminSidebar">
            {!! $mobileNavIcon('more') !!}
            <span>Más</span>
        </button>
    </nav>

    <div class="delete-confirm-modal" data-delete-confirm-modal hidden>
        <div class="delete-confirm-backdrop" data-delete-confirm-cancel></div>
        <div class="delete-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle" aria-describedby="deleteConfirmMessage">
            <h2 id="deleteConfirmTitle">Confirmar eliminación</h2>
            <p id="deleteConfirmMessage" data-delete-confirm-message>Esta acción no se puede deshacer.</p>
            <div class="delete-confirm-actions">
                <button type="button" class="btn btn-secondary" data-delete-confirm-cancel>Cancelar</button>
                <button type="button" class="btn btn-danger" data-delete-confirm-submit>Eliminar</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const toggles = document.querySelectorAll('[data-sidebar-toggle]');
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            let lastToggle = null;

            if (!toggles.length || !sidebar || !backdrop) {
                return;
            }

            const setOpen = (open, trigger = null) => {
                if (open && trigger) {
                    lastToggle = trigger;
                }

                sidebar.classList.toggle('is-open', open);
                backdrop.classList.toggle('is-visible', open);
                document.body.classList.toggle('sidebar-open', open);
                toggles.forEach((toggle) => {
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                });

                if (open) {
                    window.setTimeout(() => {
                        sidebar.querySelector('a, button, summary')?.focus();
                    }, 80);
                } else if (lastToggle) {
                    lastToggle.focus();
                    lastToggle = null;
                }
            };

            toggles.forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    setOpen(!sidebar.classList.contains('is-open'), toggle);
                });
            });

            backdrop.addEventListener('click', () => setOpen(false));

            sidebar.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => setOpen(false));
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth > 900) {
                    setOpen(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setOpen(false);
                }
            });
        })();
    </script>
    <script>
        (() => {
            const toggle = document.querySelector('[data-notification-toggle]');
            const dropdown = document.querySelector('[data-notification-dropdown]');

            if (!toggle || !dropdown) {
                return;
            }

            const setOpen = (open) => {
                dropdown.hidden = !open;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            toggle.addEventListener('click', (event) => {
                event.stopPropagation();
                setOpen(dropdown.hidden);
            });

            dropdown.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            document.addEventListener('click', () => setOpen(false));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setOpen(false);
                }
            });
        })();
    </script>
    <script>
        (() => {
            const modal = document.querySelector('[data-delete-confirm-modal]');
            const message = document.querySelector('[data-delete-confirm-message]');
            const submitButton = document.querySelector('[data-delete-confirm-submit]');
            const cancelButtons = document.querySelectorAll('[data-delete-confirm-cancel]');
            let pendingForm = null;

            if (!modal || !message || !submitButton) {
                return;
            }

            const closeModal = () => {
                modal.hidden = true;
                document.body.classList.remove('delete-confirm-open');
                pendingForm = null;
            };

            const openModal = (form) => {
                pendingForm = form;
                message.textContent = form.dataset.confirmMessage || 'Esta acción no se puede deshacer.';
                modal.hidden = false;
                document.body.classList.add('delete-confirm-open');
                submitButton.focus();
            };

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('form[data-confirm-delete]');

                if (!form) {
                    return;
                }

                event.preventDefault();
                openModal(form);
            });

            submitButton.addEventListener('click', () => {
                if (!pendingForm) {
                    closeModal();
                    return;
                }

                HTMLFormElement.prototype.submit.call(pendingForm);
            });

            cancelButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });
        })();
    </script>
    <script>
        (() => {
            const productEditor = document.querySelector('.product-editor-page');

            if (!productEditor) {
                return;
            }

            document.body.classList.add('has-product-editor');

            const resetHorizontalScroll = () => {
                document.documentElement.scrollLeft = 0;
                document.body.scrollLeft = 0;
                window.scrollTo(0, window.scrollY);
            };

            resetHorizontalScroll();
            window.addEventListener('load', resetHorizontalScroll);
            window.addEventListener('scroll', resetHorizontalScroll, { passive: true });
            window.addEventListener('resize', resetHorizontalScroll);
            document.addEventListener('touchend', resetHorizontalScroll, { passive: true });

            const focusableSelector = 'input, textarea, select, [contenteditable="true"]';
            productEditor.addEventListener('focusin', (event) => {
                if (window.innerWidth > 900 || !event.target.matches(focusableSelector)) {
                    return;
                }

                window.setTimeout(() => {
                    event.target.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
                    resetHorizontalScroll();
                }, 120);
            });
        })();
    </script>
    <script src="{{ asset('js/image-upload-optimizer.js') }}?v={{ filemtime(public_path('js/image-upload-optimizer.js')) }}"></script>
    <script src="{{ asset('js/product-image-preview.js') }}?v={{ filemtime(public_path('js/product-image-preview.js')) }}"></script>
    @php
        $vendlyMetaEvents = collect(session('meta_pixel_events', []))
            ->filter(fn ($event) => is_array($event) && filled($event['event'] ?? null));
    @endphp
    @foreach($vendlyMetaEvents as $vendlyMetaEvent)
        @include('admin.partials.meta-pixel-event', $vendlyMetaEvent)
    @endforeach
    @stack('scripts')
</body>

</html>
