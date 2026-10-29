<?php
/**
 * Global Header Component — AdminLTE 4.10.1 Design
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$pageTitle = $pageTitle ?? 'Visitor Log Management System';
$currentUser = getCurrentUser();
$currentRole = getUserRole();
$activeNav = $activeNav ?? 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= htmlspecialchars($pageTitle) ?> — AdminLTE 4 | VLMS</title>
    
    <!--begin::Theme Init (AdminLTE 4 standard)-->
    <script>
      (() => {
        'use strict';
        const root = document.documentElement;
        const STORAGE_KEY = 'lte-theme';
        let stored = null;
        try {
          stored = localStorage.getItem(STORAGE_KEY);
        } catch {}
        const authored = root.getAttribute('data-bs-theme');
        let resolved = 'light';
        if (stored === 'dark' || stored === 'light') {
          resolved = stored;
        } else if (authored === 'dark' || authored === 'light') {
          resolved = authored;
        } else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
          resolved = 'dark';
        }
        root.setAttribute('data-bs-theme', resolved);
        root.style.colorScheme = resolved;
      })();
    </script>
    <!--end::Theme Init-->

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light dark" />
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">

    <!--begin::Fonts (Source Sans 3)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.3.0/index.css"
      crossorigin="anonymous"
    />
    <!--end::Fonts-->

    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.16.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->

    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->

    <!--begin::Required Plugin(AdminLTE 4.10.1)-->
    <link rel="stylesheet" href="assets/adminlte/css/adminlte.min.css" />
    <!--end::Required Plugin(AdminLTE)-->

    <!-- Custom VLMS Enhancements & Print Stylesheet -->
    <link rel="stylesheet" href="assets/css/print.css" media="print">
    
    <!-- SweetAlert2 Stylesheet for Dynamic Alerts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
      .badge-code {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-weight: 600;
        letter-spacing: 0.05em;
      }
      .brand-image {
        max-height: 33px;
        width: auto;
      }
      .live-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #198754;
        display: inline-block;
        box-shadow: 0 0 6px #198754;
        animation: pulse 1.8s infinite;
      }
      @keyframes pulse {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.15); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.8; }
      }
      .cursor-pointer {
        cursor: pointer;
      }
      .fade-in-row {
        animation: fadeInRow 0.35s ease-in-out;
      }
      @keyframes fadeInRow {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
      }
      .kpi-update-highlight {
        animation: kpiPulse 0.8s ease;
      }
      @keyframes kpiPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); color: #0d6efd; }
        100% { transform: scale(1); }
      }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
