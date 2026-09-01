<?php

return [
    /*
    | The one-time setup page that creates the first admin. It is disabled the
    | moment an admin exists, but this flag lets the installer close the door
    | explicitly as well -- see docs/05-DEPLOYMENT.md.
    */
    'setup_enabled' => env('SETUP_ENABLED', true),
];
