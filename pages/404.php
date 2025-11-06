<?php
/**
 * Страница 404 - не найдено
 */

$title = 'Страница не найдена';
include 'templates/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto; text-align: center;">
    <div style="font-size: 5rem; margin-bottom: 1rem;">🔍</div>
    
    <h1 style="font-size: 3rem; color: #2d3748; margin-bottom: 1rem;">404</h1>
    
    <h2 style="color: #718096; margin-bottom: 2rem;">Страница не найдена</h2>
    
    <p style="color: #718096; margin-bottom: 2rem;">
        К сожалению, запрошенная вами страница не существует или была перемещена.
    </p>
    
    <a href="/" class="btn btn-primary">
        Вернуться на главную
    </a>
</div>

<?php include 'templates/footer.php'; ?>
