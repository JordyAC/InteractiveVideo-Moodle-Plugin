<?php
/**
 * Evento de Moodle para registrar la visualización del módulo mod_vidinteractivo.
 */

namespace mod_vidinteractivo\event;

defined('MOODLE_INTERNAL') || die();

class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Inicializa las propiedades del evento.
     */
    protected function init() {
        parent::init();
        $this->data['objecttable'] = 'vidinteractivo';
    }
}
