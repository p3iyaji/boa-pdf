
<?php ($markClass = $class ?? 'h-11 w-11'); ?>
<svg class="<?php echo e($markClass); ?> shrink-0 drop-shadow-md" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 72 72" fill="none" aria-hidden="true">
    <defs>
        <linearGradient id="brand-sun" x1="10" y1="6" x2="32" y2="54" gradientUnits="userSpaceOnUse">
            <stop stop-color="#fef9c3"/>
            <stop offset="0.5" stop-color="#fbbf24"/>
            <stop offset="1" stop-color="#d97706"/>
        </linearGradient>
        <linearGradient id="brand-stele" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
            <stop stop-color="#115e59"/>
            <stop offset="0.5" stop-color="#0f766e"/>
            <stop offset="1" stop-color="#042f2e"/>
        </linearGradient>
        <linearGradient id="brand-linen" x1="44" y1="18" x2="64" y2="58" gradientUnits="userSpaceOnUse">
            <stop stop-color="#f0fdfa"/>
            <stop offset="1" stop-color="#5eead4"/>
        </linearGradient>
    </defs>
    <rect x="2" y="2" width="68" height="68" rx="17" fill="url(#brand-stele)"/>
    <rect x="2" y="2" width="68" height="68" rx="17" fill="none" stroke="#fbbf24" stroke-opacity="0.4" stroke-width="1.25"/>
    
    <circle cx="26" cy="38" r="13.5" fill="url(#brand-sun)"/>
    <g stroke="#fef08a" stroke-width="1.75" stroke-linecap="round" opacity="0.9">
        <line x1="26" y1="38" x2="26" y2="13"/>
        <line x1="26" y1="38" x2="42.5" y2="28"/>
        <line x1="26" y1="38" x2="42.5" y2="48"/>
        <line x1="26" y1="38" x2="26" y2="63"/>
        <line x1="26" y1="38" x2="9.5" y2="48"/>
        <line x1="26" y1="38" x2="9.5" y2="28"/>
        <line x1="26" y1="38" x2="41" y2="38"/>
        <line x1="26" y1="38" x2="11" y2="38"/>
    </g>
    <circle cx="26" cy="38" r="13.5" fill="url(#brand-sun)"/>
    
    <path fill="#fef3c7" d="M48 17c0-1.7 1.3-3 3-3s3 1.3 3 3-1.3 3-3 3a2.9 2.9 0 01-2.1-.9 2.9 2.9 0 01-2.1.9c-1.7 0-3-1.3-3-3z" opacity="0.95"/>
    <path fill="#fef3c7" d="M45 12l3-4 3 4-3 2-3-2zm6 0l3-4 3 4-3 2-3-2z" opacity="0.9"/>
    <circle cx="51" cy="13" r="2.5" fill="#fbbf24"/>
    
    <path fill="url(#brand-linen)" d="M43 27h16v3h-1.5a1 1 0 00-.9.7l-1.3 23.5c-.2 1.9-1.7 3.3-3.6 3.3h-0.4c-1.9 0-3.4-1.4-3.6-3.3l-1.3-23.5a1 1 0 00-.9-.7H43v-3z" opacity="0.96"/>
    <path stroke="#0d9488" stroke-width="1.2" stroke-linecap="round" opacity="0.45" d="M47 36c1.8 1.2 4.2 1.2 6 0M48 42h4"/>
    <path stroke="#fbbf24" stroke-width="1.4" stroke-linecap="round" opacity="0.75" d="M41 34c-1.5 5-1.5 10 0 15M61 34c1.5 5 1.5 10 0 15"/>
    <path fill="#ffffff" fill-opacity="0.07" d="M2 56c14-3 28-3 42 0s28 3 42 0v14H2z"/>
</svg>
<?php /**PATH /Users/pauliyaji/Herd/powerhouse/resources/views/partials/brand-mark.blade.php ENDPATH**/ ?>