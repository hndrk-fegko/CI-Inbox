<?php

/**
 * Label-Modul Konfiguration
 * 
 * Standard-Labels (System) und Validierungs-Regeln
 */

return [
    /**
     * System-Labels
     * 
     * Diese Labels werden automatisch bei der Installation erstellt
     * und können nicht gelöscht werden.
     */
    'system_labels' => [
        [
            'name' => 'Inbox',
            'color' => '#1a73e8', // Google Blue
            'icon' => '📥',
            'display_order' => 1,
            'description' => 'New and unread messages'
        ],
        [
            'name' => 'Sent',
            'color' => '#34a853', // Google Green
            'icon' => '📤',
            'display_order' => 2,
            'description' => 'Sent messages'
        ],
        [
            'name' => 'Drafts',
            'color' => '#f9ab00', // Google Yellow
            'icon' => '📝',
            'display_order' => 3,
            'description' => 'Draft messages'
        ],
        [
            'name' => 'Trash',
            'color' => '#ea4335', // Google Red
            'icon' => '🗑️',
            'display_order' => 4,
            'description' => 'Deleted messages'
        ],
        [
            'name' => 'Spam',
            'color' => '#ea4335', // Google Red
            'icon' => '⚠️',
            'display_order' => 5,
            'description' => 'Spam and junk messages'
        ],
        [
            'name' => 'Starred',
            'color' => '#fbbc04', // Google Orange
            'icon' => '⭐',
            'display_order' => 6,
            'description' => 'Important and starred messages'
        ],
        [
            'name' => 'Archive',
            'color' => '#5f6368', // Gray
            'icon' => '📦',
            'display_order' => 7,
            'description' => 'Archived messages'
        ]
    ],
    
    /**
     * Standard-Farben für Custom Labels
     * 
     * Zufällige Auswahl wenn keine Farbe angegeben
     */
    'default_custom_colors' => [
        '#FF5733', // Red-Orange
        '#33C4FF', // Sky Blue
        '#33FF57', // Lime Green
        '#FF33C4', // Pink
        '#C4FF33', // Yellow-Green
        '#33FFC4', // Turquoise
        '#C433FF', // Purple
        '#FFC433', // Orange-Yellow
        '#FF3333', // Bright Red
        '#3333FF', // Bright Blue
        '#33FF33', // Bright Green
        '#FF33FF', // Magenta
    ],
    
    /**
     * Validierungs-Regeln
     */
    'validation' => [
        // Label-Name Länge
        'name_min_length' => 2,
        'name_max_length' => 50,
        
        // Hex-Farbe Pattern: #RRGGBB
        'color_pattern' => '/^#[0-9A-Fa-f]{6}$/',
        
        // Maximale Anzahl Custom Labels pro Benutzer
        'max_custom_labels' => 100,
        
        // Maximale Anzahl Labels pro Thread
        'max_labels_per_thread' => 20
    ],
    
    /**
     * Feature-Flags
     */
    'features' => [
        // Label-Hierarchie aktivieren (z.B. "Projekte/Alpha/Sprint-1")
        'enable_hierarchy' => false,
        
        // Label-Icons aktivieren
        'enable_icons' => true,
        
        // Label-Beschreibungen aktivieren
        'enable_descriptions' => false,
        
        // Automatisches Labeling (AI-basiert)
        'enable_auto_labeling' => false
    ],
    
    /**
     * UI-Einstellungen
     */
    'ui' => [
        // Label-Badge-Stil
        'badge_style' => 'rounded', // 'rounded', 'square', 'pill'
        
        // Label-Font-Size
        'font_size' => '12px',
        
        // Label-Padding
        'padding' => '4px 8px',
        
        // Maximale Label-Anzeige in Thread-Liste
        'max_visible_labels' => 3
    ]
];
