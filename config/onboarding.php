<?php

return [
    'sectors' => [
        'freelance' => 'Freelance',
        'retail' => 'Commerce',
        'services' => 'Services',
        'startup' => 'Startup',
        'construction' => 'BTP',
        'consulting' => 'Conseil',
    ],

    'defaults_by_sector' => [
        'freelance' => ['clients', 'invoice', 'projects', 'notion-workspace', 'google-calendar'],
        'retail' => ['clients', 'invoice', 'stock', 'projects', 'notion-workspace'],
        'services' => ['clients', 'invoice', 'projects', 'notion-workspace', 'google-calendar'],
        'startup' => ['clients', 'invoice', 'projects', 'notion-workspace', 'google-calendar', 'google-drive'],
        'construction' => ['clients', 'invoice', 'stock', 'projects'],
        'consulting' => ['clients', 'invoice', 'projects', 'notion-workspace', 'google-docx'],
    ],
];
