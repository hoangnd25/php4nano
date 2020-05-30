<?php

    require_once __DIR__ . '/../../src/Tools.php';
    
    use php4nano\Tools as NanoTools;

    $mnem =
    [
        'turkey',
        'fever',
        'wish',
        'tray',
        'remind',
        'abandon',
        'announce',
        'skin',
        'input',
        'permit',
        'mobile',
        'exclude',
        'ghost',
        'album',
        'floor',
        'utility',
        'attack',
        'oil',
        'payment',
        'stumble',
        'noise',
        'orbit',
        'grain',
        'dash'
    ];
    
    echo NanoTools::mnem2hex($mnem);
