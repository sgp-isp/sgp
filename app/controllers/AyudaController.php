<?php
declare(strict_types=1);

class AyudaController extends Controller
{
    public function index(): void
    {
        Session::start();
        AuthMiddleware::require();
        $this->view('ayuda/index', []);
    }
}
