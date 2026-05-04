<?php
// includes/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Security + cache headers ──────────────────────────────────────
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
    "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
    "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; " .
    "img-src 'self' data:; " .
    "connect-src 'self';"
);

require_once __DIR__ . '/functions.php';

// ── Detect whether local Bootstrap files are present ─────────────
$localBootstrap      = file_exists(__DIR__ . '/../bootstrap/bootstrap.min.css');
$localBootstrapIcons = file_exists(__DIR__ . '/../bootstrap/bootstrap-icons.min.css');

$bootstrapCss  = $localBootstrap
    ? BASE_URL . '/bootstrap/bootstrap.min.css'
    : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';

$bootstrapIconsCss = $localBootstrapIcons
    ? BASE_URL . '/bootstrap/bootstrap-icons.min.css'
    : 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';

$use_sidebar = false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($page_title) ? e($page_title) . ' — ' : '' ?><?= APP_NAME ?></title>
  <meta name="description" content="Plateforme d'apprentissage peer-to-peer entre étudiants">
  <meta name="theme-color" content="#4f46e5">
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/images/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= $bootstrapCss ?>">
  <link rel="stylesheet" href="<?= $bootstrapIconsCss ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/main.css">
  <?php if (!empty($page_css)): ?>
    <link rel="stylesheet" href="<?= BASE_URL . e($page_css) ?>">
  <?php endif; ?>
</head>
<body>
