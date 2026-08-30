<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0e6e73">
{{-- /icons/ is deliberately avoided: it's a reserved Apache path on some
     shared hosts (the classic mod_autoindex directory-listing icons, e.g.
     /icons/folder.gif) and silently shadows anything the app puts there. --}}
<link rel="icon" type="image/png" sizes="32x32" href="/pwa-icons/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/pwa-icons/favicon-16.png">
<link rel="apple-touch-icon" href="/pwa-icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Ραντεβού ΓΕΛ">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }
</script>
