<?php
return [
    'app' => [
        [
            'name' => 'chenxi',
            'number' => 5,
            'command' => '/web/app/php/bin/php',
            'arguments' => ['/web/www/default/chenxi/artisan', 'queue:work', 'redis', '--queue=default'],
        ]
    ]
];