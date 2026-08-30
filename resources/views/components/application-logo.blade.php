{{--
    The school's own "1ο" badge (the same mark used in the site header and
    the PWA icon), replacing Laravel/Breeze's stock default logo. This is an
    <img>, not an inline SVG like the original — fill-current/text-* classes
    passed by callers no longer recolor it (the badge has fixed brand
    colors), but width/height sizing classes still apply normally.
--}}
<img src="/pwa-icons/icon-512.png" alt="1ο ΓΕΛ Ραφήνας" {{ $attributes }}>
