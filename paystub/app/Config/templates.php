<?php
// Declarative template catalog (text only, no binary assets)
return [
    // 1) Horizontal Blue
    'horizontal_blue' => [
        'key' => 'horizontal_blue',
        'name' => 'Horizontal Blue',
        'description' => 'Encabezado ancho; Earnings izquierda, Deductions/Taxes derecha; Summary al final.',
        'thumbnail_svg' => '<svg viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg"><rect width="160" height="110" rx="10" fill="#0f172a"/><rect x="10" y="10" width="140" height="20" rx="4" fill="#1d4ed8"/><rect x="10" y="36" width="80" height="30" rx="3" fill="#1e3a8a"/><rect x="92" y="36" width="58" height="12" rx="2" fill="#1e3a8a"/><rect x="92" y="50" width="58" height="16" rx="2" fill="#1e3a8a"/><rect x="10" y="72" width="140" height="20" rx="3" fill="#1e3a8a"/></svg>',
        'pdf_view' => 'pdf-layout-horizontal-blue.php',
        'tokens' => [
            'primary'=>'#0F172A','accent'=>'#1D4ED8','muted'=>'#64748B','bg'=>'#FFFFFF','line'=>'#E5E7EB',
            'font'=>['h1'=>'18pt','h2'=>'11pt','text'=>'9pt'], 'radius'=>'6pt','pad'=>'6pt'
        ],
        'layout' => [
            'orientation'=>'horizontal',
            'header'=>['employer','employee','period','folio'],
            'grid'=>['earnings'=>'left','deductions'=>'right','taxes'=>'right','summary'=>'full','distribution'=>'full'],
            'tables'=>[
                'earnings'=>['label','hours','rate','current','ytd'],
                'deductions'=>['label','current','ytd'],
                'taxes'=>['label','current','ytd']
            ]
        ]
    ],

    // 2) Horizontal Black
    'horizontal_black' => [
        'key' => 'horizontal_black',
        'name' => 'Horizontal Black',
        'description' => 'Alto contraste; encabezados oscuros; líneas marcadas.',
        'thumbnail_svg' => '<svg viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg"><rect width="160" height="110" rx="10" fill="#111827"/><rect x="10" y="10" width="140" height="20" rx="4" fill="#374151"/><rect x="10" y="36" width="80" height="30" rx="3" fill="#1f2937"/><rect x="92" y="36" width="58" height="12" rx="2" fill="#1f2937"/><rect x="92" y="50" width="58" height="16" rx="2" fill="#1f2937"/><rect x="10" y="72" width="140" height="20" rx="3" fill="#374151"/></svg>',
        'pdf_view' => 'pdf-layout-horizontal-black.php',
        'tokens' => [
            'primary'=>'#111827','accent'=>'#374151','muted'=>'#9CA3AF','bg'=>'#FFFFFF','line'=>'#111827',
            'font'=>['h1'=>'18pt','h2'=>'11pt','text'=>'9pt'], 'radius'=>'6pt','pad'=>'6pt'
        ],
        'layout' => [
            'orientation'=>'horizontal',
            'header'=>['employer','employee','period','folio'],
            'grid'=>['earnings'=>'left','deductions'=>'right','taxes'=>'right','summary'=>'full','distribution'=>'full'],
            'tables'=>[
                'earnings'=>['label','hours','rate','current','ytd'],
                'deductions'=>['label','current','ytd'],
                'taxes'=>['label','current','ytd']
            ]
        ]
    ],

    // 3) Vertical Blue
    'vertical_blue' => [
        'key' => 'vertical_blue',
        'name' => 'Vertical Blue',
        'description' => 'Secciones apiladas: Earnings → Deductions → Taxes → Summary.',
        'thumbnail_svg' => '<svg viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg"><rect width="160" height="110" rx="10" fill="#0f172a"/><rect x="10" y="10" width="140" height="18" rx="4" fill="#1d4ed8"/><rect x="10" y="32" width="140" height="14" rx="2" fill="#1e3a8a"/><rect x="10" y="48" width="140" height="14" rx="2" fill="#1e3a8a"/><rect x="10" y="64" width="140" height="14" rx="2" fill="#1e3a8a"/><rect x="10" y="80" width="140" height="14" rx="2" fill="#1e3a8a"/></svg>',
        'pdf_view' => 'pdf-layout-vertical-blue.php',
        'tokens' => [
            'primary'=>'#0F172A','accent'=>'#1D4ED8','muted'=>'#64748B','bg'=>'#FFFFFF','line'=>'#E5E7EB',
            'font'=>['h1'=>'18pt','h2'=>'11pt','text'=>'9pt'], 'radius'=>'6pt','pad'=>'5pt'
        ],
        'layout' => [
            'orientation'=>'vertical',
            'header'=>['employer','employee','period','folio'],
            'grid'=>['earnings'=>'full','deductions'=>'full','taxes'=>'full','summary'=>'full','distribution'=>'full'],
            'tables'=>[
                'earnings'=>['label','hours','rate','current','ytd'],
                'deductions'=>['label','current','ytd'],
                'taxes'=>['label','current','ytd']
            ]
        ]
    ],

    // 4) Vertical Black
    'vertical_black' => [
        'key' => 'vertical_black',
        'name' => 'Vertical Black',
        'description' => 'Versión monocroma ideal para impresión B/N.',
        'thumbnail_svg' => '<svg viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg"><rect width="160" height="110" rx="10" fill="#111827"/><rect x="10" y="10" width="140" height="18" rx="4" fill="#374151"/><rect x="10" y="32" width="140" height="14" rx="2" fill="#1f2937"/><rect x="10" y="48" width="140" height="14" rx="2" fill="#1f2937"/><rect x="10" y="64" width="140" height="14" rx="2" fill="#1f2937"/><rect x="10" y="80" width="140" height="14" rx="2" fill="#374151"/></svg>',
        'pdf_view' => 'pdf-layout-vertical-black.php',
        'tokens' => [
            'primary'=>'#111827','accent'=>'#374151','muted'=>'#9CA3AF','bg'=>'#FFFFFF','line'=>'#111827',
            'font'=>['h1'=>'18pt','h2'=>'11pt','text'=>'9pt'], 'radius'=>'6pt','pad'=>'5pt'
        ],
        'layout' => [
            'orientation'=>'vertical',
            'header'=>['employer','employee','period','folio'],
            'grid'=>['earnings'=>'full','deductions'=>'full','taxes'=>'full','summary'=>'full','distribution'=>'full'],
            'tables'=>[
                'earnings'=>['label','hours','rate','current','ytd'],
                'deductions'=>['label','current','ytd'],
                'taxes'=>['label','current','ytd']
            ]
        ]
    ],

    // 5) Compact Blue
    'compact_blue' => [
        'key' => 'compact_blue',
        'name' => 'Compact Blue',
        'description' => 'Alta densidad: tipografías 8–9pt, márgenes mínimos.',
        'thumbnail_svg' => '<svg viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg"><rect width="160" height="110" rx="10" fill="#0f172a"/><rect x="10" y="10" width="140" height="16" rx="3" fill="#1d4ed8"/><rect x="10" y="30" width="140" height="12" rx="2" fill="#1e3a8a"/><rect x="10" y="44" width="140" height="12" rx="2" fill="#1e3a8a"/><rect x="10" y="58" width="140" height="12" rx="2" fill="#1e3a8a"/><rect x="10" y="72" width="140" height="12" rx="2" fill="#1e3a8a"/></svg>',
        'pdf_view' => 'pdf-layout-compact-blue.php',
        'tokens' => [
            'primary'=>'#0F172A','accent'=>'#1D4ED8','muted'=>'#64748B','bg'=>'#FFFFFF','line'=>'#CBD5E1',
            'font'=>['h1'=>'16pt','h2'=>'10pt','text'=>'8pt'], 'radius'=>'4pt','pad'=>'3pt'
        ],
        'layout' => [
            'orientation'=>'vertical',
            'header'=>['employer','employee','period','folio'],
            'grid'=>['earnings'=>'full','deductions'=>'full','taxes'=>'full','summary'=>'full','distribution'=>'full'],
            'tables'=>[
                'earnings'=>['label','hours','rate','current','ytd'],
                'deductions'=>['label','current','ytd'],
                'taxes'=>['label','current','ytd']
            ]
        ]
    ],

    // 6) Minimal Gray
    'minimal_gray' => [
        'key' => 'minimal_gray',
        'name' => 'Minimal Gray',
        'description' => 'Bordes finos, sin fondos; títulos en mayúsculas pequeñas.',
        'thumbnail_svg' => '<svg viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg"><rect width="160" height="110" rx="10" fill="#f3f4f6"/><rect x="10" y="10" width="140" height="14" rx="2" fill="#9ca3af"/><rect x="10" y="30" width="140" height="12" rx="2" fill="#c7cbd1"/><rect x="10" y="46" width="140" height="12" rx="2" fill="#c7cbd1"/><rect x="10" y="62" width="140" height="12" rx="2" fill="#c7cbd1"/><rect x="10" y="78" width="140" height="12" rx="2" fill="#c7cbd1"/></svg>',
        'pdf_view' => 'pdf-layout-minimal-gray.php',
        'tokens' => [
            'primary'=>'#111827','accent'=>'#6B7280','muted'=>'#6B7280','bg'=>'#FFFFFF','line'=>'#E5E7EB',
            'font'=>['h1'=>'16pt','h2'=>'10pt','text'=>'9pt'], 'radius'=>'2pt','pad'=>'5pt'
        ],
        'layout' => [
            'orientation'=>'vertical',
            'header'=>['employer','employee','period','folio'],
            'grid'=>['earnings'=>'full','deductions'=>'full','taxes'=>'full','summary'=>'full','distribution'=>'full'],
            'tables'=>[
                'earnings'=>['label','hours','rate','current','ytd'],
                'deductions'=>['label','current','ytd'],
                'taxes'=>['label','current','ytd']
            ]
        ]
    ],

    // 7) Statement Wide
    'statement_wide' => [
        'key' => 'statement_wide',
        'name' => 'Statement Wide',
        'description' => 'Bandas superior/inferior con totales; Earnings ancho completo; Deductions+Taxes en dos subcolumnas.',
        'thumbnail_svg' => '<svg viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg"><rect width="160" height="110" rx="10" fill="#0f172a"/><rect x="10" y="10" width="140" height="14" rx="2" fill="#22c55e"/><rect x="10" y="28" width="140" height="20" rx="3" fill="#1e3a8a"/><rect x="10" y="52" width="66" height="24" rx="3" fill="#1e3a8a"/><rect x="84" y="52" width="66" height="24" rx="3" fill="#1e3a8a"/><rect x="10" y="82" width="140" height="14" rx="2" fill="#22c55e"/></svg>',
        'pdf_view' => 'pdf-layout-statement-wide.php',
        'tokens' => [
            'primary'=>'#0F172A','accent'=>'#22C55E','muted'=>'#64748B','bg'=>'#FFFFFF','line'=>'#E5E7EB',
            'font'=>['h1'=>'18pt','h2'=>'11pt','text'=>'9pt'], 'radius'=>'6pt','pad'=>'6pt'
        ],
        'layout' => [
            'orientation'=>'wide',
            'header'=>['employer','employee','period','folio'],
            'grid'=>['earnings'=>'full','deductions'=>'left','taxes'=>'right','summary'=>'bands','distribution'=>'full'],
            'tables'=>[
                'earnings'=>['label','hours','rate','current','ytd'],
                'deductions'=>['label','current','ytd'],
                'taxes'=>['label','current','ytd']
            ]
        ]
    ],

    // 8) Modern Split
    'modern_split' => [
        'key' => 'modern_split',
        'name' => 'Modern Split',
        'description' => 'Cabecera 50/50; Earnings y Deductions en columnas; Taxes y Summary abajo.',
        'thumbnail_svg' => '<svg viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg"><rect width="160" height="110" rx="10" fill="#0f172a"/><rect x="10" y="10" width="66" height="18" rx="3" fill="#6366f1"/><rect x="84" y="10" width="66" height="18" rx="3" fill="#6366f1"/><rect x="10" y="32" width="66" height="24" rx="3" fill="#1e3a8a"/><rect x="84" y="32" width="66" height="24" rx="3" fill="#1e3a8a"/><rect x="10" y="60" width="140" height="14" rx="2" fill="#1e3a8a"/></svg>',
        'pdf_view' => 'pdf-layout-modern-split.php',
        'tokens' => [
            'primary'=>'#0F172A','accent'=>'#6366F1','muted'=>'#64748B','bg'=>'#FFFFFF','line'=>'#E5E7EB',
            'font'=>['h1'=>'18pt','h2'=>'11pt','text'=>'9pt'], 'radius'=>'6pt','pad'=>'6pt'
        ],
        'layout' => [
            'orientation'=>'split',
            'header'=>['employer','employee','period','folio'],
            'grid'=>['earnings'=>'left','deductions'=>'right','taxes'=>'full','summary'=>'full','distribution'=>'full'],
            'tables'=>[
                'earnings'=>['label','hours','rate','current','ytd'],
                'deductions'=>['label','current','ytd'],
                'taxes'=>['label','current','ytd']
            ]
        ]
    ],

    // Alias legacy (no mostrar en UI)
    'classic_black' => [
        'key' => 'classic_black', 'name' => 'Classic Black (legacy)', 'description' => 'Alias de Horizontal Black', 'hidden' => true,
        'thumbnail_svg' => '<svg viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg"><rect width="160" height="110" rx="10" fill="#111827"/></svg>',
        'pdf_view' => 'pdf-layout-horizontal-black.php',
        'tokens' => [ 'primary'=>'#111827','accent'=>'#374151','muted'=>'#9CA3AF','bg'=>'#FFFFFF','line'=>'#111827','font'=>['h1'=>'18pt','h2'=>'11pt','text'=>'9pt'], 'radius'=>'6pt','pad'=>'6pt' ],
        'layout' => [ 'orientation'=>'horizontal', 'header'=>['employer','employee','period','folio'], 'grid'=>['earnings'=>'left','deductions'=>'right','taxes'=>'right','summary'=>'full','distribution'=>'full'], 'tables'=>['earnings'=>['label','hours','rate','current','ytd'],'deductions'=>['label','current','ytd'],'taxes'=>['label','current','ytd']] ]
    ],
];
