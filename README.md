# Módulo de Video Interactivo para Moodle (mod_vidinteractivo)

Módulo de actividad nativo para Moodle que permite crear videos interactivos con simulaciones procedimentales y evaluación formativa mediante zonas activas (hotspots).

## Descripción

Este plugin transforma contenido de video pasivo en experiencias de aprendizaje activo mediante la inserción de zonas interactivas que interrumpen la reproducción en marcas de tiempo predefinidas. Los estudiantes deben completar simulaciones, responder preguntas o identificar elementos de interfaz antes de continuar. Aunque fue desarrollado y validado utilizando tutoriales sobre el gestor bibliográfico Zotero como caso de estudio, el módulo es completamente agnóstico al contenido y aplicable a cualquier materia que requiera evaluación de competencias procedimentales.

## Características Principales

- **Integración Nativa con Moodle**: Desarrollado como módulo de actividad estándar (mod) siguiendo las guías oficiales de desarrollo de Moodle. No requiere dependencias externas, iFrames ni herramientas de autoría de terceros.

- **Motor de Video Adaptativo**: Soporta tanto archivos de video locales (HTML5) como recursos externos de YouTube mediante la implementación del patrón Adapter, proporcionando una interfaz de control unificada independientemente de la fuente del medio.

- **Sistema de Hotspots Interactivos**: Permite crear interrupciones basadas en tiempo con múltiples tipos de interacción:
  - Selección sobre imagen (simulaciones de interfaz con clics en coordenadas)
  - Preguntas de opción múltiple
  - Validación verdadero/falso
  - Respuestas cortas
  - Arrastrar y soltar
  - Superposiciones de texto enriquecido
  - Anotaciones sobre capturas de pantalla

- **Integración con el Libro de Calificaciones**: Sincronización automática con el sistema de calificación de Moodle mediante la API nativa grade_update. Calcula las calificaciones finales basándose en los mejores intentos a través de todos los hotspots válidos.

- **Persistencia de Progreso**: Rastrea la posición de visualización del estudiante, el historial de intentos y el estado de completado a través de sesiones asíncronas. Fuerza el retroceso a puntos de control anteriores cuando las interacciones obligatorias fallan.

- **Herramientas de Autoría para Docentes**: Editor visual de línea de tiempo para colocar marcadores interactivos y definir coordenadas de hotspots directamente sobre capturas de pantalla del video mediante selección con arrastrar y soltar.

## Arquitectura Técnica

### Backend (PHP)
- **Esquema de Base de Datos**: Cuatro tablas normalizadas mediante XMLDB (vidinteractivo, vidinteractivo_interactions, vidinteractivo_attempts, vidinteractivo_progress)
- **Funciones del Núcleo**: lib.php implementa callbacks CRUD estándar de Moodle (add_instance, update_instance, delete_instance)
- **Form API**: mod_form.php utiliza MoodleForms para captura sanitizada de parámetros y configuración
- **Servicios AJAX**: Servicios web para guardado asíncrono de intentos y cálculo de calificaciones

### Frontend (JavaScript/HTML5)
- **Módulos AMD**: Definición de módulos asíncronos siguiendo el estándar oficial de Moodle
- **Patrón Adapter de Video**: Interfaz unificada que abstrae las complejidades de HTML5 Video API y YouTube iFrame API
- **Manipulación del DOM**: Inyección dinámica de superposiciones con posicionamiento absoluto para simulaciones interactivas
- **Event Listeners**: Suscripción a eventos timeupdate para sincronización precisa de marcas de tiempo (sondeo cada 250ms para YouTube)

## Requisitos

- Moodle 5.0 o superior (probado en Moodle 5.3 Dev)
- PHP 8.x
- Base de datos: PostgreSQL, MariaDB o MySQL
- Navegador moderno con soporte para HTML5 Video API y ES6+

## Instalación

1. Clone o descargue este repositorio en su instalación de Moodle:
2. Navegue a Administración del Sitio > Notificaciones como administrador. Moodle detectará automáticamente el nuevo plugin y solicitará la instalación de la base de datos.

3. Confirme la instalación. El módulo aparecerá en el selector de actividades al editar un curso.

## Estructura del Proyecto
mod/vidinteractivo/
├── db/ # Esquema XMLDB, access.php, scripts de actualización
├── lang/ # Cadenas de internacionalización (es/en)
├── classes/ # Clases PHP (eventos, privacidad, formularios)
├── amd/ # Módulos JavaScript AMD (player, timeline, adapter)
├── templates/ # Plantillas Mustache para renderizado
├── styles.css # Estilos del reproductor y superposiciones
├── lib.php # Callbacks del ciclo de vida del módulo
├── mod_form.php # Formulario de configuración para docentes
├── view.php # Vista principal de la actividad
└── version.php # Metadatos del plugin


## Uso

### Para Docentes
1. Active la edición en su curso y haga clic en "Añadir una actividad o recurso"
2. Seleccione "Video Interactivo" de la lista de actividades
3. Configure los ajustes generales (nombre, descripción, calificación)
4. Suba un archivo de video o pegue una URL de YouTube
5. Utilice el editor de línea de tiempo para colocar hotspots interactivos:
   - Haga clic en la línea de tiempo para establecer la marca de tiempo
   - Suba o capture una captura de pantalla de la interfaz a simular
   - Dibuje zonas clickeables usando la herramienta de selección de coordenadas
   - Configure el tipo de pregunta, puntuación máxima e intentos permitidos
6. Guarde y regrese al curso

### Para Estudiantes
1. Haga clic en la actividad de video interactivo
2. Vea el video; la reproducción se pausará automáticamente en las marcas de tiempo configuradas
3. Complete el desafío interactivo:
   - Para simulaciones: Haga clic en el elemento correcto de la interfaz en la captura de pantalla
   - Para preguntas: Seleccione o escriba su respuesta
4. Reciba retroalimentación inmediata (animaciones de éxito/error)
5. Si quedan intentos, reintente o continúe al siguiente segmento
6. El progreso se guarda automáticamente; puede reanudar desde la última posición

## Contexto de Desarrollo

**Institución**: Escuela Superior Politécnica de Chimborazo (ESPOCH)  
**Facultad**: Informática y Electrónica  
**Carrera**: Ingeniería en Software  
**Materia**: Entornos Virtuales de Aprendizaje  
**Equipo**: Los IA's  
**Tutor**: PhD. Danilo Pastor  
**Período**: Marzo - Julio 2026

## Metodología

Este proyecto fue desarrollado utilizando metodología de Prototipado Rápido con tres sprints iterativos:
1. **Estructuración del Esquema**: Definición de tablas XMLDB e instalación limpia
2. **Implementación del Backend**: Lógica PHP para manejo de formularios y persistencia de datos
3. **Desarrollo del Frontend**: Algoritmos JavaScript para control de video y validación de colisiones

El enfoque se centró en la entrega de código funcional más que en marcos de diseño instruccional extensivos, permitiendo la finalización dentro de las restricciones de tiempo académico.

## Ambiente de Pruebas

- Instancia Moodle 5.3 Dev en contenedor Docker
- Google Chrome 120+ DevTools para depuración de frontend
- OBS Studio para captura de interfaz de Zotero 7
- IDE Visual Studio Code con control de versiones Git

## Contribuciones

Las contribuciones son bienvenidas. Por favor siga estas guías:
1. Haga un fork del repositorio
2. Cree una rama para su característica (`git checkout -b feature/CaracteristicaIncreible`)
3. Confirme sus cambios (`git commit -m 'Añadir alguna CaracteristicaIncreible'`)
4. Haga push a la rama (`git push origin feature/CaracteristicaIncreible`)
5. Abra un Pull Request

Asegúrese de que el código cumpla con los estándares de codificación de Moodle y pase las pruebas unitarias antes de enviar.

## Licencia

Distribuido bajo la Licencia GNU GPL v3. Consulte el archivo LICENSE para más información.

## Referencias

- Documentación para Desarrolladores de Moodle: Guías de arquitectura de módulos de actividad. https://moodle.dev/general/development/plugin-types/mod
- Mayer, R. E. (2020). Multimedia Learning (3rd ed.). Cambridge University Press.
- García-Aretio, L. (2017). Los videos interactivos en los entornos virtuales de aprendizaje. RIED, 20(2), 9-18.
- Mozilla MDN: Evento timeupdate de HTMLMediaElement. https://developer.mozilla.org/en-US/docs/Web/API/HTMLMediaElement/timeupdate_event

## Agradecimientos

- Escuela Superior Politécnica de Chimborazo por proporcionar la infraestructura de desarrollo
- Comunidad de Moodle por el framework LMS de código abierto
- Proyecto Zotero por servir como caso de uso educativo

## Soporte

Para problemas, preguntas o sugerencias, utilice la sección de Issues de GitHub de este repositorio.
