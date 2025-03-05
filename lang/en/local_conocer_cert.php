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
 * Cadenas de idioma para el plugin local_conocer_cert.
 *
 * @package    local_conocer_cert
 * @copyright  2025 Sebastian Gonzalez Zepeda sgonzalez@infraestructuragis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Información general del plugin
$string['pluginname'] = 'Certificaciones CONOCER';
$string['modulename'] = 'Certificaciones CONOCER';
$string['welcome_message'] = 'Bienvenido al sistema de certificaciones CONOCER. Por favor, seleccione una opción para comenzar.';

// Roles generales
$string['candidate'] = 'Candidato';
$string['evaluator'] = 'Evaluador';
$string['company'] = 'Empresa';
$string['admin'] = 'Administrador';
$string['candidate_description'] = 'Solicite una certificación de competencia laboral.';
$string['evaluator_description'] = 'Evalúe a candidatos para certificaciones de competencia laboral.';
$string['company_description'] = 'Registre a su empresa como aval de competencias laborales.';
$string['apply_as_evaluator'] = 'Solicitar ser evaluador';
$string['register_company'] = 'Registrar empresa';

// Dashboards
$string['admin_dashboard'] = 'Panel de Administración';
$string['candidate_dashboard'] = 'Panel de Candidato';
$string['evaluator_dashboard'] = 'Panel de Evaluador';
$string['company_dashboard'] = 'Panel de Empresa';
$string['dashboard'] = 'Panel';
$string['welcome'] = 'Bienvenido';

// Competencias
$string['competency'] = 'Competencia';
$string['competencies'] = 'Competencias';
$string['conocercompetencies'] = 'Competencias CONOCER';
$string['competencyinfo'] = 'Información de la competencia';
$string['competencycode'] = 'Código de competencia';
$string['competencycodeformat'] = 'El código debe seguir el formato establecido por CONOCER, ej. EC0217';
$string['competencyname'] = 'Nombre de la competencia';
$string['competencycodeexists'] = 'Ya existe una competencia con ese código';
$string['invalidcompetencycode'] = 'Código de competencia inválido. Debe seguir el formato ECXXXX';
$string['competencylevels'] = 'Niveles de competencia';
$string['availablelevels'] = 'Niveles disponibles';
$string['sector'] = 'Sector';
$string['manage_competencies'] = 'Gestionar competencias';
$string['competencystatus'] = 'Estado de la competencia';
$string['active'] = 'Activo';
$string['atleastonelevel'] = 'Debe seleccionar al menos un nivel';
$string['requirements'] = 'Requisitos';
$string['code'] = 'Código';

// Niveles de competencia
$string['level'] = 'Nivel';
$string['level1'] = 'Nivel 1';
$string['level2'] = 'Nivel 2';
$string['level3'] = 'Nivel 3';
$string['level4'] = 'Nivel 4';
$string['level5'] = 'Nivel 5';
$string['levelnotavailable'] = 'El nivel seleccionado no está disponible para esta competencia';

// Sectores
$string['sector_agro'] = 'Agropecuario';
$string['sector_industrial'] = 'Industrial';
$string['sector_commerce'] = 'Comercio';
$string['sector_services'] = 'Servicios';
$string['sector_education'] = 'Educación';
$string['sector_technology'] = 'Tecnología';
$string['sector_health'] = 'Salud';
$string['sector_other'] = 'Otro';

// Información de certificación
$string['requirementseval'] = 'Requisitos y evaluación';
$string['requireddocuments'] = 'Documentos requeridos';
$string['certificationinfo'] = 'Información de certificación';
$string['prerequisites'] = 'Requisitos previos';
$string['evaluationtype'] = 'Tipo de evaluación';
$string['evaltype_practical'] = 'Práctica';
$string['evaltype_theoretical'] = 'Teórica';
$string['evaltype_mixed'] = 'Mixta';
$string['estimatedduration'] = 'Duración estimada (días)';
$string['cost'] = 'Costo';
$string['startdate'] = 'Fecha de inicio';
$string['enddate'] = 'Fecha de fin';
$string['endbeforestart'] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
$string['no_certificate'] = 'Sin certificado';

// Documentos requeridos
$string['requiredcandidatedocs'] = 'Documentos requeridos al candidato';
$string['doc_id_oficial'] = 'Identificación oficial';
$string['doc_curp_doc'] = 'CURP';
$string['doc_comprobante_domicilio'] = 'Comprobante de domicilio';
$string['doc_evidencia_laboral'] = 'Evidencia laboral';
$string['doc_fotografia'] = 'Fotografía';
$string['doc_certificado_estudios'] = 'Certificado de estudios';
$string['doc_curriculum'] = 'Currículum';
$string['doc_carta_recomendacion'] = 'Carta de recomendación';
$string['doc_empresa_acta_constitutiva'] = 'Acta constitutiva';
$string['doc_empresa_rfc'] = 'RFC';
$string['doc_empresa_poder_notarial'] = 'Poder notarial';
$string['doc_empresa_comprobante_fiscal'] = 'Comprobante de domicilio fiscal';
$string['doc_empresa_id_representante'] = 'Identificación del representante legal';

// Estados de documentos
$string['doc_status_pendiente'] = 'Pendiente de revisión';
$string['doc_status_aprobado'] = 'Aprobado';
$string['doc_status_rechazado'] = 'Rechazado';
$string['doc_status_en_revision'] = 'En revisión';

// Errores de documentos
$string['error:filetoobig'] = 'El archivo es demasiado grande. El tamaño máximo permitido es {$a}.';
$string['error:invalidfiletype'] = 'Tipo de archivo no permitido. Los tipos permitidos son: {$a}.';
$string['error:invalidextension'] = 'Extensión de archivo no permitida.';
$string['error:blockedextension'] = 'La extensión del archivo está bloqueada por razones de seguridad.';
$string['error:suspiciouspdf'] = 'El PDF contiene elementos potencialmente peligrosos.';
$string['error:invalidimage'] = 'La imagen no es válida.';
$string['error:imagetoosmall'] = 'La imagen es demasiado pequeña.';
$string['error:imagetoobig'] = 'La imagen es demasiado grande.';
$string['error:idimagetoosmallorblurry'] = 'La imagen de la identificación es demasiado pequeña o borrosa.';
$string['error:phototoosmallorblurry'] = 'La fotografía es demasiado pequeña o borrosa.';
$string['error:photowrongratio'] = 'La fotografía tiene una proporción incorrecta.';
$string['error:virussuspected'] = 'Se sospecha que el archivo contiene un virus.';
$string['warning:filenamesanitized'] = 'El nombre del archivo ha sido sanitizado.';

// Candidatos
$string['manage_candidates'] = 'Gestionar candidatos';
$string['personalinfo'] = 'Información personal';
$string['fullname'] = 'Nombre completo';
$string['curp'] = 'CURP';
$string['curpformat'] = 'Clave Única de Registro de Población (18 caracteres)';
$string['invalidcurp'] = 'CURP inválido. Debe tener 18 caracteres y seguir el formato correcto.';
$string['phone'] = 'Teléfono';
$string['invalidphone'] = 'Número de teléfono inválido. Debe tener 10 dígitos.';
$string['address'] = 'Dirección';
$string['experience'] = 'Experiencia relacionada';
$string['howdidyouhear'] = '¿Cómo se enteró de este programa?';
$string['source_internet'] = 'Internet';
$string['source_friend'] = 'Un amigo';
$string['source_work'] = 'En el trabajo';
$string['source_school'] = 'En la escuela';
$string['source_radio'] = 'Radio';
$string['source_tv'] = 'Televisión';
$string['source_social'] = 'Redes sociales';
$string['source_other'] = 'Otra fuente';
$string['currentemployment'] = 'Situación laboral actual';
$string['employment_employed'] = 'Empleado';
$string['employment_unemployed'] = 'Desempleado';
$string['employment_selfemployed'] = 'Autónomo';
$string['employment_student'] = 'Estudiante';
$string['employment_retired'] = 'Jubilado';
$string['employment_other'] = 'Otra situación';

// Documentos
$string['officialid'] = 'Identificación oficial';
$string['curpdocument'] = 'Documento CURP';
$string['addressproof'] = 'Comprobante de domicilio';
$string['workevidence'] = 'Evidencia de experiencia laboral';
$string['photo'] = 'Fotografía';
$string['additionaldocs'] = 'Documentos adicionales';
$string['upload'] = 'Subir';
$string['upload_documents'] = 'Subir documentos';
$string['document'] = 'Documento';
$string['documents'] = 'Documentos';
$string['view_documents'] = 'Ver documentos';
$string['pendingdocuments'] = 'Documentos pendientes';
$string['pending_docs'] = 'Documentos pendientes';
$string['pending_documents_message'] = 'Tiene documentos pendientes de cargar para completar su solicitud.';

// Evaluación
$string['evaluationpreferences'] = 'Preferencias de evaluación';
$string['evalmode_inperson'] = 'Presencial';
$string['evalmode_virtual'] = 'Virtual';
$string['evalmode_mixed'] = 'Mixta';
$string['evalmode_any'] = 'Cualquiera';
$string['preferredevalmode'] = 'Modalidad preferida';
$string['availability'] = 'Disponibilidad';
$string['availability_weekdays'] = 'Días de semana';
$string['availability_weekends'] = 'Fines de semana';
$string['availability_mornings'] = 'Mañanas';
$string['availability_afternoons'] = 'Tardes';
$string['availability_anytime'] = 'Cualquier momento';
$string['availability_full'] = 'Disponibilidad completa';
$string['availability_partial'] = 'Disponibilidad parcial';
$string['availability_weekends'] = 'Fines de semana';
$string['availability_limited'] = 'Disponibilidad limitada';
$string['additionalcomments'] = 'Comentarios adicionales';
$string['acceptterms'] = 'Acepto los términos y condiciones';
$string['acceptprivacypolicy'] = 'Acepto la política de privacidad';

// Términos y condiciones
$string['terms_and_conditions'] = 'Términos y condiciones';
$string['privacy_policy'] = 'Política de privacidad';

// Empresas
$string['manage_companies'] = 'Gestionar empresas';
$string['companyinfo'] = 'Información de la empresa';
$string['companyname'] = 'Nombre de la empresa';
$string['rfc'] = 'RFC';
$string['rfcformat'] = 'Registro Federal de Contribuyentes';
$string['invalidrfc'] = 'RFC inválido. Debe seguir el formato correcto para personas morales.';
$string['rfcexists'] = 'Ya existe una empresa registrada con ese RFC.';
$string['employeecount'] = 'Número de empleados';
$string['invalidemployeecount'] = 'El número de empleados debe ser un valor numérico positivo.';
$string['contactinfo'] = 'Información de contacto';
$string['contactname'] = 'Nombre del contacto';
$string['contactposition'] = 'Puesto del contacto';
$string['contact'] = 'Contacto';
$string['company_name'] = 'Nombre de la empresa';

// Documentos de empresas
$string['articlesofincorporation'] = 'Acta constitutiva';
$string['rfcdocument'] = 'Documento RFC';
$string['notarialpower'] = 'Poder notarial';
$string['fiscaladdressproof'] = 'Comprobante de domicilio fiscal';
$string['legalrepid'] = 'Identificación del representante legal';

// Competencias de interés
$string['competenciesofinterest'] = 'Competencias de interés';
$string['justification'] = 'Justificación del interés';
$string['selectatleastonecompetency'] = 'Debe seleccionar al menos una competencia';

// Evaluadores
$string['manage_evaluators'] = 'Gestionar evaluadores';
$string['professionalinfo'] = 'Información profesional';
$string['professionallicense'] = 'Cédula profesional';
$string['professionallicensedoc'] = 'Documento de cédula profesional';
$string['professionalformat'] = 'Número de cédula profesional (7-8 dígitos)';
$string['invalidprofessionallicense'] = 'Número de cédula profesional inválido';
$string['academicgrade'] = 'Grado académico';
$string['bachelor'] = 'Licenciatura';
$string['master'] = 'Maestría';
$string['phd'] = 'Doctorado';
$string['technician'] = 'Técnico';
$string['other'] = 'Otro';
$string['yearsofexperience'] = 'Años de experiencia';
$string['invalidyearsofexperience'] = 'Los años de experiencia deben ser un valor numérico positivo';
$string['professionalexperience'] = 'Experiencia profesional';
$string['owncertifications'] = 'Certificaciones propias';
$string['evaluatordocuments'] = 'Documentos del evaluador';
$string['curriculum'] = 'Currículum Vitae';
$string['certificationdocuments'] = 'Documentos de certificación';
$string['evaluatorstatus'] = 'Estado del evaluador';
$string['status'] = 'Estado';
$string['status_active'] = 'Activo';
$string['status_inactive'] = 'Inactivo';
$string['status_pending'] = 'Pendiente';
$string['status_suspended'] = 'Suspendido';
$string['notes'] = 'Notas';
$string['expiration'] = 'Fecha de expiración';
$string['maxcandidates'] = 'Máximo de candidatos simultáneos';
$string['invalidmaxcandidates'] = 'El máximo de candidatos debe ser un valor numérico positivo';
$string['evaluatorrole'] = 'Evaluador CONOCER';
$string['evaluatorrole_desc'] = 'Rol para evaluadores de certificaciones CONOCER';
$string['evaluate'] = 'Evaluar';
$string['evaluate_candidate'] = 'Evaluar candidato';
$string['assign_evaluator'] = 'Asignar evaluador';

// Procesos y etapas
$string['activeprocesses'] = 'Procesos activos';
$string['stage'] = 'Etapa';
$string['stage_solicitud'] = 'Solicitud';
$string['stage_documentacion'] = 'Documentación';
$string['stage_evaluacion'] = 'Evaluación';
$string['stage_resultados'] = 'Resultados';
$string['etapa_solicitud'] = 'Solicitud';
$string['etapa_evaluacion'] = 'Evaluación';
$string['etapa_pendiente_revision'] = 'Pendiente de revisión';
$string['etapa_aprobado'] = 'Aprobado';
$string['etapa_rechazado'] = 'Rechazado';
$string['startdate'] = 'Fecha de inicio';
$string['evaluator'] = 'Evaluador';
$string['no_assigned'] = 'No asignado';
$string['view_process'] = 'Ver proceso';
$string['view_details'] = 'Ver detalles';
$string['process_id'] = 'ID de proceso';
$string['process_result'] = 'Resultado del proceso';

// Certificaciones
$string['certificatefolio'] = 'Folio de certificado';
$string['certificatedate'] = 'Fecha de emisión';
$string['certificateexpiry'] = 'Fecha de vencimiento';
$string['expired'] = 'Vencido';
$string['download_certificate'] = 'Descargar certificado';
$string['certificate'] = 'Certificado';
$string['completedcertifications'] = 'Certificaciones completadas';
$string['mycertifications'] = 'Mis certificaciones';
$string['view_certification'] = 'Ver certificación';
$string['candidate_certifications'] = 'Certificaciones del candidato';
$string['no_certifications'] = 'No hay certificaciones disponibles';
$string['certificate_of_competency'] = 'Certificado de Competencia Laboral';
$string['certificate_states'] = 'Este documento certifica que:';
$string['has_demonstrated_competency'] = 'Ha demostrado competencia en';
$string['certificate_issue_date'] = 'Fecha de emisión';
$string['certificate_expiry_date'] = 'Fecha de vencimiento';
$string['certification_authority'] = 'Autoridad certificadora';
$string['verification_instructions'] = 'Este certificado puede ser verificado en línea utilizando el siguiente enlace:';
$string['verification_code'] = 'Código de verificación';
$string['printcertificate'] = 'Imprimir certificado';
$string['downloadcertificate'] = 'Descargar certificado';
$string['folio'] = 'Folio';
$string['certificateissuersubcontext'] = 'Certificados emitidos';
$string['certificationssubcontext'] = 'Certificaciones';
$string['certificado_vencido'] = 'Certificado vencido';
$string['certificado_disponible'] = 'Certificado disponible';
$string['certificado_por_vencer'] = 'Certificado por vencer';

// Notificaciones
$string['notifications'] = 'Notificaciones';
$string['notif_candidato_registrado_subject'] = 'Solicitud de certificación registrada';
$string['notif_candidato_registrado_message'] = 'Estimado/a {$firstname} {$lastname}, su solicitud de certificación para la competencia "{$competencia}" nivel {$nivel} ha sido registrada exitosamente. Por favor, complete la carga de documentos requeridos para continuar con el proceso.';
$string['notif_documentos_aprobados_subject'] = 'Documentos aprobados para certificación';
$string['notif_documentos_aprobados_message'] = 'Estimado/a {$firstname} {$lastname}, sus documentos para la certificación en "{$competencia}" nivel {$nivel} han sido aprobados. Próximamente se le asignará un evaluador.';
$string['notif_documentos_rechazados_subject'] = 'Documentos rechazados para certificación';
$string['notif_documentos_rechazados_message'] = 'Estimado/a {$firstname} {$lastname}, algunos de sus documentos para la certificación en "{$competencia}" nivel {$nivel} han sido rechazados. Motivo: {$comentarios}. Por favor, cargue nuevamente los documentos corregidos.';
$string['notif_evaluador_asignado_subject'] = 'Evaluador asignado para su certificación';
$string['notif_evaluador_asignado_message'] = 'Estimado/a {$firstname} {$lastname}, se le ha asignado a {$evaluador_nombre} como evaluador para su certificación en "{$competencia}" nivel {$nivel}. El evaluador se pondrá en contacto con usted próximamente.';
$string['notif_proceso_completado_subject'] = 'Proceso de certificación completado';
$string['notif_proceso_completado_message'] = 'Estimado/a {$firstname} {$lastname}, su proceso de certificación para la competencia "{$competencia}" nivel {$nivel} ha sido completado con el resultado: {$resultado}.';
$string['notif_certificado_disponible_subject'] = 'Su certificado está disponible';
$string['notif_certificado_disponible_message'] = 'Estimado/a {$firstname} {$lastname}, su certificado para la competencia "{$competencia}" nivel {$nivel} ya está disponible para descarga.';
$string['notification_mark_read'] = 'Marcar como leída';
$string['notification_mark_all_read'] = 'Marcar todas como leídas';
$string['notification_view_all'] = 'Ver todas';
$string['notification_no_notifications'] = 'No tiene notificaciones';
$string['unread'] = 'No leída';
$string['from'] = 'De';
$string['system'] = 'Sistema';
$string['new'] = 'Nueva';
$string['no_notifications'] = 'No hay notificaciones';

// Eventos
$string['event_candidate_created'] = 'Candidato creado';
$string['event_candidate_created_desc'] = 'El usuario con ID {$userid} solicitó certificación para la competencia {$competencia} nivel {$nivel}';
$string['event_candidate_created_basic_desc'] = 'El usuario con ID {$userid} solicitó una certificación';
$string['candidatecreated'] = 'Candidato creado';
$string['event_company_registered'] = 'Empresa registrada';
$string['event_company_registered_desc'] = 'La empresa {$nombre} con RFC {$rfc} ha sido registrada {$userinfo} {$sectorinfo}';
$string['event_company_registered_unknown'] = 'Una empresa desconocida ha sido registrada';
$string['event_company_registered_by_user'] = 'por el usuario con ID {$userid}';
$string['event_company_registered_sector'] = 'en el sector {$sector}';
$string['companyregistered'] = 'Empresa registrada';
$string['event_certificate_expired'] = 'Certificado vencido';
$string['event_certificate_expired_desc'] = 'El certificado con folio {$folio} del usuario con ID {$userid} para la competencia {$competencia} nivel {$nivel} ha vencido el {$fecha_vencimiento}';
$string['event_certificate_expired_unknown'] = 'Un certificado desconocido ha vencido';
$string['certificateexpired'] = 'Certificado vencido';
$string['event_certification_completed'] = 'Certificación completada';
$string['event_certification_completed_desc'] = 'El usuario con ID {$userid} ha completado la certificación para la competencia {$competencia} nivel {$nivel} con resultado: {$resultado}';
$string['event_certification_completed_unknown'] = 'Una certificación desconocida ha sido completada';
$string['certificationcompleted'] = 'Certificación completada';
$string['event_evaluator_assigned'] = 'Evaluador asignado';
$string['evaluator_assigned'] = 'Evaluador asignado';
$string['event_evaluator_created'] = 'Evaluador creado';
$string['evaluator_created'] = 'Evaluador creado';
$string['event_evaluation_submitted'] = 'Evaluación enviada';
$string['evaluation_submitted'] = 'Evaluación enviada';

// Resultados
$string['result'] = 'Resultado';
$string['resultado_aprobado'] = 'Aprobado';
$string['resultado_rechazado'] = 'Rechazado';
$string['resultado_pendiente'] = 'Pendiente';
$string['resultado_'] = 'Sin resultado';

// Fechas
$string['date'] = 'Fecha';
$string['completion_date'] = 'Fecha de finalización';
$string['request_date'] = 'Fecha de solicitud';
$string['assignment_date'] = 'Fecha de asignación';
$string['evaluation_date'] = 'Fecha de evaluación';

// Acciones
$string['actions'] = 'Acciones';
$string['view'] = 'Ver';
$string['view_all'] = 'Ver todos';

// Estados de solicitudes
$string['estado_pendiente'] = 'Pendiente';
$string['estado_aprobada'] = 'Aprobada';
$string['estado_rechazada'] = 'Rechazada';
$string['estado_en_proceso'] = 'En proceso';
$string['estado_completada'] = 'Completada';

// Estadísticas y reportes
$string['statistics'] = 'Estadísticas';
$string['management'] = 'Gestión';
$string['total_candidates'] = 'Total de candidatos';
$string['total_companies'] = 'Total de empresas';
$string['total_evaluators'] = 'Total de evaluadores';
$string['total_competencies'] = 'Total de competencias';
$string['pending_documents'] = 'Documentos pendientes';
$string['pending_evaluation'] = 'Evaluaciones pendientes';
$string['pending_companies'] = 'Empresas pendientes';
$string['approved_certifications'] = 'Certificaciones aprobadas';
$string['rejected_certifications'] = 'Certificaciones rechazadas';
$string['pending_evaluator_assignments'] = 'Asignaciones de evaluador pendientes';
$string['days'] = 'días';
$string['days_pending'] = 'Días pendiente';
$string['total_assignations'] = 'Total de asignaciones';
$string['pending'] = 'Pendientes';
$string['in_progress'] = 'En progreso';
$string['completed'] = 'Completados';
$string['last_7_days'] = 'Últimos 7 días';
$string['manage_reports'] = 'Gestionar reportes';
$string['view_reports'] = 'Ver reportes';
$string['reports'] = 'Reportes';

// Evaluadores
$string['assigned_candidates'] = 'Candidatos asignados';
$string['recent_evaluations'] = 'Evaluaciones recientes';
$string['view_pending_evaluations'] = 'Ver evaluaciones pendientes';
$string['view_completed_evaluations'] = 'Ver evaluaciones completadas';
$string['edit_profile'] = 'Editar perfil';
$string['no_pending_evaluations'] = 'No hay evaluaciones pendientes';
$string['no_completed_evaluations'] = 'No hay evaluaciones completadas';
$string['pending_evaluations'] = 'Evaluaciones pendientes';

// Administración
$string['administration'] = 'Administración';
$string['quick_actions'] = 'Acciones rápidas';
$string['recent_requests'] = 'Solicitudes recientes';
$string['welcome_to_admin_dashboard'] = 'Bienvenido al Panel de Administración';
$string['no_pending_actions'] = 'No hay acciones pendientes que requieran su atención en este momento.';
$string['candidates'] = 'Candidatos';
$string['companies'] = 'Empresas';
$string['evaluators'] = 'Evaluadores';

// Mensajes de bienvenida y generales
$string['welcome_to_certification'] = 'Bienvenido al Sistema de Certificaciones';
$string['no_activity_message'] = 'No tiene actividad de certificación. Puede iniciar una nueva solicitud de certificación utilizando el botón de abajo.';
$string['start_certification_process'] = 'Iniciar proceso de certificación';
$string['request_certification'] = 'Solicitar certificación';
$string['new_request'] = 'Nueva solicitud';

// Errores y permisos
$string['error_permission'] = 'No tiene permisos para acceder a esta página.';
$string['current'] = 'Actual';

// Configuración
$string['generalsettings'] = 'Configuración general';
$string['certificationauthority'] = 'Autoridad certificadora';
$string['certificationauthoritydesc'] = 'Nombre de la institución que emite los certificados';
$string['certificateduration'] = 'Duración de certificados';
$string['certificatedurationdesc'] = 'Duración predeterminada de los certificados en años';
$string['notificationsettings'] = 'Configuración de notificaciones';
$string['enableemailnotifications'] = 'Habilitar notificaciones por email';
$string['enableemailnotificationsdesc'] = 'Enviar notificaciones por email además de las notificaciones del sistema';
$string['notificationsfromaddress'] = 'Dirección de correo del remitente';
$string['notificationsfromaddressdesc'] = 'Dirección de correo electrónico desde la que se enviarán las notificaciones';
$string['notificationsfromname'] = 'Nombre del remitente';
$string['notificationsfromnamedesc'] = 'Nombre que aparecerá como remitente de las notificaciones';
$string['securitysettings'] = 'Configuración de seguridad';
$string['maxfilesize'] = 'Tamaño máximo de archivos';
$string['maxfilesizedesc'] = 'Tamaño máximo permitido para archivos subidos';
$string['allowedmimetypes'] = 'Tipos MIME permitidos';
$string['allowedmimetypesdesc'] = 'Lista de tipos MIME permitidos para la carga de archivos (separados por comas)';
$string['scanforvirus'] = 'Escanear archivos en busca de virus';
$string['scanforvirusdesc'] = 'Escanear archivos subidos en busca de virus (requiere ClamAV)';
$string['pluginpages'] = 'Páginas del plugin';
$string['gotoplugin'] = 'Ir a la página principal del plugin';

// Verificación de certificados
$string['certificate_not_found'] = 'El certificado no existe o no es válido';
$string['certificate_data_error'] = 'Error al recuperar los datos del certificado';
$string['certificate_valid'] = 'El certificado es válido';
$string['certificate_inactive'] = 'El certificado no está activo';
$string['certificate_expired'] = 'El certificado ha expirado';
$string['renew_certificate'] = 'Renovar certificado';
$string['verification_code'] = 'Código de verificación';

// Panel de candidato
$string['candidate_certification_process'] = 'Proceso de certificación de candidato';
$string['upload_pending_documents'] = 'Cargar documentos pendientes';
$string['view_evaluation_status'] = 'Ver estado de evaluación';
$string['no_documents_uploaded'] = 'No hay documentos cargados';
$string['all_documents_approved'] = 'Todos los documentos han sido aprobados';
$string['document_status'] = 'Estado de documentos';
$string['view_document_status'] = 'Ver estado de documentos';

// Panel de evaluador
$string['evaluator_workload'] = 'Carga de trabajo del evaluador';
$string['total_candidates_evaluated'] = 'Total de candidatos evaluados';
$string['evaluation_success_rate'] = 'Tasa de aprobación';
$string['new_evaluation_subject'] = 'Nueva asignación de evaluación';
$string['new_evaluation_message'] = 'Estimado evaluador, se le ha asignado un nuevo candidato para evaluar: {$candidate} para la competencia {$competency} nivel {$level}. Asignado por: {$assignedby}';
$string['new_evaluation_small'] = 'Nueva asignación de evaluación';
$string['view_candidate_details'] = 'Ver detalles del candidato';
$string['evaluation_result_subject'] = 'Resultado de evaluación';
$string['evaluation_result_message_aprobado'] = 'Felicitaciones, ha aprobado la evaluación para la competencia {$competency} nivel {$level}. Evaluado por: {$evaluator}';
$string['evaluation_result_message_rechazado'] = 'Le informamos que no ha aprobado la evaluación para la competencia {$competency} nivel {$level}. Evaluado por: {$evaluator}. Puede intentarlo nuevamente en el futuro.';
$string['evaluation_result_small'] = 'Resultado de evaluación disponible';
$string['view_evaluation_details'] = 'Ver detalles de la evaluación';
$string['evaluator_nueva_asignacion'] = 'Nueva asignación de candidato';
$string['recordatorio_evaluador'] = 'Recordatorio de evaluación pendiente';

// Panel de la empresa
$string['company_dashboard_title'] = 'Panel de Empresa';
$string['total_certifications'] = 'Total de certificaciones';
$string['completed_certifications'] = 'Certificaciones completadas';
$string['in_progress_certifications'] = 'Certificaciones en proceso';
$string['success_rate'] = 'Tasa de éxito';
$string['candidate_management'] = 'Gestión de candidatos';
$string['candidate_name'] = 'Nombre del candidato';
$string['active_certifications'] = 'Certificaciones activas';
$string['last_activity'] = 'Última actividad';
$string['no_candidates_message'] = 'No hay candidatos registrados para su empresa.';
$string['add_candidate'] = 'Añadir candidato';
$string['add_first_candidate'] = 'Añadir primer candidato';
$string['view_all_candidates'] = 'Ver todos los candidatos';
$string['recent_activities'] = 'Actividades recientes';
$string['no_recent_activities'] = 'No hay actividades recientes';
$string['available_standards'] = 'Estándares disponibles';

// Privacidad
$string['privacy:evaluatorsubcontext'] = 'Información de evaluador';
$string['privacy:companiessubcontext'] = 'Empresas';
$string['privacy:reviewersubcontext'] = 'Documentos revisados';
$string['privacy:evaluationssubcontext'] = 'Evaluaciones realizadas';
$string['privacy:notificationssubcontext'] = 'Notificaciones';
$string['privacy:securitylogsubcontext'] = 'Registro de seguridad';

// Tareas programadas
$string['task_notify_evaluators'] = 'Enviar notificaciones a evaluadores';
$string['task_notify_candidates'] = 'Enviar notificaciones a candidatos';
$string['task_expire_certificates'] = 'Procesar certificados vencidos';
$string['view_expired_certificates'] = 'Ver certificados vencidos';
$string['informe_certificados_vencidos'] = 'Informe de certificados vencidos';
$string['recordatorio_documentos'] = 'Recordatorio de documentos pendientes';
$string['plazo_evaluacion_vencimiento'] = 'Aviso de plazo próximo a vencer';

// Más elementos de navegación
$string['enroll_in_standard'] = 'Inscribir en estándar';
$string['view_profile'] = 'Ver perfil';
$string['view_certifications'] = 'Ver certificaciones';
$string['candidate_profile'] = 'Perfil de candidato';
$string['evaluator_profile'] = 'Perfil de evaluador';
$string['company_profile'] = 'Perfil de empresa';

// Cargos de empresa
$string['director'] = 'Director';
$string['manager'] = 'Gerente';
$string['supervisor'] = 'Supervisor';
$string['coordinator'] = 'Coordinador';
$string['hr_manager'] = 'Gerente de Recursos Humanos';
$string['training_manager'] = 'Gerente de Capacitación';
$string['other_position'] = 'Otro cargo';

// Mensajes de éxito y error
$string['request_created'] = 'Solicitud de certificación creada correctamente';
$string['request_creation_failed'] = 'Error al crear la solicitud de certificación';
$string['request_already_exists'] = 'Ya existe una solicitud para esta competencia y nivel';
$string['document_uploaded'] = 'Documento cargado correctamente';
$string['document_upload_failed'] = 'Error al cargar el documento';
$string['evaluation_submitted'] = 'Evaluación enviada correctamente';
$string['evaluation_submission_failed'] = 'Error al enviar la evaluación';
$string['company_registered'] = 'Empresa registrada correctamente';
$string['company_registration_failed'] = 'Error al registrar la empresa';

// Informes y estadísticas
$string['report_general'] = 'Informe general';
$string['report_certifications'] = 'Informe de certificaciones';
$string['report_companies'] = 'Informe de empresas';
$string['report_evaluators'] = 'Informe de evaluadores';
$string['report_competencies'] = 'Informe de competencias';
$string['by_sector'] = 'Por sector';
$string['most_requested'] = 'Más solicitadas';
$string['by_level'] = 'Por nivel';

// Documentación y ayuda
$string['help'] = 'Ayuda';
$string['documentation'] = 'Documentación';
$string['faq'] = 'Preguntas frecuentes';
$string['contact_support'] = 'Contactar soporte';

// Configuración de reportes
$string['reports_settings'] = 'Configuración de reportes';
$string['report_frequency'] = 'Frecuencia de generación de reportes';
$string['daily'] = 'Diario';
$string['weekly'] = 'Semanal';
$string['monthly'] = 'Mensual';
$string['report_recipients'] = 'Destinatarios de reportes';
$string['include_statistics'] = 'Incluir estadísticas';
$string['include_charts'] = 'Incluir gráficos';
$string['include_pending_items'] = 'Incluir elementos pendientes';

// Estados empresa
$string['estado_pendiente'] = 'Pendiente';
$string['estado_activa'] = 'Activa';
$string['estado_suspendida'] = 'Suspendida';
$string['estado_rechazada'] = 'Rechazada';

// Validación de empresa
$string['company_validation'] = 'Validación de empresa';
$string['company_validation_process'] = 'Proceso de validación de empresa';
$string['company_approved'] = 'Empresa aprobada';
$string['company_rejected'] = 'Empresa rechazada';
$string['company_documents_review'] = 'Revisión de documentos de empresa';

// Otros
$string['emailexists'] = 'El correo electrónico ya está registrado';
$string['invalidemail'] = 'Dirección de correo electrónico no válida';
$string['usernotanevaluator'] = 'El usuario no es un evaluador';
$string['levelnotavailable'] = 'El nivel seleccionado no está disponible para esta competencia';
$string['competencynotfound'] = 'Competencia no encontrada';

// Páginas de Administración - companies.php
$string['companies_title'] = 'Gestión de Empresas';
$string['companies_description'] = 'Gestione las empresas registradas como avales en el sistema de certificaciones CONOCER.';
$string['add_company'] = 'Añadir empresa';
$string['edit_company'] = 'Editar empresa';
$string['delete_company'] = 'Eliminar empresa';
$string['company_details'] = 'Detalles de la empresa';
$string['approve_company'] = 'Aprobar empresa';
$string['reject_company'] = 'Rechazar empresa';
$string['company_docs'] = 'Documentos de la empresa';
$string['company_list'] = 'Lista de empresas';
$string['company_filter'] = 'Filtrar empresas';
$string['company_search'] = 'Buscar empresas';
$string['filter_by_status'] = 'Filtrar por estado';
$string['filter_by_sector'] = 'Filtrar por sector';
$string['company_id'] = 'ID de empresa';
$string['company_created'] = 'Empresa creada';
$string['company_updated'] = 'Empresa actualizada';
$string['company_deleted'] = 'Empresa eliminada';
$string['confirm_delete_company'] = '¿Está seguro de que desea eliminar esta empresa? Esta acción no se puede deshacer.';
$string['no_companies_found'] = 'No se encontraron empresas.';
$string['assigned_competencies'] = 'Competencias asignadas';

// Páginas de Administración - candidates.php
$string['candidates_title'] = 'Gestión de Candidatos';
$string['candidates_description'] = 'Gestione los candidatos a certificación en el sistema CONOCER.';
$string['add_new_candidate'] = 'Añadir nuevo candidato';
$string['edit_candidate'] = 'Editar candidato';
$string['delete_candidate'] = 'Eliminar candidato';
$string['view_candidate'] = 'Ver candidato';
$string['candidate_details'] = 'Detalles del candidato';
$string['candidate_documents'] = 'Documentos del candidato';
$string['candidate_processes'] = 'Procesos del candidato';
$string['candidate_certificates'] = 'Certificados del candidato';
$string['candidate_history'] = 'Historial del candidato';
$string['candidate_id'] = 'ID de candidato';
$string['candidate_created'] = 'Candidato creado';
$string['candidate_updated'] = 'Candidato actualizado';
$string['candidate_deleted'] = 'Candidato eliminado';
$string['confirm_delete_candidate'] = '¿Está seguro de que desea eliminar este candidato? Esta acción no se puede deshacer.';
$string['no_candidates_found'] = 'No se encontraron candidatos.';
$string['candidate_list'] = 'Lista de candidatos';
$string['candidate_filter'] = 'Filtrar candidatos';
$string['candidate_search'] = 'Buscar candidatos';
$string['filter_by_competency'] = 'Filtrar por competencia';
$string['filter_by_level'] = 'Filtrar por nivel';
$string['filter_by_result'] = 'Filtrar por resultado';
$string['document_review'] = 'Revisión de documentos';
$string['review_documents'] = 'Revisar documentos';
$string['approve_document'] = 'Aprobar documento';
$string['reject_document'] = 'Rechazar documento';
$string['document_comments'] = 'Comentarios sobre el documento';
$string['all_documents'] = 'Todos los documentos';
$string['pending_review'] = 'Pendientes de revisión';
$string['approved_documents'] = 'Documentos aprobados';
$string['rejected_documents'] = 'Documentos rechazados';

// Páginas de Administración - evaluators.php
$string['evaluators_title'] = 'Gestión de Evaluadores';
$string['evaluators_description'] = 'Gestione los evaluadores registrados en el sistema de certificaciones CONOCER.';
$string['add_evaluator'] = 'Añadir evaluador';
$string['edit_evaluator'] = 'Editar evaluador';
$string['delete_evaluator'] = 'Eliminar evaluador';
$string['evaluator_details'] = 'Detalles del evaluador';
$string['evaluator_competencies'] = 'Competencias del evaluador';
$string['evaluator_assignments'] = 'Asignaciones del evaluador';
$string['evaluator_history'] = 'Historial del evaluador';
$string['evaluator_statistics'] = 'Estadísticas del evaluador';
$string['evaluator_documents'] = 'Documentos del evaluador';
$string['evaluator_id'] = 'ID de evaluador';
$string['evaluator_created'] = 'Evaluador creado';
$string['evaluator_updated'] = 'Evaluador actualizado';
$string['evaluator_deleted'] = 'Evaluador eliminado';
$string['confirm_delete_evaluator'] = '¿Está seguro de que desea eliminar este evaluador? Esta acción no se puede deshacer.';
$string['no_evaluators_found'] = 'No se encontraron evaluadores.';
$string['evaluator_list'] = 'Lista de evaluadores';
$string['evaluator_filter'] = 'Filtrar evaluadores';
$string['evaluator_search'] = 'Buscar evaluadores';
$string['activate_evaluator'] = 'Activar evaluador';
$string['deactivate_evaluator'] = 'Desactivar evaluador';
$string['suspend_evaluator'] = 'Suspender evaluador';
$string['evaluator_applications'] = 'Solicitudes de evaluador';
$string['approve_application'] = 'Aprobar solicitud';
$string['reject_application'] = 'Rechazar solicitud';

// Páginas de Administración - competencies.php
$string['competencies_title'] = 'Gestión de Competencias';
$string['competencies_description'] = 'Gestione las competencias disponibles en el sistema de certificaciones CONOCER.';
$string['add_competency'] = 'Añadir competencia';
$string['edit_competency'] = 'Editar competencia';
$string['delete_competency'] = 'Eliminar competencia';
$string['competency_details'] = 'Detalles de la competencia';
$string['competency_levels'] = 'Niveles de la competencia';
$string['competency_documents'] = 'Documentos requeridos';
$string['competency_evaluators'] = 'Evaluadores asignados';
$string['competency_statistics'] = 'Estadísticas de la competencia';
$string['competency_id'] = 'ID de competencia';
$string['competency_created'] = 'Competencia creada';
$string['competency_updated'] = 'Competencia actualizada';
$string['competency_deleted'] = 'Competencia eliminada';
$string['confirm_delete_competency'] = '¿Está seguro de que desea eliminar esta competencia? Esta acción no se puede deshacer.';
$string['no_competencies_found'] = 'No se encontraron competencias.';
$string['competency_list'] = 'Lista de competencias';
$string['competency_filter'] = 'Filtrar competencias';
$string['competency_search'] = 'Buscar competencias';
$string['activate_competency'] = 'Activar competencia';
$string['deactivate_competency'] = 'Desactivar competencia';

// Páginas de Administración - reports.php
$string['reports_title'] = 'Informes y Estadísticas';
$string['reports_description'] = 'Visualice informes y estadísticas del sistema de certificaciones CONOCER.';
$string['general_statistics'] = 'Estadísticas generales';
$string['certification_statistics'] = 'Estadísticas de certificación';
$string['evaluator_performance'] = 'Desempeño de evaluadores';
$string['company_statistics'] = 'Estadísticas de empresas';
$string['document_statistics'] = 'Estadísticas de documentos';
$string['monthly_report'] = 'Informe mensual';
$string['annual_report'] = 'Informe anual';
$string['custom_report'] = 'Informe personalizado';
$string['export_report'] = 'Exportar informe';
$string['print_report'] = 'Imprimir informe';
$string['date_range'] = 'Rango de fechas';
$string['start_date'] = 'Fecha inicial';
$string['end_date'] = 'Fecha final';
$string['report_filters'] = 'Filtros del informe';
$string['generate_report'] = 'Generar informe';
$string['chart_type'] = 'Tipo de gráfico';
$string['bar_chart'] = 'Gráfico de barras';
$string['line_chart'] = 'Gráfico de líneas';
$string['pie_chart'] = 'Gráfico circular';
$string['stacked_chart'] = 'Gráfico apilado';
$string['show_data_table'] = 'Mostrar tabla de datos';
$string['include_details'] = 'Incluir detalles';

// Páginas de Candidato - new_request.php
$string['new_request_title'] = 'Nueva Solicitud de Certificación';
$string['new_request_description'] = 'Complete el formulario para solicitar una nueva certificación.';
$string['select_competency'] = 'Seleccionar competencia';
$string['select_level'] = 'Seleccionar nivel';
$string['competency_requirements'] = 'Requisitos de la competencia';
$string['competency_description'] = 'Descripción de la competencia';
$string['request_form'] = 'Formulario de solicitud';
$string['submit_request'] = 'Enviar solicitud';
$string['cancel_request'] = 'Cancelar solicitud';
$string['request_submitted'] = 'Solicitud enviada';
$string['request_canceled'] = 'Solicitud cancelada';
$string['available_competencies'] = 'Competencias disponibles';
$string['competency_cost'] = 'Costo de certificación';
$string['estimated_duration'] = 'Duración estimada';
$string['certifying_authority'] = 'Autoridad certificadora';

// Páginas de Candidato - my_certifications.php
$string['my_certifications_title'] = 'Mis Certificaciones';
$string['my_certifications_description'] = 'Visualice sus certificaciones actuales y anteriores.';
$string['current_certifications'] = 'Certificaciones actuales';
$string['past_certifications'] = 'Certificaciones anteriores';
$string['certification_status'] = 'Estado de certificación';
$string['certification_details'] = 'Detalles de certificación';
$string['certification_date'] = 'Fecha de certificación';
$string['certification_expiry'] = 'Fecha de vencimiento';
$string['certification_id'] = 'ID de certificación';
$string['certification_document'] = 'Documento de certificación';
$string['download_all_certificates'] = 'Descargar todos los certificados';
$string['view_certification_history'] = 'Ver historial de certificación';
$string['renew_expired_certificate'] = 'Renovar certificado vencido';
$string['request_duplicate'] = 'Solicitar duplicado';

// Páginas de Candidato - upload_documents.php
$string['upload_documents_title'] = 'Cargar Documentos';
$string['upload_documents_description'] = 'Cargue los documentos requeridos para su proceso de certificación.';
$string['document_type'] = 'Tipo de documento';
$string['file_to_upload'] = 'Archivo a cargar';
$string['upload_instructions'] = 'Instrucciones de carga';
$string['allowed_file_types'] = 'Tipos de archivo permitidos';
$string['maximum_file_size'] = 'Tamaño máximo de archivo';
$string['upload_selected_file'] = 'Cargar archivo seleccionado';
$string['document_uploaded_successfully'] = 'Documento cargado exitosamente';
$string['upload_failed'] = 'Error en la carga';
$string['required_documents_info'] = 'Información sobre documentos requeridos';
$string['additional_documents_info'] = 'Información sobre documentos adicionales';
$string['document_specifications'] = 'Especificaciones de los documentos';
$string['document_preview'] = 'Vista previa del documento';

// Páginas de Candidato - view_process.php
$string['view_process_title'] = 'Ver Proceso de Certificación';
$string['view_process_description'] = 'Detalles de su proceso de certificación actual.';
$string['process_information'] = 'Información del proceso';
$string['current_stage'] = 'Etapa actual';
$string['process_timeline'] = 'Línea de tiempo del proceso';
$string['assigned_evaluator'] = 'Evaluador asignado';
$string['evaluation_date'] = 'Fecha de evaluación';
$string['evaluation_results'] = 'Resultados de evaluación';
$string['evaluator_comments'] = 'Comentarios del evaluador';
$string['evaluator_recommendations'] = 'Recomendaciones del evaluador';
$string['next_steps'] = 'Próximos pasos';
$string['contact_evaluator'] = 'Contactar evaluador';
$string['view_uploaded_documents'] = 'Ver documentos cargados';
$string['evaluation_score'] = 'Puntuación de evaluación';
$string['passing_score'] = 'Puntuación aprobatoria';

// Páginas de Evaluador - evaluate.php
$string['evaluate_title'] = 'Evaluar Candidato';
$string['evaluate_description'] = 'Complete la evaluación para el candidato asignado.';
$string['candidate_information'] = 'Información del candidato';
$string['evaluation_form'] = 'Formulario de evaluación';
$string['evaluation_criteria'] = 'Criterios de evaluación';
$string['evaluation_instructions'] = 'Instrucciones de evaluación';
$string['submit_evaluation'] = 'Enviar evaluación';
$string['save_draft'] = 'Guardar borrador';
$string['cancel_evaluation'] = 'Cancelar evaluación';
$string['evaluation_complete'] = 'Evaluación completada';
$string['evaluation_saved'] = 'Evaluación guardada';
$string['overall_result'] = 'Resultado general';
$string['specific_comments'] = 'Comentarios específicos';
$string['improvement_areas'] = 'Áreas de mejora';
$string['strengths'] = 'Fortalezas';
$string['candidate_feedback'] = 'Retroalimentación al candidato';
$string['score'] = 'Calificación';
$string['minimum_score'] = 'Calificación mínima';
$string['criterion'] = 'Criterio';
$string['score_scale'] = 'Escala de calificación';
$string['not_evaluated'] = 'No evaluado';
$string['unsatisfactory'] = 'Insatisfactorio';
$string['needs_improvement'] = 'Necesita mejorar';
$string['meets_expectations'] = 'Cumple expectativas';
$string['exceeds_expectations'] = 'Supera expectativas';
$string['not_applicable'] = 'No aplica';

// Páginas de Evaluador - pending.php
$string['pending_evaluations_title'] = 'Evaluaciones Pendientes';
$string['pending_evaluations_description'] = 'Lista de candidatos pendientes de evaluación.';
$string['evaluations_assigned'] = 'Evaluaciones asignadas';
$string['due_date'] = 'Fecha límite';
$string['days_remaining'] = 'Días restantes';
$string['competency_to_evaluate'] = 'Competencia a evaluar';
$string['evaluation_priority'] = 'Prioridad';
$string['high_priority'] = 'Alta';
$string['medium_priority'] = 'Media';
$string['low_priority'] = 'Baja';
$string['past_due'] = 'Vencida';
$string['close_to_due'] = 'Próxima a vencer';
$string['evaluation_id'] = 'ID de evaluación';
$string['sort_by'] = 'Ordenar por';
$string['oldest_first'] = 'Más antiguas primero';
$string['newest_first'] = 'Más recientes primero';
$string['priority_order'] = 'Por prioridad';
$string['candidate_order'] = 'Por candidato';

// Páginas de Empresa - register.php
$string['register_company_title'] = 'Registro de Empresa';
$string['register_company_description'] = 'Complete el formulario para registrar su empresa como aval de certificaciones CONOCER.';
$string['company_information'] = 'Información de la empresa';
$string['legal_information'] = 'Información legal';
$string['company_legal_name'] = 'Razón social';
$string['company_commercial_name'] = 'Nombre comercial';
$string['commercial_activity'] = 'Actividad comercial';
$string['company_size'] = 'Tamaño de la empresa';
$string['micro_enterprise'] = 'Microempresa';
$string['small_enterprise'] = 'Pequeña empresa';
$string['medium_enterprise'] = 'Mediana empresa';
$string['large_enterprise'] = 'Gran empresa';
$string['company_website'] = 'Sitio web';
$string['company_phone'] = 'Teléfono';
$string['company_email'] = 'Correo electrónico';
$string['company_address'] = 'Dirección';
$string['postal_code'] = 'Código postal';
$string['state'] = 'Estado';
$string['city'] = 'Ciudad';
$string['legal_representative'] = 'Representante legal';
$string['legal_representative_position'] = 'Cargo del representante legal';
$string['company_registration_date'] = 'Fecha de constitución';
$string['registration_motivation'] = 'Motivación para registrarse';
$string['interest_standards'] = 'Estándares de interés';
$string['terms_acceptance'] = 'Aceptación de términos';
$string['data_privacy_consent'] = 'Consentimiento de privacidad de datos';
$string['submit_registration'] = 'Enviar registro';
$string['cancel_registration'] = 'Cancelar registro';
$string['registration_submitted'] = 'Registro enviado';
$string['registration_confirmation'] = 'Confirmación de registro';

// Páginas comunes
$string['back'] = 'Volver';
$string['continue'] = 'Continuar';
$string['save'] = 'Guardar';
$string['next'] = 'Siguiente';
$string['previous'] = 'Anterior';
$string['finish'] = 'Finalizar';
$string['search'] = 'Buscar';
$string['filter'] = 'Filtrar';
$string['reset'] = 'Restablecer';
$string['apply_filters'] = 'Aplicar filtros';
$string['clear_filters'] = 'Limpiar filtros';
$string['no_results'] = 'No se encontraron resultados';
$string['all'] = 'Todos';
$string['select'] = 'Seleccionar';
$string['delete'] = 'Eliminar';
$string['edit'] = 'Editar';
$string['close'] = 'Cerrar';
$string['confirm'] = 'Confirmar';
$string['cancel'] = 'Cancelar';
$string['print'] = 'Imprimir';
$string['export'] = 'Exportar';
$string['import'] = 'Importar';
$string['download'] = 'Descargar';
$string['preview'] = 'Vista previa';
$string['details'] = 'Detalles';
$string['more_info'] = 'Más información';
$string['less_info'] = 'Menos información';
$string['show_more'] = 'Mostrar más';
$string['show_less'] = 'Mostrar menos';
$string['loading'] = 'Cargando';
$string['processing'] = 'Procesando';
$string['success'] = 'Éxito';
$string['error'] = 'Error';
$string['warning'] = 'Advertencia';
$string['info'] = 'Información';
$string['confirmation_required'] = 'Se requiere confirmación';
$string['operation_successful'] = 'Operación exitosa';
$string['operation_failed'] = 'Error en la operación';
$string['please_wait'] = 'Por favor, espere...';
$string['no_data'] = 'No hay datos disponibles';
$string['yes'] = 'Sí';
$string['no'] = 'No';
$string['ok'] = 'Aceptar';

