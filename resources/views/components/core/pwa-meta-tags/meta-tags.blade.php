@props(['title' => config('app.name', 'PlanesOfTlessa')])

<title>{{ $title }}</title>

<!-- PWA Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- Favicon and App Icons -->
<link rel="icon" type="image/png" sizes="192x192" href="/pwa-images/tlessa-icons/tlessa-icon-192.png">
<link rel="apple-touch-icon" sizes="180x180" href="/pwa-images/tlessa-icons/tlessa-icon-180.png">

<!-- Theme color -->
<meta name="theme-color" content="#000000">

<!-- Apple-specific meta tags -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="{{ $title }}">

<!-- Apple Startup Images -->
@php
    $splashScreens = [
        '640x1136' => 'pwa-images/tlessa-splash-images/tlessa-splash-640x1136.png',
        '750x1334' => 'pwa-images/tlessa-splash-images/tlessa-splash-750x1334.png',
        '828x1792' => 'pwa-images/tlessa-splash-images/tlessa-splash-828x1792.png',
        '1125x2436' => 'pwa-images/tlessa-splash-images/tlessa-splash-1125x2436.png',
        '1242x2208' => 'pwa-images/tlessa-splash-images/tlessa-splash-1242x2208.png',
        '1242x2688' => 'pwa-images/tlessa-splash-images/tlessa-splash-1242x2688.png',
        '1536x2048' => 'pwa-images/tlessa-splash-images/tlessa-splash-1536x2048.png',
        '1668x2224' => 'pwa-images/tlessa-splash-images/tlessa-splash-1668x2224.png',
        '1668x2388' => 'pwa-images/tlessa-splash-images/tlessa-splash-1668x2388.png',
        '2048x2732' => 'pwa-images/tlessa-splash-images/tlessa-splash-2048x2732.png',
    ];
@endphp

@foreach($splashScreens as $size => $file)
    <link rel="apple-touch-startup-image" href="/{{ $file }}" media="(device-width: {{ explode('x', $size)[0] }}px) and (device-height: {{ explode('x', $size)[1] }}px)">
@endforeach

<!-- General Web App Tags -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="{{ $title }}">

<!-- Additional meta tags for SEO and social media -->
<meta name="description" content="{{ $title }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="Experience the web in a new way with our Progressive Web App.">
<meta property="og:image" content="/pwa-images/tlessa-icons/tlessa-icon-512.png">
<meta property="og:url" content="{{ url()->current() }}">

<!-- Windows -->
<meta name="msapplication-TileColor" content="#000000">
<meta name="msapplication-TileImage" content="/pwa-images/tlessa-icons/tlessa-icon-144.png">
<meta name="msapplication-config" content="/browserconfig.xml">
