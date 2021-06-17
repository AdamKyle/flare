<?php

return [
    // ======= BATTLE COUNT =======
    //
    // We keep track of the characters battles, how many seconds between each,
    // in an array: [1,2,3]
    //
    // The question being asked here, is how big should that array be allowed to grow?
    //
    // ============================
    'battle_count' => env('BATTLE_COUNT', 3),

    // ======= BATTLE TIME =======
    //
    // Depending on how large you let battle_count to grow, this will be the average of the most common.
    //
    // For example, if ou set this to 15, then the average of the numbers in battle_count will have to
    // fall with in +-4 of the 15 for a time out to potentially trigger.
    //
    // ============================
    'battle_time'  => env('BATTLE_TIME', 1),

    // ======= POSSIBLY CHEATING COUNT =======
    //
    // The count before we check if we are cheating. This number is used to determine how many times
    // before we actually check the cheating value.
    //
    // This value should be the same as battle_count.
    //
    // ============================
    'possibly_cheating' => env('POSSIBLY_CHEATING_COUNT', 3),

    // ======= TIME OUT DELETE =======
    //
    // How long before we delete the cache for cheating?
    //
    // ===============================
    'time_out_delete' => env('TIME_OUT_DELETE', 30),

    // ======= BATTLE TIME _OUT =======
    //
    // How long before we force a timeout if they continue doing this action?
    //
    // ================================
    'battle_time_out' => env('BATTLETIME_OUT', 15),
];
