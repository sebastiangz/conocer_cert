<?php
// Archivo: local/conocer_cert/classes/util/security.php
// 2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
// Implementación de seguridad para el plugin

namespace local_conocer_cert\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Clase para gestionar aspectos de seguridad del plugin CONOCER
 */
class security {
    /**
     * Verifica si el usuario actual tiene los permisos necesarios para una acción
     *
     * @param string $capability Capacidad a verificar
     * @param int $contextid ID del contexto (opcional)
     * @param int $userid ID del usuario (opcional, por defecto el usuario actual)
     * @return bool Verdadero si tiene permiso, falso en caso contrario
     */
    public static function verify_capability($capability, $contextid = null, $userid = null) {
        global $USER;
        
        if ($userid === null) {
            $userid = $USER->id;
        }
        
        if ($contextid === null) {
            $context = \context_system::instance();
        } else {
            $context = \context::instance_by_id($contextid);
        }
        
        return has_capability($capability, $context, $userid);
    }
    
    /**
     * Verifica si el usuario actual puede acceder a los datos de un candidato
     *
     * @param int $candidatoid ID del candidato
     * @param int $userid ID del usuario (opcional, por defecto el usuario actual)
     * @return bool Verdadero si tiene acceso, falso en caso contrario
     */
    public static function can_access_candidate($candidatoid, $userid = null) {
        global $DB, $USER;
        
        if ($userid === null) {
            $userid = $USER->id;
        }
        
        // Verificar si es administrador
        if (self::verify_capability('local/conocer_cert:managecandidates')) {
            return true;
        }
        
        // Verificar si es evaluador asignado
        if (self::verify_capability('local/conocer_cert:evaluatecandidates')) {
            $isEvaluator = $DB->record_exists('local_conocer_procesos', [
                'candidato_id' => $candidatoid,
                'evaluador_id' => $userid
            ]);
            
            if ($isEvaluator) {
                return true;
            }
        }
        
        // Verificar si es el propio candidato
        $candidato = $DB->get_record('local_conocer_candidatos', ['id' => $candidatoid], 'userid');
        if ($candidato && $candidato->userid == $userid) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica si el usuario actual puede acceder a los datos de una empresa
     *
     * @param int $empresaid ID de la empresa
     * @param int $userid ID del usuario (opcional, por defecto el usuario actual)
     * @return bool Verdadero si tiene acceso, falso en caso contrario
     */
    public static function can_access_company($empresaid, $userid = null) {
        global $DB, $USER;
        
        if ($userid === null) {
            $userid = $USER->id;
        }
        
        // Verificar si es administrador
        if (self::verify_capability('local/conocer_cert:managecompanies')) {
            return true;
        }
        
        // Verificar si es contacto de la empresa
        $empresa = $DB->get_record('local_conocer_empresas', ['id' => $empresaid], 'contacto_userid');
        if ($empresa && $empresa->contacto_userid == $userid) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Genera un token de acceso seguro para documentos sensibles
     *
     * @param int $documentid ID del documento
     * @param int $userid ID del usuario
     * @param int $expiry Tiempo de expiración en segundos (por defecto 1 hora)
     * @return string Token de acceso
     */
    public static function generate_document_token($documentid, $userid, $expiry = 3600) {
        global $CFG;
        
        $timestamp = time() + $expiry;
        $data = $documentid . '|' . $userid . '|' . $timestamp;
        $hash = hash_hmac('sha256', $data, $CFG->passwordsaltmain);
        
        return base64_encode($data . '|' . $hash);
    }
    
    /**
     * Verifica un token de acceso a documentos
     *
     * @param string $token Token a verificar
     * @return array|false Arreglo con los datos del documento o falso si no es válido
     */
    public static function verify_document_token($token) {
        global $CFG;
        
        $decoded = base64_decode($token);
        if ($decoded === false) {
            return false;
        }
        
        $parts = explode('|', $decoded);
        if (count($parts) !== 4) {
            return false;
        }
        
        list($documentid, $userid, $timestamp, $hash) = $parts;
        
        // Verificar expiración
        if (time() > $timestamp) {
            return false;
        }
        
        // Verificar hash
        $data = $documentid . '|' . $userid . '|' . $timestamp;
        $expected_hash = hash_hmac('sha256', $data, $CFG->passwordsaltmain);
        
        if (!hash_equals($expected_hash, $hash)) {
            return false;
        }
        
        return [
            'documentid' => $documentid,
            'userid' => $userid,
            'expires' => $timestamp
        ];
    }
    
    /**
     * Registra un intento de acceso no autorizado
     *
     * @param string $action Acción intentada
     * @param array $details Detalles adicionales
     */
    public static function log_unauthorized_attempt($action, $details = []) {
        global $DB, $USER;
        
        $record = new \stdClass();
        $record->userid = $USER->id;
        $record->action = $action;
        $record->ip = getremoteaddr();
        $record->details = json_encode($details);
        $record->timecreated = time();
        
        $DB->insert_record('local_conocer_security_log', $record);
        
        // Si hay demasiados intentos, bloquear temporalmente
        $recentAttempts = $DB->count_records_select(
            'local_conocer_security_log',
            "userid = :userid AND timecreated > :time",
            ['userid' => $USER->id, 'time' => time() - 3600]
        );
        
        if ($recentAttempts > 5) {
            // Bloquear por 30 minutos
            $cache = \cache::make('local_conocer_cert', 'security');
            $cache->set('blocked_' . $USER->id, time() + 1800);
        }
    }
}

/**
 * Clase para validación de documentos sensibles
 */
class file_validator {
    /** @var array Tipos MIME permitidos para documentos de identidad */
    const ALLOWED_ID_MIMES = ['application/pdf', 'image/jpeg', 'image/png'];
    
    /** @var array Tipos MIME permitidos para documentos oficiales */
    const ALLOWED_OFFICIAL_MIMES = ['application/pdf'];
    
    /** @var int Tamaño máximo para archivos (10MB) */
    const MAX_FILE_SIZE = 10485760;
    
    /** @var array Extensiones de virus conocidas para bloquear */
    const BLOCKED_EXTENSIONS = ['exe', 'bat', 'com', 'cmd', 'scr', 'pif', 'js'];
    
    /**
     * Valida un archivo subido
     *
     * @param stored_file $file Archivo a validar
     * @param array $allowed_mimes Tipos MIME permitidos
     * @param int $max_size Tamaño máximo permitido
     * @return array Resultado de la validación ['valid' => bool, 'message' => string]
     */
    public static function validate_file($file, $allowed_mimes = null, $max_size = null) {
        if ($allowed_mimes === null) {
            $allowed_mimes = self::ALLOWED_ID_MIMES;
        }
        
        if ($max_size === null) {
            $max_size = self::MAX_FILE_SIZE;
        }
        
        // Validar tamaño
        if ($file->get_filesize() > $max_size) {
            return [
                'valid' => false,
                'message' => get_string('error:filetoobig', 'local_conocer_cert', format_size($max_size))
            ];
        }
        
        // Validar tipo MIME
        $mimetype = $file->get_mimetype();
        if (!in_array($mimetype, $allowed_mimes)) {
            return [
                'valid' => false,
                'message' => get_string('error:invalidfiletype', 'local_conocer_cert', implode(', ', $allowed_mimes))
            ];
        }
        
        // Validar extensión
        $extension = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), self::BLOCKED_EXTENSIONS)) {
            return [
                'valid' => false,
                'message' => get_string('error:blockedextension', 'local_conocer_cert')
            ];
        }
        
        // Validar contenido sospechoso en PDFs
        if ($mimetype === 'application/pdf') {
            $contenido = $file->get_content();
            
            // Buscar código JavaScript incrustado
            if (stripos($contenido, '/JS') !== false || stripos($contenido, '/JavaScript') !== false) {
                return [
                    'valid' => false,
                    'message' => get_string('error:suspiciouspdf', 'local_conocer_cert')
                ];
            }
        }
        
        // Sanitizar nombre de archivo
        $newfilename = clean_param($file->get_filename(), PARAM_FILE);
        if ($newfilename !== $file->get_filename()) {
            // Renombrar archivo
            $fs = get_file_storage();
            $filerecord = [
                'contextid' => $file->get_contextid(),
                'component' => $file->get_component(),
                'filearea' => $file->get_filearea(),
                'itemid' => $file->get_itemid(),
                'filepath' => $file->get_filepath(),
                'filename' => $newfilename,
                'userid' => $file->get_userid()
            ];
            
            $fs->create_file_from_storedfile($filerecord, $file);
            $file->delete();
        }
        
        return [
            'valid' => true,
            'message' => ''
        ];
    }
    
    /**
     * Valida un documento de identidad
     *
     * @param stored_file $file Archivo a validar
     * @return array Resultado de la validación
     */
    public static function validate_id_document($file) {
        $result = self::validate_file($file, self::ALLOWED_ID_MIMES);
        
        if (!$result['valid']) {
            return $result;
        }
        
        // Validaciones adicionales específicas para ID
        $mimetype = $file->get_mimetype();
        
        if (in_array($mimetype, ['image/jpeg', 'image/png'])) {
            // Verificar dimensiones mínimas para asegurar legibilidad
            $imageinfo = $file->get_imageinfo();
            if (empty($imageinfo) || $imageinfo['width'] < 500 || $imageinfo['height'] < 300) {
                return [
                    'valid' => false,
                    'message' => get_string('error:idimagetoosmallorblurry', 'local_conocer_cert')
                ];
            }
        }
        
        return [
            'valid' => true,
            'message' => ''
        ];
    }
    
    /**
     * Valida un documento oficial (acta constitutiva, poder notarial, etc.)
     *
     * @param stored_file $file Archivo a validar
     * @return array Resultado de la validación
     */
    public static function validate_official_document($file) {
        return self::validate_file($file, self::ALLOWED_OFFICIAL_MIMES);
    }
    
    /**
     * Aplica marca de agua con información del usuario a un documento PDF
     *
     * @param stored_file $file Archivo a procesar
     * @param string $text Texto para la marca de agua
     * @return stored_file Archivo procesado
     */
    public static function apply_watermark($file, $text) {
        global $CFG;
        
        // Requiere la librería TCPDF
        require_once($CFG->dirroot . '/lib/pdflib.php');
        
        if ($file->get_mimetype() !== 'application/pdf') {
            return $file;
        }
        
        // Crear temporalmente el archivo
        $tempfile = $CFG->tempdir . '/' . $file->get_contenthash() . '.pdf';
        $file->copy_content_to($tempfile);
        
        // Procesar con TCPDF
        $pdf = new \pdf();
        $pagecount = $pdf->setSourceFile($tempfile);
        
        $newpdf = new \TCPDF();
        $newpdf->setPrintHeader(false);
        $newpdf->setPrintFooter(false);
        
        for ($i = 1; $i <= $pagecount; $i++) {
            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);
            
            $newpdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $newpdf->useTemplate($tpl);
            
            // Añadir marca de agua
            $newpdf->SetFont('helvetica', '', 10);
            $newpdf->SetTextColor(200, 200, 200);
            $newpdf->SetAlpha(0.5);
            $newpdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);
            $newpdf->Text($size['width'] / 4, $size['height'] / 2, $text);
            $newpdf->Rotate(0);
        }
        
        // Guardar el nuevo PDF
        $newpdfcontent = $newpdf->Output('', 'S');
        
        // Crear nuevo archivo
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => $file->get_contextid(),
            'component' => $file->get_component(),
            'filearea' => $file->get_filearea(),
            'itemid' => $file->get_itemid(),
            'filepath' => $file->get_filepath(),
            'filename' => $file->get_filename(),
            'userid' => $file->get_userid()
        ];
        
        // Eliminar el archivo original
        $file->delete();
        
        // Crear nuevo archivo con la marca de agua
        $newfile = $fs->create_file_from_string($filerecord, $newpdfcontent);
        
        // Limpiar archivo temporal
        unlink($tempfile);
        
        return $newfile;
    }
    
    /**
     * Verifica metadatos del documento para asegurar autenticidad
     * 
     * @param stored_file $file Archivo a verificar
     * @param string $tipo Tipo de documento
     * @return array Resultado con metadatos extraídos
     */
    public static function extract_document_metadata($file) {
        $mimetype = $file->get_mimetype();
        $result = [
            'filename' => $file->get_filename(),
            'mimetype' => $mimetype,
            'size' => $file->get_filesize(),
            'metadata' => []
        ];
        
        if ($mimetype === 'application/pdf') {
            // Extraer metadatos de PDF
            $content = $file->get_content();
            
            // Extraer título
            preg_match('/\/Title\s*\((.*?)\)/i', $content, $matches);
            if (!empty($matches[1])) {
                $result['metadata']['title'] = $matches[1];
            }
            
            // Extraer autor
            preg_match('/\/Author\s*\((.*?)\)/i', $content, $matches);
            if (!empty($matches[1])) {
                $result['metadata']['author'] = $matches[1];
            }
            
            // Extraer fecha de creación
            preg_match('/\/CreationDate\s*\((.*?)\)/i', $content, $matches);
            if (!empty($matches[1])) {
                $result['metadata']['creation_date'] = $matches[1];
            }
        } else if (in_array($mimetype, ['image/jpeg', 'image/png'])) {
            // Extraer metadatos EXIF para imágenes
            $imageinfo = $file->get_imageinfo();
            $result['metadata'] = [
                'width' => $imageinfo['width'] ?? 0,
                'height' => $imageinfo['height'] ?? 0
            ];
            
            if ($mimetype === 'image/jpeg' && function_exists('exif_read_data')) {
                $tempfile = tempnam(sys_get_temp_dir(), 'img');
                $file->copy_content_to($tempfile);
                $exif = @exif_read_data($tempfile, 'ANY_TAG', true);
                unlink($tempfile);
                
                if ($exif) {
                    if (!empty($exif['EXIF']['DateTimeOriginal'])) {
                        $result['metadata']['date_taken'] = $exif['EXIF']['DateTimeOriginal'];
                    }
                    if (!empty($exif['IFD0']['Make'])) {
                        $result['metadata']['camera_make'] = $exif['IFD0']['Make'];
                    }
                    if (!empty($exif['IFD0']['Model'])) {
                        $result['metadata']['camera_model'] = $exif['IFD0']['Model'];
                    }
                }
            }
        }
        
        return $result;
    }
}
