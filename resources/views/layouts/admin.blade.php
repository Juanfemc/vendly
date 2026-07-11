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

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
        }

        * {
            box-sizing: border-box;
        }

        img,
        table {
            max-width: 100%;
        }

        .container {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            align-items: start;
            min-height: 100vh;
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

        .menu-toggle {
            border: 1px solid #d1d5db;
            background: white;
            color: #111827;
            border-radius: 10px;
            width: 42px;
            height: 42px;
            padding: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .menu-toggle svg {
            width: 20px;
            height: 20px;
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
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
            white-space: normal;
            overflow-wrap: anywhere;
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

        .main {
            padding: 24px;
            box-sizing: border-box;
            min-width: 0;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
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
            background: white;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .list-card {
            margin-bottom: 16px;
        }

        .panel-list {
            display: grid;
            gap: 14px;
        }

        .panel-empty {
            padding: 28px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            text-align: center;
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
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
        }

        .resource-card--with-media {
            grid-template-columns: minmax(120px, 180px) minmax(0, 1fr) auto;
        }

        .resource-card__media {
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 10px;
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
            padding: 6px 10px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: #374151;
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
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
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
            padding: 10px 14px;
            background: #4f46e5;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            line-height: 1.2;
            min-height: 40px;
        }

        .btn-secondary {
            background: #ff6a00;
            color: #ffffff;
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
            grid-template-columns: repeat(auto-fit, minmax(150px, max-content));
            gap: 8px;
            align-items: center;
        }

        .ai-assistant-actions .btn {
            width: auto;
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
        }

        .ai-assistant-preview[hidden] {
            display: none;
        }

        .ai-assistant-preview img {
            display: block;
            width: min(100%, 360px);
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
            max-width: 1120px;
            margin: 0 auto;
            padding-bottom: 86px;
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
            color: #111827;
            font-size: clamp(24px, 3vw, 30px);
            letter-spacing: 0;
        }

        .product-editor-hero p {
            margin: 4px 0 0;
            color: #0f766e;
            font-size: 14px;
            line-height: 1.5;
        }

        .product-editor-form {
            display: grid;
            gap: 22px;
        }

        .product-editor-card {
            padding: 24px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .06);
        }

        .product-editor-card__head {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 22px;
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
            border-radius: 14px;
        }

        .product-editor-rich .rich-toolbar {
            background: #f8fafc;
        }

        .product-editor-rich .rich-content {
            min-height: 150px;
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
            margin-bottom: 18px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
        }

        .product-editor-current-media img {
            width: 76px;
            height: 76px;
            object-fit: cover;
            border-radius: 14px;
            background: #e5e7eb;
        }

        .product-editor-current-media strong,
        .product-editor-upgrade strong {
            display: block;
            color: #111827;
            font-size: 14px;
        }

        .product-editor-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }

        .product-editor-gallery label {
            display: grid;
            gap: 8px;
            width: 96px;
            color: #4b5563;
            font-size: 12px;
        }

        .product-editor-gallery img {
            width: 96px;
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
        }

        .product-editor-upload,
        .product-editor-upgrade {
            position: relative;
            min-height: 164px;
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
            margin-top: 18px;
        }

        .product-editor-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
            cursor: pointer;
        }

        .product-editor-switch strong {
            display: block;
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
                overflow-x: hidden;
            }

            .mobile-topbar {
                display: flex;
            }

            .container {
                display: block;
                min-width: 0;
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
                transform: translateX(-100%);
                transition: transform .22s ease;
                overflow: hidden;
                overscroll-behavior: contain;
                box-shadow: 14px 0 32px rgba(17, 24, 39, 0.14);
            }

            .sidebar.is-open {
                transform: translateX(0);
            }

            .main {
                padding: 18px 16px 28px;
                width: 100%;
                overflow-x: hidden;
            }

            .product-editor-page {
                padding-bottom: 104px;
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
                position: sticky;
                right: auto;
                bottom: 12px;
                left: auto;
                border-radius: 18px;
            }

            .product-editor-actions .btn {
                flex: 1;
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

        @media (max-width: 720px) {
            .mobile-topbar {
                padding: 12px 14px;
            }

            .mobile-brand {
                font-size: 14px;
            }

            .menu-toggle {
                width: 40px;
                height: 40px;
                border-radius: 9px;
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
                padding: 14px 12px 24px;
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
                padding: 12px 10px 20px;
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
        }
    </style>
</head>

<body>
    <div class="mobile-topbar">
        <div class="mobile-brand">
            <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly">
            <span>{{ auth()->user()->isAdmin() ? 'Vendly Admin' : 'Vendly Store' }}</span>
        </div>
        <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 7h16"></path>
                <path d="M4 12h16"></path>
                <path d="M4 17h16"></path>
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
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <div class="delete-confirm-modal" data-delete-confirm-modal hidden>
        <div class="delete-confirm-backdrop" data-delete-confirm-cancel></div>
        <div class="delete-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle" aria-describedby="deleteConfirmMessage">
            <h2 id="deleteConfirmTitle">Confirmar eliminacion</h2>
            <p id="deleteConfirmMessage" data-delete-confirm-message>Esta accion no se puede deshacer.</p>
            <div class="delete-confirm-actions">
                <button type="button" class="btn btn-secondary" data-delete-confirm-cancel>Cancelar</button>
                <button type="button" class="btn btn-danger" data-delete-confirm-submit>Eliminar</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const toggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            if (!toggle || !sidebar || !backdrop) {
                return;
            }

            const setOpen = (open) => {
                sidebar.classList.toggle('is-open', open);
                backdrop.classList.toggle('is-visible', open);
                document.body.classList.toggle('sidebar-open', open);
            };

            toggle.addEventListener('click', () => {
                setOpen(!sidebar.classList.contains('is-open'));
            });

            backdrop.addEventListener('click', () => setOpen(false));

            window.addEventListener('resize', () => {
                if (window.innerWidth > 900) {
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
                message.textContent = form.dataset.confirmMessage || 'Esta accion no se puede deshacer.';
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
    <script src="{{ asset('js/image-upload-optimizer.js') }}?v={{ filemtime(public_path('js/image-upload-optimizer.js')) }}"></script>
    <script src="{{ asset('js/product-image-preview.js') }}?v={{ filemtime(public_path('js/product-image-preview.js')) }}"></script>
    @stack('scripts')
</body>

</html>
