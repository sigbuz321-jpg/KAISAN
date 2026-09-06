<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Design tokens that live outside CSS
    |--------------------------------------------------------------------------
    |
    | resources/css/app.css is the source of truth for the design system, but
    | Filament needs the primary colour as a hex string in PHP. This file is
    | that bridge, so the panel and the student pages cannot drift apart.
    |
    | #D97706 is the hex equivalent of --color-accent, oklch(0.65 0.15 65).
    | If one changes, change the other in the same commit.
    |
    */

    'colors' => [
        'primary' => env('DESIGN_PRIMARY_COLOR', '#D97706'),
    ],

];
