<?php

return [
    // Serverbyt deploys public_html as a sibling of the app root, not app/public/,
    // so index.php calls $app->usePublicPath() — dompdf must use that same resolved
    // path (public_path()) instead of its own base_path('public') fallback, which
    // doesn't exist here and throws "Cannot resolve public path".
    'public_path' => public_path(),
];
