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
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/app.css') }}">
</head>
<body>
<div id="eazyLaunchSplash" role="status" aria-label="Loading EazyPOS"><img src="/icons/eazypos-mark.png" alt="EazyPOS"></div>
<div class="app">
<aside class="sidebar" id="desktopSidebar"></aside>
<main class="main">
<header class="topbar"><div class="top-left"><button class="hamb" onclick="toggleDrawer()">☰</button><div class="search-wrap" style="width:min(520px,100%)"><input class="top-search" id="globalSearch" placeholder="Search products, sales, customers..." oninput="renderGlobalSuggestions()" onkeydown="if(event.key==='Enter') quickSearch()"><div id="globalSearchSuggest" class="search-suggest" style="display:none"></div></div></div><div class="top-actions"><button class="round" onclick="openRegisterReport()">▣</button><button class="round" onclick="toast('No new notifications')">♢</button><div class="user-chip"><span class="avatar" id="avatar">E</span><span id="userNameTop">User</span></div></div></header>
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
