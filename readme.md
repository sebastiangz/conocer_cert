# Gestión de Certificaciones CONOCER

Este plugin para Moodle proporciona un sistema integral para la gestión de certificaciones CONOCER (Consejo Nacional de Normalización y Certificación de Competencias Laborales de México) dentro de una instalación de Moodle.

## Descripción General

El plugin de Gestión de Certificaciones CONOCER permite a instituciones educativas y centros de certificación administrar el ciclo completo de certificaciones de competencias, incluyendo:

- Gestión de candidatos que buscan certificación
- Manejo y verificación de documentos cargados
- Asignación de evaluadores externos a candidatos
- Seguimiento de procesos de certificación desde la solicitud hasta la finalización
- Generación y verificación de certificados oficiales
- Registro de empresas como avales de certificación
- Generación de reportes y estadísticas detalladas

## Características

### Para Candidatos
- Solicitar certificación en competencias y niveles específicos
- Cargar documentación requerida (identificación, comprobante de domicilio, etc.)
- Seguir el progreso de certificación a través de un panel personalizado
- Descargar certificados al completar exitosamente el proceso
- Recibir notificaciones automáticas en etapas clave del proceso

### Para Evaluadores
- Ver candidatos asignados en espera de evaluación
- Enviar resultados de evaluación con valoraciones detalladas
- Monitorear carga de trabajo y estadísticas de desempeño
- Gestionar perfil personal de evaluador y competencias

### Para Empresas
- Registrarse como entidades certificadoras
- Seleccionar competencias de interés
- Seguir procesos de certificación dentro de la empresa
- Generar informes específicos para la empresa

### Para Administradores
- Gestionar todo el proceso de certificación
- Revisar y aprobar documentos de candidatos
- Asignar evaluadores a candidatos de certificación
- Configurar competencias y sus niveles
- Generar informes y estadísticas completas
- Personalizar plantillas de notificación

## Instalación

### Requisitos Previos
- Moodle 4.1 o superior
- PHP 7.4 o superior
- Base de datos: MySQL 5.7+ / MariaDB 10.2+ / PostgreSQL 9.6+

### Pasos de Instalación
1. Descargar el paquete del plugin
2. Extraer la carpeta y colocarla en la instalación de Moodle bajo `/local/`
3. Renombrar la carpeta a `conocer_cert` si es necesario
4. Visitar el sitio Moodle como administrador para completar la instalación
5. Configurar los ajustes del plugin en Administración del sitio > Plugins > Plugins locales > Certificación CONOCER

## Configuración

Después de la instalación, necesitará configurar:

1. **Ajustes básicos**:
   - Nombre y logo de la institución
   - Políticas de vencimiento de certificados
   - Restricciones de carga de documentos

2. **Competencias**:
   - Agregar competencias estándar CONOCER con sus códigos oficiales
   - Configurar niveles disponibles para cada competencia
   - Definir documentación requerida

3. **Roles de Usuario**:
   - Asignar administradores del sistema
   - Registrar evaluadores externos
   - Configurar administradores de empresas

## Uso

El plugin agrega un elemento de navegación principal "Certificación CONOCER" que se adapta al rol del usuario:

- **Candidatos** ven sus solicitudes de certificación y progreso
- **Evaluadores** ven sus candidatos asignados y herramientas de evaluación
- **Empresas** ven sus competencias registradas y candidatos
- **Administradores** ven opciones completas de gestión

## Características de Seguridad

El plugin incluye funciones robustas de seguridad:

- Validación de documentos para contenido potencialmente malicioso
- Sistema seguro de verificación de certificados
- Controles de acceso basados en roles
- Registro detallado de eventos de seguridad

## Tareas Programadas

El plugin incluye las siguientes tareas automatizadas:

- Envío de recordatorios a candidatos con documentos pendientes
- Notificación a evaluadores sobre evaluaciones pendientes
- Procesamiento de fechas de vencimiento de certificados
- Generación de informes periódicos para administradores

## Personalización

El sistema utiliza plantillas Mustache que pueden ser sobrescritas en su tema para personalizar la apariencia de:

- Interfaces de panel de control
- Plantillas de certificados
- Mensajes de notificación
- Diseños de informes

## Soporte y Desarrollo

- **Autor**: Sebastian Gonzalez Zepeda
- **Correo electrónico**: sgonzalez@infraestructuragis.com
- **Copyright**: 2025 
- **Licencia**: [GNU GPL v3 o posterior](http://www.gnu.org/copyleft/gpl.html)

Para reportes de errores, solicitudes de funciones u otras consultas, comuníquese directamente con el autor.

## Colaboración

Las contribuciones para mejorar el plugin son bienvenidas. Por favor, siga estos pasos:

1. Hacer un fork del repositorio
2. Crear una rama para la funcionalidad
3. Realizar los cambios
4. Enviar una solicitud de extracción

## Agradecimientos

Este plugin fue desarrollado para apoyar a instituciones educativas en México que ofrecen certificaciones CONOCER, proporcionando una solución digital integral para gestionar todo el proceso de certificación.
