<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Visit Retention
    |--------------------------------------------------------------------------
    |
    | Number of days raw visitor records are kept before the `visits:prune`
    | scheduled command deletes them. Set to 0 to disable pruning.
    |
    */

    'retention_days' => env('VISITS_RETENTION_DAYS', 180),
];
