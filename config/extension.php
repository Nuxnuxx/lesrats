<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Latest browser extension version
    |--------------------------------------------------------------------------
    |
    | Must match `browser-extension/manifest.json`. Bumped at release time.
    | The profile page compares this to the version reported by the installed
    | extension (via the postMessage bridge); a mismatch forces the user to
    | download the new zip before they can reconnect.
    */
    'latest_version' => '2.2.0',

    /*
    | Public path of the packaged extension zip. Produced by `npm run pack:extension`
    | which zips `browser-extension/` into `public/extension/lesrats-extension-latest.zip`.
    */
    'download_path' => '/extension/lesrats-extension-latest.zip',
];
