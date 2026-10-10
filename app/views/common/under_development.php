<div class="main-content">
    <div class="dashboard-container">
        <!-- Mensaje de Desarrollo -->
        <div class="development-card">
            <div class="development-icon">
                <i class="ti <?= $data['icon'] ?? 'ti-tool' ?>" style="font-size: 64px; color: #3B82F6;"></i>
            </div>
            <h1 class="development-title">
                <i class="ti ti-hammer"></i> Módulo en Desarrollo
            </h1>
            <h2 class="development-module"><?= $data['module_name'] ?? 'Módulo' ?></h2>
            <p class="development-message">
                <?= $data['message'] ?? 'Este módulo está siendo desarrollado.' ?>
            </p>
            <p class="development-description">
                <?= $data['description'] ?? 'Estará disponible próximamente.' ?>
            </p>
            
            <div class="development-actions">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="ti ti-arrow-left"></i> Volver
                </a>
                <?php
                $rol_id = Session::get('role_id') ?? 0;
                $dashboardUrl = URLROOT . '/dashboard';
                if ($rol_id == 1) $dashboardUrl = URLROOT . '/admin';
                if ($rol_id == 2) $dashboardUrl = URLROOT . '/tutor';
                if ($rol_id == 3) $dashboardUrl = URLROOT . '/pasante';
                ?>
                <a href="<?= $dashboardUrl ?>" class="btn btn-primary">
                    <i class="ti ti-home"></i> Ir al Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.development-card {
    background: white;
    border-radius: 24px;
    padding: 60px 40px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    max-width: 600px;
    margin: 80px auto;
}

.development-icon {
    margin-bottom: 24px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.development-title {
    font-size: 1.5rem;
    color: #64748b;
    margin-bottom: 16px;
    font-weight: 600;
}

.development-module {
    font-size: 2rem;
    color: #162660;
    margin-bottom: 20px;
    font-weight: 700;
}

.development-message {
    font-size: 1.1rem;
    color: #475569;
    margin-bottom: 12px;
}

.development-description {
    font-size: 0.95rem;
    color: #94a3b8;
    margin-bottom: 32px;
}

.development-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: #162660;
    color: white;
}

.btn-primary:hover {
    background: #0d1a3d;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(22, 38, 96, 0.3);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-secondary:hover {
    background: #e2e8f0;
    transform: translateY(-2px);
}
</style>

