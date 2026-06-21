# mod_vidinteractivo

Actividad Moodle para crear videos interactivos con pausas, preguntas y objetos
de aprendizaje insertados en momentos concretos de la reproduccion.

## Compatibilidad

- Moodle 5.0 o superior (`$plugin->requires = 2025041400`).
- Probado a nivel de codigo para una base compatible con Moodle 5.x.
- Moodle 5.3dev sigue siendo una version no liberada; el plugin evita APIs
  experimentales de 5.3 para conservar compatibilidad desde 5.0.

## Estado actual

Esta version incorpora una base modular para los tipos de interaccion:

- Opcion multiple.
- Verdadero / Falso.
- Seleccion sobre imagen con areas rectangulares.
- Arrastrar y soltar.
- Respuesta corta.
- HTML/texto informativo.
- Pausa informativa.

Tambien incluye:

- Timeline con marcadores.
- Pausa automatica.
- Overlay de respuesta para estudiantes.
- Registro historico de intentos.
- Puntuacion maxima por interaccion.
- Intentos permitidos.
- Penalizacion por intento extra.
- Calculo de calificacion final con la formula:

```text
(puntos obtenidos / puntos posibles) * nota maxima de la actividad
```

- Reporte docente basico por estudiante.
- Integracion con Gradebook.
- `db/upgrade.php` para migrar instalaciones existentes del prototipo.
- `amd/build` para instalaciones Moodle normales.

## Estructura principal

```text
mod_vidinteractivo/
├── version.php
├── lib.php
├── mod_form.php
├── view.php
├── report.php
├── styles.css
├── db/
│   ├── install.xml
│   ├── upgrade.php
│   ├── access.php
│   └── services.php
├── classes/
│   ├── event/
│   ├── external/
│   └── interaction/
│       ├── element_interface.php
│       ├── registry.php
│       └── types/
├── templates/
└── amd/
    ├── src/
    └── build/
```

## Pendientes importantes

- Soporte completo de File API para videos subidos a Moodle.
- Soporte nativo para YouTube/Vimeo mediante sus APIs JavaScript.
- Editor visual avanzado para dibujar zonas sobre imagen, en vez de escribir
  coordenadas manualmente.
- Areas poligonales para seleccion sobre imagen.
- Reporte detallado por interaccion e intento.
- Backup/restore Moodle.
- Privacy API.
- Pruebas PHPUnit/Behat dentro de una instalacion Moodle 5.x real.
