<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/eazypos-192.png">
<link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="EazyPOS">
<meta name="theme-color" content="#d3ff12">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<title>EazyPOS — POS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/app.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/polish.css') }}">
</head>
<body>
<div id="eazyLaunchSplash" role="status" aria-label="Loading EazyPOS"><img src="/icons/eazypos-mark.png" alt="EazyPOS"></div>
<div class="app">
<aside class="sidebar" id="desktopSidebar"></aside>
<main class="main">
<header class="topbar"><div class="top-left"><button class="hamb" onclick="toggleDrawer()" aria-label="Open menu"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg></button><div class="search-wrap" style="width:min(520px,100%)"><input class="top-search" id="globalSearch" placeholder="Search products, sales, customers..." oninput="renderGlobalSuggestions()" onkeydown="if(event.key==='Enter') quickSearch()"><div id="globalSearchSuggest" class="search-suggest" style="display:none"></div></div></div><div class="top-actions"><button class="round" onclick="openRegisterReport()" title="Register details" aria-label="Register details"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M7 20h10"/><path d="M12 16v4"/><path d="M7 8h4"/></svg></button><button class="round" onclick="toast('No new notifications')" title="Notifications" aria-label="Notifications"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></button><div class="user-chip"><span class="avatar" id="avatar">E</span><span id="userNameTop">User</span></div></div></header>
<div id="page"></div>
</main>
</div>
<div class="mobile-bottom" id="mobileBottom"></div>
<div class="mobile-drawer" id="drawer" onclick="if(event.target.id==='drawer')toggleDrawer()"><div class="drawer" id="drawerInner"></div></div>
<div class="modal hidden" id="modal"><div class="modalbox" id="modalBox"></div></div>
<div class="toast" id="toast"></div>
@foreach (\App\Support\Asset::POS_SCRIPTS as $script)
<script src="{{ \App\Support\Asset::url($script) }}"></script>
@endforeach
</body>
</html>
