<?php
// Archivo: local/conocer_cert/db/access.php
// 2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
// Definición de capacidades y permisos para el plugin

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Gestión de candidatos
    'local/conocer_cert:managecandidates' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ],
    
    // Gestión de empresas
    'local/conocer_cert:managecompanies' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ],
    
    // Gestión de competencias
    'local/conocer_cert:managecompetencies' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ],
    
    // Ver reportes
    'local/conocer_cert:viewreports' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        ]
    ],
    
    // Evaluar candidatos (para evaluadores externos)
    'local/conocer_cert:evaluatecandidates' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => []
    ],
    
    // Ver evaluaciones (para evaluadores)
    'local/conocer_cert:viewevaluations' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => []
    ],
    
    // Calificar candidatos (para evaluadores)
    'local/conocer_cert:gradecandidates' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => []
    ],
    
    // Gestión de evaluadores
    'local/conocer_cert:manageevaluators' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ],
    
    // Acceder a documentos sensibles
    'local/conocer_cert:accesssensitivedocuments' => [
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ],
    
    // Emitir certificados
    'local/conocer_cert:issuecertificates' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ],
    
    // Configurar el sistema
    'local/conocer_cert:configurecertification' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ],
    
    // Acceder al dashboard del candidato
    'local/conocer_cert:accesscandidatedashboard' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW
        ]
    ],
    
    // Acceder al dashboard de empresa
    'local/conocer_cert:accesscompanydashboard' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW
        ]
    ],
    
    // Acceder al dashboard de evaluador
    'local/conocer_cert:accessevaluatordashboard' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => []
    ],
    
    // Acceder al dashboard de administrador
    'local/conocer_cert:accessadmindashboard' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ]
];
