<?php
/**
 * Breadcrumbs Component
 * 
 * @param array $items - Array de items: ['label' => 'Texto', 'url' => 'link'] o solo 'label' para el actual
 * 
 * Uso:
 * <?php
 * $breadcrumbItems = [
 *     ['label' => 'Gestión', 'url' => URLROOT . '/admin'],
 *     ['label' => 'Usuarios']
 * ];
 * include APPROOT . '/views/layouts/breadcrumbs.php';
 * ?>
 */

if (!isset($items) || empty($items)) {
    $items = [
        ['label' => 'Escritorio']
    ];
}
?>

<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= URLROOT ?>/public/dashboard">
        <i class="ti ti-home"></i>
        Inicio
    </a>
    
    <?php foreach ($items as $index => $item): ?>
        <?php if ($index === count($items) - 1): ?>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-current"><?= htmlspecialchars($item['label']) ?></span>
        <?php else: ?>
            <span class="breadcrumb-separator">›</span>
            <a href="<?= htmlspecialchars($item['url']) ?>">
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
