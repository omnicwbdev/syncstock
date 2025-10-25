<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Crontab</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cron-code {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #4ec9b0;
            padding: 8px 12px;
            border-radius: 4px;
            word-break: break-all;
        }
        .comment-line {
            color: #6c757d;
            font-style: italic;
        }
        .task-card {
            transition: all 0.3s ease;
        }
        .task-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .example-table {
            background: transparent !important;
        }
        .example-table td {
            border: none !important;
            padding: 8px 12px;
        }
        .example-table tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero is-primary hero-gradient">
        <div class="hero-body">
            <div class="container">
                <div class="columns is-centered">
                    <div class="column is-8 has-text-centered">
                        <h1 class="title is-2">
                            <i class="fas fa-calendar-alt mr-3"></i>
                            Gerenciador de Crontab
                        </h1>
                        <p class="subtitle is-5">
                            Gerencie suas tarefas agendadas de forma simples e intuitiva
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="section">
        <div class="container">
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['mensagem'])): ?>
                <div class="notification is-success is-light">
                    <button class="delete"></button>
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($_SESSION['mensagem']) ?>
                </div>
                <?php unset($_SESSION['mensagem']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="notification is-danger is-light">
                    <button class="delete"></button>
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?= htmlspecialchars($_SESSION['erro']) ?>
                </div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <div class="columns">
                <!-- Left Column - Forms and Actions -->
                <div class="column is-4">
                    <!-- Examples Card -->
                    <div class="card mb-5">
                        <div class="card-header">
                            <div class="card-header-title">
                                <i class="fas fa-lightbulb has-text-warning mr-2"></i>
                                Exemplos de Agendamento
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="content">
                                <table class="table example-table is-fullwidth is-hoverable">
                                    <tbody>
                                        <?php foreach ($exemplos as $pattern => $descricao): ?>
                                            <tr>
                                                <td class="has-text-weight-semibold" style="width: 120px;">
                                                    <code><?= htmlspecialchars($pattern) ?></code>
                                                </td>
                                                <td class="is-size-7"><?= htmlspecialchars($descricao) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Add Task Form -->
                    <div class="card mb-5">
                        <div class="card-header">
                            <div class="card-header-title">
                                <i class="fas fa-plus-circle has-text-success mr-2"></i>
                                Adicionar Nova Tarefa
                            </div>
                        </div>
                        <div class="card-content">
                            <form method="POST">
                                <div class="field">
                                    <label class="label">Agendamento (Cron Pattern)</label>
                                    <div class="control has-icons-left">
                                        <input type="text" 
                                               class="input" 
                                               name="agendamento" 
                                               placeholder="* * * * *"
                                               value="<?= htmlspecialchars($_POST['agendamento'] ?? '') ?>"
                                               required>
                                        <span class="icon is-small is-left">
                                            <i class="fas fa-clock"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="field">
                                    <label class="label">Comando</label>
                                    <div class="control has-icons-left">
                                        <input type="text" 
                                               class="input" 
                                               name="comando" 
                                               placeholder="/usr/bin/php /caminho/script.php"
                                               value="<?= htmlspecialchars($_POST['comando'] ?? '') ?>"
                                               required>
                                        <span class="icon is-small is-left">
                                            <i class="fas fa-terminal"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="field">
                                    <label class="label">Comentário (opcional)</label>
                                    <div class="control has-icons-left">
                                        <input type="text" 
                                               class="input" 
                                               name="comentario" 
                                               placeholder="Descrição da tarefa"
                                               value="<?= htmlspecialchars($_POST['comentario'] ?? '') ?>">
                                        <span class="icon is-small is-left">
                                            <i class="fas fa-comment"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="field">
                                    <div class="control">
                                        <button type="submit" name="adicionar" class="button is-success is-fullwidth">
                                            <i class="fas fa-plus mr-2"></i>
                                            Adicionar Tarefa
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-title">
                                <i class="fas fa-bolt has-text-warning mr-2"></i>
                                Ações Rápidas
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="buttons are-small is-flex-direction-column">
                                <form method="POST" class="is-fullwidth">
                                    <button type="submit" 
                                            name="backup" 
                                            onclick="return confirm('Fazer backup do crontab atual?')"
                                            class="button is-info is-fullwidth">
                                        <i class="fas fa-save mr-2"></i>
                                        Fazer Backup
                                    </button>
                                </form>
                                
                                <form method="POST" class="is-fullwidth">
                                    <button type="submit" 
                                            name="limpar_tudo" 
                                            onclick="return confirm('ATENÇÃO: Isso removerá TODAS as tarefas do crontab. Continuar?')"
                                            class="button is-danger is-fullwidth">
                                        <i class="fas fa-trash mr-2"></i>
                                        Limpar Todas as Tarefas
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Tasks List -->
                <div class="column is-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-title">
                                <i class="fas fa-tasks has-text-primary mr-2"></i>
                                Tarefas Existentes
                            </div>
                            <span class="card-header-icon">
                                <span class="tag is-primary is-medium">
                                    <?= count($tarefas) ?> tarefa(s)
                                </span>
                            </span>
                        </div>
                        <div class="card-content">
                            <?php if (empty($tarefas)): ?>
                                <div class="has-text-centered py-6">
                                    <i class="fas fa-inbox has-text-grey-light fa-3x mb-4"></i>
                                    <p class="title is-5 has-text-grey">Nenhuma tarefa encontrada</p>
                                    <p class="subtitle is-6 has-text-grey">Adicione sua primeira tarefa usando o formulário ao lado</p>
                                </div>
                            <?php else: ?>
                                <div class="content">
                                    <?php foreach ($tarefas as $index => $tarefa): ?>
                                        <div class="card task-card mb-3">
                                            <div class="card-content py-3">
                                                <div class="media">
                                                    <div class="media-left">
                                                        <span class="tag is-light is-small">
                                                            #<?= $index + 1 ?>
                                                        </span>
                                                    </div>
                                                    <div class="media-content">
                                                        <?php if (strpos($tarefa, '#') === 0): ?>
                                                            <p class="comment-line">
                                                                <i class="fas fa-comment has-text-grey-light mr-2"></i>
                                                                <?= htmlspecialchars($tarefa) ?>
                                                            </p>
                                                        <?php else: ?>
                                                            <div class="cron-code">
                                                                <?= htmlspecialchars($tarefa) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="media-right">
                                                        <?php if (strpos($tarefa, '#') !== 0): ?>
                                                            <form method="POST">
                                                                <input type="hidden" name="comando_remover" value="<?= htmlspecialchars($tarefa) ?>">
                                                                <button type="submit" 
                                                                        name="remover" 
                                                                        onclick="return confirm('Tem certeza que deseja remover esta tarefa?')"
                                                                        class="button is-danger is-small"
                                                                        title="Remover Tarefa">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="content has-text-centered">
            <div class="buttons is-centered are-small mb-3">
                <span class="button is-static">
                    <i class="fas fa-code mr-2"></i>
                    Gerenciador Crontab
                </span>
                <span class="button is-static">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Interface Segura
                </span>
            </div>
            <p>
                <strong>Gerenciador de Crontab</strong> &copy; <?= date('Y') ?> - Desenvolvido com PHP e Bulma CSS
            </p>
        </div>
    </footer>

    <!-- JavaScript for Bulma components -->
    <script>
        // Close notifications
        document.addEventListener('DOMContentLoaded', () => {
            // Add delete functionality to notifications
            (document.querySelectorAll('.notification .delete') || []).forEach(($delete) => {
                const $notification = $delete.parentNode;
                $delete.addEventListener('click', () => {
                    $notification.parentNode.removeChild($notification);
                });
            });

            // Auto-remove notifications after 5 seconds
            setTimeout(() => {
                const notifications = document.querySelectorAll('.notification');
                notifications.forEach(notification => {
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>
