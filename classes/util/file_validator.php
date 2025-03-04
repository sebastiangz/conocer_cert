<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * File validator for CONOCER certification plugin.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_conocer_cert\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for validating files uploaded in the certification process.
 */
class file_validator {
    /** @var array Tipos MIME permitidos para documentos de identidad */
    const ALLOWED_ID_MIMES = ['application/pdf', 'image/jpeg', 'image/png'];
    
    /** @var array Tipos MIME permitidos para documentos oficiales */
    const ALLOWED_OFFICIAL_MIMES = ['application/pdf'];
    
    /** @var array Tipos MIME permitidos para fotografías */
    const ALLOWED_PHOTO_MIMES = ['image/jpeg', 'image/png'];
    
    /** @var int Tamaño máximo para archivos (10MB) */
    const MAX_FILE_SIZE = 10485760;
    
    /** @var int Tamaño máximo para fotografías (2MB) */
    const MAX_PHOTO_SIZE = 2097152;
    
    /** @var array Extensiones de virus conocidas para bloquear */
    const BLOCKED_EXTENSIONS = ['exe', 'bat', 'com', 'cmd', 'scr', 'pif', 'js', 'vbs', 'ps1', 'msi', 'htaccess'];
    
    /** @var array Extensiones permitidas para documentos */
    const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    
    /**
     * Valida un archivo subido
     *
     * @param \stored_file $file Archivo a validar
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
        if (!in_array(strtolower($extension), self::ALLOWED_EXTENSIONS)) {
            return [
                'valid' => false,
                'message' => get_string('error:invalidextension', 'local_conocer_cert')
            ];
        }
        
        // Verificar extensiones bloqueadas
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
            
            // Buscar otros contenidos maliciosos
            $suspicious_patterns = [
                '/Launch', '/URL ', '/SubmitForm', '/GoTo', '/URI', '/GoToR', 
                '/AA', '/OpenAction', '/AcroForm'
            ];
            
            foreach ($suspicious_patterns as $pattern) {
                if (stripos($contenido, $pattern) !== false) {
                    return [
                        'valid' => false,
                        'message' => get_string('error:suspiciouspdf', 'local_conocer_cert')
                    ];
                }
            }
        }
        
        // Validar imágenes
        if (in_array($mimetype, ['image/jpeg', 'image/png'])) {
            $imageinfo = $file->get_imageinfo();
            
            // Verificar si es una imagen válida
            if (empty($imageinfo)) {
                return [
                    'valid' => false,
                    'message' => get_string('error:invalidimage', 'local_conocer_cert')
                ];
            }
            
            // Verificar dimensiones mínimas
            if ($imageinfo['width'] < 300 || $imageinfo['height'] < 300) {
                return [
                    'valid' => false,
                    'message' => get_string('error:imagetoosmall', 'local_conocer_cert')
                ];
            }
            
            // Verificar dimensiones máximas
            if ($imageinfo['width'] > 4000 || $imageinfo['height'] > 4000) {
                return [
                    'valid' => false,
                    'message' => get_string('error:imagetoobig', 'local_conocer_cert')
                ];
            }
        }
        
        // Sanitizar nombre de archivo
        $originalFilename = $file->get_filename();
        $sanitizedFilename = clean_param($originalFilename, PARAM_FILE);
        
        if ($sanitizedFilename !== $originalFilename) {
            // Si necesitamos renombrar, lo haríamos aquí, pero en realidad Moodle
            // ya maneja esto en sus API de almacenamiento de archivos
            return [
                'valid' => true,
                'message' => get_string('warning:filenamesanitized', 'local_conocer_cert'),
                'sanitized_name' => $sanitizedFilename
            ];
        }
        
        return [
            'valid' => true,
            'message' => ''
        ];
    }
    
    /**
     * Valida un documento de identidad
     *
     * @param \stored_file $file Archivo a validar
     * @return array Resultado de la validación
     */
    public static function validate_id_document($file) {
        // Primero validamos aspectos generales del archivo
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
        
        // Verificar contenido sensible (DNI, pasaporte, etc.)
        if ($mimetype === 'application/pdf') {
            // Para esta validación necesitaríamos procesar el PDF con una
            // herramienta más avanzada o un servicio externo
            // Aquí solo implementamos una validación básica
        }
        
        return [
            'valid' => true,
            'message' => ''
        ];
    }
    
    /**
     * Valida un documento oficial (acta constitutiva, poder notarial, etc.)
     *
     * @param \stored_file $file Archivo a validar
     * @return array Resultado de la validación
     */
    public static function validate_official_document($file) {
        return self::validate_file($file, self::ALLOWED_OFFICIAL_MIMES);
    }
    
    /**
     * Valida una fotografía
     *
     * @param \stored_file $file Archivo a validar
     * @return array Resultado de la validación
     */
    public static function validate_photo($file) {
        // Primero validamos aspectos generales del archivo
        $result = self::validate_file($file, self::ALLOWED_PHOTO_MIMES, self::MAX_PHOTO_SIZE);
        
        if (!$result['valid']) {
            return $result;
        }
        
        // Validaciones específicas para fotografías
        $imageinfo = $file->get_imageinfo();
        
        // Verificar dimensiones mínimas
        if (empty($imageinfo) || $imageinfo['width'] < 400 || $imageinfo['height'] < 400) {
            return [
                'valid' => false,
                'message' => get_string('error:phototoosmallorblurry', 'local_conocer_cert')
            ];
        }
        
        // Verificar proporción (debe ser aproximadamente 3:4 o 1:1)
        $ratio = $imageinfo['width'] / $imageinfo['height'];
        if ($ratio < 0.7 || $ratio > 1.3) {
            return [
                'valid' => false,
                'message' => get_string('error:photowrongratio', 'local_conocer_cert')
            ];
        }
        
        return [
            'valid' => true,
            'message' => ''
        ];
    }
    
    /**
     * Aplica marca de agua con información del usuario a un documento PDF
     *
     * @param \stored_file $file Archivo a procesar
     * @param string $text Texto para la marca de agua
     * @return \stored_file|bool Archivo procesado o false en caso de error
     */
    public static function apply_watermark($file, $text) {
        global $CFG;
        
        // Verificar que sea un PDF
        if ($file->get_mimetype() !== 'application/pdf') {
            return false;
        }
        
        // Requiere la librería TCPDF
        require_once($CFG->libdir . '/pdflib.php');
        
        try {
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
            
            // Crear nuevo archivo en storage
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
        } catch (\Exception $e) {
            if (file_exists($tempfile)) {
                unlink($tempfile);
            }
            return false;
        }
    }
    
    /**
     * Extrae metadatos del documento para asegurar autenticidad
     * 
     * @param \stored_file $file Archivo a verificar
     * @return array Resultado con metadatos extraídos
     */
    public static function extract_document_metadata($file) {
        $mimetype = $file->get_mimetype();
        $result = [
            'filename' => $file->get_filename(),
            'mimetype' => $mimetype,
            'size' => $file->get_filesize(),
            'metadata' => [],
            'hash' => $file->get_contenthash()
        ];
        
        if ($mimetype === 'application/pdf') {
            // Extraer metadatos de PDF
            $content = $file->get_content();
            
            // Extraer título
            if (preg_match('/\/Title\s*\((.*?)\)/i', $content, $matches)) {
                $result['metadata']['title'] = self::decode_pdf_string($matches[1]);
            }
            
            // Extraer autor
            if (preg_match('/\/Author\s*\((.*?)\)/i', $content, $matches)) {
                $result['metadata']['author'] = self::decode_pdf_string($matches[1]);
            }
            
            // Extraer fecha de creación
            if (preg_match('/\/CreationDate\s*\((.*?)\)/i', $content, $matches)) {
                $result['metadata']['creation_date'] = self::decode_pdf_string($matches[1]);
            }
            
            // Extraer productor
            if (preg_match('/\/Producer\s*\((.*?)\)/i', $content, $matches)) {
                $result['metadata']['producer'] = self::decode_pdf_string($matches[1]);
            }
            
            // Extraer creador
            if (preg_match('/\/Creator\s*\((.*?)\)/i', $content, $matches)) {
                $result['metadata']['creator'] = self::decode_pdf_string($matches[1]);
            }
        } else if (in_array($mimetype, ['image/jpeg', 'image/png'])) {
            // Extraer metadatos EXIF para imágenes
            $imageinfo = $file->get_imageinfo();
            if (!empty($imageinfo)) {
                $result['metadata'] = [
                    'width' => $imageinfo['width'],
                    'height' => $imageinfo['height']
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
                        if (!empty($exif['EXIF']['Software'])) {
                            $result['metadata']['software'] = $exif['EXIF']['Software'];
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Decodifica cadenas en formato PDF
     *
     * @param string $string Cadena a decodificar
     * @return string Cadena decodificada
     */
    private static function decode_pdf_string($string) {
        // Las cadenas en PDF pueden estar escapadas con secuencias como \( \) \\ etc.
        $string = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $string);
        
        // También pueden estar codificadas en octal: \nnn
        $string = preg_replace_callback('/\\\\([0-9]{3})/', function($matches) {
            return chr(octdec($matches[1]));
        }, $string);
        
        return $string;
    }
    
    /**
     * Valida un archivo contra virus conocidos
     * 
     * Esta función es un placeholder. En un entorno real, deberías
     * integrar un antivirus como ClamAV o un servicio externo.
     *
     * @param \stored_file $file Archivo a escanear
     * @return array Resultado del escaneo
     */
    public static function scan_for_viruses($file) {
        // En un entorno de producción, esta función debería integrarse
        // con un antivirus como ClamAV
        // Ejemplo básico:
        
        $suspicious = false;
        $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        
        // Verificar extensiones de alto riesgo
        if (in_array($extension, self::BLOCKED_EXTENSIONS)) {
            $suspicious = true;
        }
        
        // Verificar contenido sospechoso en ciertos tipos de archivo
        if (in_array($extension, ['pdf', 'doc', 'docx'])) {
            $content = $file->get_content();
            
            // Buscar patrones sospechosos (muy básico)
            $patterns = [
                'eval(', 'exec(', 'system(', 'cmd.exe', 'powershell',
                '<?php', '<%', 'scripting.filesystemobject',
                'JFIF corrupted', 'PK\x03\x04' // ZIP header in documento
            ];
            
            foreach ($patterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $suspicious = true;
                    break;
                }
            }
        }
        
        return [
            'clean' => !$suspicious,
            'message' => $suspicious ? get_string('error:virussuspected', 'local_conocer_cert') : ''
        ];
    }
    
    /**
     * Verifica si un tipo MIME está permitido
     *
     * @param string $mimetype Tipo MIME a verificar
     * @param string $filearea Área de archivo (id_oficial, curp_doc, etc.)
     * @return bool Si el tipo MIME está permitido
     */
    public static function is_allowed_mimetype($mimetype, $filearea) {
        switch ($filearea) {
            case 'id_oficial':
            case 'curp_doc':
            case 'comprobante_domicilio':
            case 'evidencia_laboral':
                return in_array($mimetype, self::ALLOWED_ID_MIMES);
            
            case 'acta_constitutiva':
            case 'rfc_doc':
            case 'poder_notarial':
            case 'comprobante_fiscal':
                return in_array($mimetype, self::ALLOWED_OFFICIAL_MIMES);
            
            case 'fotografia':
                return in_array($mimetype, self::ALLOWED_PHOTO_MIMES);
            
            default:
                return in_array($mimetype, self::ALLOWED_ID_MIMES);
        }
    }
    
    /**
     * Obtiene el tamaño máximo permitido según el área de archivo
     *
     * @param string $filearea Área de archivo
     * @return int Tamaño máximo en bytes
     */
    public static function get_max_filesize($filearea) {
        switch ($filearea) {
            case 'fotografia':
                return self::MAX_PHOTO_SIZE;
            
            default:
                return self::MAX_FILE_SIZE;
        }
    }
}
