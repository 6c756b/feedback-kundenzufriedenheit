<?php
// Kopiere diese Datei nach config.php und passe die Werte an.
// config.php ist in .gitignore und wird nicht eingecheckt.
return [
    'env' => 'production',  // 'production' oder 'local'
    'db' => [
        'driver'   => 'mysql',      // 'mysql' oder 'sqlite'
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'feedback',
        'user'     => 'db_user',
        'password' => 'db_password',
        // Nur für SQLite (driver=sqlite):
        // 'path' => __DIR__ . '/database/feedback.sqlite',
    ],
    'ldap' => [
        'host'    => 'ldap.example.com',
        'port'    => 389,
        'base_dn' => 'dc=example,dc=com',
    ],
    'app' => [
        'version'          => '0.1a',               // App-Version (siehe CHANGELOG.md)
        'url'              => 'https://feedback.example.com',
        'session_lifetime' => 480,                  // Minuten
        'name'             => 'Feedback',           // Kurzname: Browser-Titel, Backend-Nav
        'company_name'     => 'Muster GmbH',        // Footer-Copyright, PDF, E-Mail
        'company_slogan'   => '',                   // Slogan-Badge neben Logo im Frontend (leer = kein Badge)
        'survey_label'     => 'Kundenbefragung',    // Bezeichnung der Umfrage in UI, PDF und E-Mail
        'impressum_url'    => 'https://www.example.com/impressum',                   // Footer-Link Impressum (leer = kein Link)
        'datenschutz_url'  => 'https://www.example.com/datenschutz',                 // Footer-Link Datenschutz (leer = kein Link)
    ],
    'branding' => [
        'logo_main'    => '/assets/img/logo.svg',       // Header-Logo im Frontend
        'logo_height'  => '56px',                       // Höhe des Logos im Umfrage-Header (CSS-Wert, z.B. '26px', '2rem')
        'logo_favicon' => '/assets/img/favicon.png',    // Browser-Favicon
        'logo_nav'     => '/assets/img/logo-nav.png',   // Logo in der Backend-Navigation
        'logo_alt'     => 'Muster GmbH',                // Alt-Text für alle Logos
        'hero_image'   => '/assets/img/hero.jpg',       // Hintergrundbild der Landingpage
        'hero_alt'     => '',                            // aria-label für Hero-Bild (optional)
    ],
    'landing' => [
        'hero_headline' => "Ihr Feedback.\nUnser Anspruch.",
        'card_heading'  => 'Gemeinsam weiterentwickeln',
        'card_body'     => 'Beschreibungstext auf der Landingpage.',
        'checklist'     => [
            'Volle Transparenz',
            'Dauer ca. 5 Minuten',
            'Gemeinsame Weiterentwicklung',
        ],
    ],
    'templates' => [
        'email'     => 'email.html',       // relativ zu resources/templates/
        'signature' => 'signature.html',   // relativ zu resources/templates/
    ],
    'smtp' => [
        'host'          => 'smtp.example.com',      // z.B. 'smtp.office365.com'
        'port'          => 587,                     // 587 = STARTTLS, 465 = SMTPS
        'user'          => '',                      // SMTP-Benutzername
        'password'      => '',                      // SMTP-Passwort
        'from_fallback' => 'noreply@example.com',   // Absender wenn User keine E-Mail hat
        'from_name'     => 'Muster GmbH',           // Anzeigename des Absenders
    ],
    'survey' => [
        'welcome_text'  => 'Sehr geehrte Damen und Herren,\n\nwir freuen uns, dass Sie sich die Zeit nehmen, unsere Zusammenarbeit zu bewerten. Ihre Meinung ist uns sehr wichtig.',
        'thankyou_text' => 'Vielen Dank für Ihre Teilnahme. Ihr Feedback hilft uns, unsere Leistungen kontinuierlich zu verbessern.',
    ],
    // Nur aktiv wenn env=local - LDAP wird umgangen
    // 'dev_auth' => [
    //     'users' => [
    //         ['email' => 'admin@local.dev',  'password' => 'admin',  'name' => 'Dev Admin',  'role' => 'superadmin'],
    //         ['email' => 'staff@local.dev',  'password' => 'staff',  'name' => 'Dev Staff',  'role' => 'staff'],
    //         ['email' => 'reader@local.dev', 'password' => 'reader', 'name' => 'Dev Reader', 'role' => 'reader'],
    //     ],
    // ],
];
