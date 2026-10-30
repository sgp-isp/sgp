<?php
/**
 * LegalController - Módulo de Términos de Servicio y Privacidad Institucional
 * Instituto de Salud Pública del Estado Bolívar (ISPEB)
 */
class LegalController extends Controller
{
    public function index()
    {
        $this->terminos();
    }

    /**
     * Términos y Condiciones de Uso del SGP
     */
    public function terminos()
    {
        $data = [
            'title' => 'Términos y Condiciones de Uso — SGP ISPEB',
            'current_page' => 'terminos'
        ];
        $this->view('legal/terminos', $data, false);
    }

    /**
     * Política de Privacidad y Tratamiento de Datos
     */
    public function privacidad()
    {
        $data = [
            'title' => 'Política de Privacidad y Tratamiento de Datos — SGP ISPEB',
            'current_page' => 'privacidad'
        ];
        $this->view('legal/privacidad', $data, false);
    }
}
