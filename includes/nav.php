<?php
require_once 'includes/auth.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Gerenciador de Projetos</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        Visão Geral
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'cronograma.php' ? 'active' : ''; ?>" href="cronograma.php">
                        Cronograma
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'tarefas.php' ? 'active' : ''; ?>" href="tarefas.php">
                        Controle de Tarefas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'stakeholders.php' ? 'active' : ''; ?>" href="stakeholders.php">
                        Stakeholders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'reunioes.php' ? 'active' : ''; ?>" href="reunioes.php">
                        Reuniões
                    </a>
                </li>
                <?php if (verificarPermissao('gerenciar_usuarios')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="usuarios.php">Usuários</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="nav-link">Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? ''); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Sair</a>
                </li>
            </ul>
        </div>
    </div>
</nav> 