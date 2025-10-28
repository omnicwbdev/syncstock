<!DOCTYPE html>
<html lang="pt-BR" data-theme="corporate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Crontab</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cron-pattern {
            min-width: 110px;
            font-family: 'Courier New', monospace;
            font-size: 0.7rem;
        }
        .task-code {
            word-break: break-all;
            overflow-wrap: break-word;
            font-size: 0.75rem;
        }
        .fade-out {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .compact-table td {
            padding: 0.2rem 0.4rem;
        }
    </style>
</head>
<body class="min-h-screen bg-base-100">
    <!-- Header -->
    <div class="bg-base-200 border-b py-3">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <h1 class="text-xl font-bold text-base-content">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Gerenciador de Crontab
                </h1>
                <p class="text-xs text-base-content/70 mt-1">Sistema de agendamento de tarefas</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-3">
        <!-- Alert Messages -->
        <div id="alerts-container" class="max-w-6xl mx-auto space-y-2 mb-3">
            <?php if (isset($_SESSION['mensagem'])): ?>
                <div class="alert alert-success shadow-xs rounded-lg py-2 px-3" data-auto-dismiss="3000">
                    <i class="fas fa-check-circle text-xs"></i>
                    <span class="text-xs"><?= htmlspecialchars($_SESSION['mensagem']) ?></span>
                    <button class="btn btn-ghost btn-xs alert-close p-0 min-h-0 h-auto">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <?php unset($_SESSION['mensagem']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-error shadow-xs rounded-lg py-2 px-3" data-auto-dismiss="3000">
                    <i class="fas fa-exclamation-triangle text-xs"></i>
                    <span class="text-xs"><?= htmlspecialchars($_SESSION['erro']) ?></span>
                    <button class="btn btn-ghost btn-xs alert-close p-0 min-h-0 h-auto">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>
        </div>

        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">
                <!-- Left Column -->
                <div class="xl:col-span-1 space-y-3">
                    <!-- Examples Card -->
                    <div class="card bg-base-100 shadow-xs border rounded-lg">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-lightbulb text-primary text-sm"></i>
                                <h2 class="font-semibold text-sm">Exemplos</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="table table-xs compact-table">
                                    <tbody>
                                        <?php foreach ($exemplos as $pattern => $descricao): ?>
                                            <tr>
                                                <td class="cron-pattern">
                                                    <code class="bg-base-200 px-1 py-0.5 rounded text-xs">
                                                        <?= htmlspecialchars($pattern) ?>
                                                    </code>
                                                </td>
                                                <td class="text-xs leading-tight"><?= htmlspecialchars($descricao) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Add Task Form -->
                    <div class="card bg-base-100 shadow-xs border rounded-lg">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-plus-circle text-success text-sm"></i>
                                <h2 class="font-semibold text-sm">Nova Tarefa</h2>
                            </div>
                            
                            <form method="POST" id="task-form">
                                <div class="space-y-2">
                                    <div class="form-control">
                                        <label class="label py-0">
                                            <span class="label-text text-xs">Agendamento</span>
                                        </label>
                                        <input type="text" 
                                               class="input input-bordered input-sm w-full text-xs" 
                                               name="agendamento" 
                                               placeholder="* * * * *"
                                               value="<?= htmlspecialchars($_POST['agendamento'] ?? '') ?>"
                                               required>
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-0">
                                            <span class="label-text text-xs">Comando</span>
                                        </label>
                                        <input type="text" 
                                               class="input input-bordered input-sm w-full text-xs" 
                                               name="comando" 
                                               placeholder="/usr/bin/php /script.php"
                                               value="<?= htmlspecialchars($_POST['comando'] ?? '') ?>"
                                               required>
                                    </div>

                                    <div class="form-control">
                                        <label class="label py-0">
                                            <span class="label-text text-xs">Comentário (opcional)</span>
                                        </label>
                                        <input type="text" 
                                               class="input input-bordered input-sm w-full text-xs" 
                                               name="comentario" 
                                               placeholder="Descrição"
                                               value="<?= htmlspecialchars($_POST['comentario'] ?? '') ?>">
                                    </div>

                                    <button type="submit" name="adicionar" class="btn btn-success btn-sm w-full text-xs mt-1">
                                        <i class="fas fa-plus mr-1"></i>
                                        Adicionar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card bg-base-100 shadow-xs border rounded-lg">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-bolt text-warning text-sm"></i>
                                <h2 class="font-semibold text-sm">Ações</h2>
                            </div>
                            
                            <div class="space-y-1">
                                <form method="POST" class="quick-action-form">
                                    <button type="submit" 
                                            name="backup" 
                                            class="btn btn-info btn-sm w-full text-xs confirm-action"
                                            data-message="Fazer backup do crontab atual?">
                                        <i class="fas fa-save mr-1"></i>
                                        Backup
                                    </button>
                                </form>
                                
                                <form method="POST" class="quick-action-form">
                                    <button type="submit" 
                                            name="limpar_tudo" 
                                            class="btn btn-error btn-sm w-full text-xs confirm-action"
                                            data-message="ATENÇÃO: Isso removerá TODAS as tarefas. Continuar?">
                                        <i class="fas fa-trash mr-1"></i>
                                        Limpar Tudo
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Tasks List -->
                <div class="xl:col-span-2">
                    <div class="card bg-base-100 shadow-xs border rounded-lg">
                        <div class="card-body p-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-tasks text-primary text-sm"></i>
                                    <h2 class="font-semibold text-sm">Tarefas Existentes</h2>
                                </div>
                                <div class="flex gap-1">
                                    <span class="badge badge-primary badge-xs">
                                        <?= count($tarefasComComentarios) ?> tarefas
                                    </span>
                                    <?php if (count($comentariosSoltos) > 0): ?>
                                        <span class="badge badge-outline badge-xs">
                                            <?= count($comentariosSoltos) ?> comentários
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Comentários Soltos -->
                            <?php if (count($comentariosSoltos) > 0): ?>
                                <div class="mb-3">
                                    <h3 class="text-xs font-medium mb-1 text-base-content/70 uppercase tracking-wide">
                                        Comentários Gerais
                                    </h3>
                                    <div class="space-y-1">
                                        <?php foreach ($comentariosSoltos as $comentario): ?>
                                            <div class="bg-base-200 rounded px-2 py-1 border-l-2 border-warning">
                                                <div class="text-base-content/60 italic text-xs">
                                                    <?= htmlspecialchars($comentario) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Tarefas Agendadas -->
                            <div id="tasks-container">
                                <?php if (empty($tarefasComComentarios)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox text-base-content/30 text-2xl mb-2"></i>
                                        <p class="text-xs text-base-content/50">Nenhuma tarefa agendada</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-2">
                                        <?php foreach ($tarefasComComentarios as $index => $item): ?>
                                            <div class="bg-base-50 border rounded p-2">
                                                <!-- Comentário -->
                                                <?php if ($item['comentario']): ?>
                                                    <div class="mb-1 bg-base-200 rounded px-2 py-1 border-l-2 border-primary">
                                                        <div class="text-base-content/60 italic text-xs">
                                                            <?= htmlspecialchars($item['comentario']) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Tarefa -->
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="flex items-start gap-2 flex-1 min-w-0">
                                                        <span class="text-xs text-base-content/40 font-mono bg-base-200 px-1 rounded">
                                                            #<?= $index + 1 ?>
                                                        </span>
                                                        <code class="bg-base-200 text-base-content font-mono p-1 rounded text-xs flex-1 task-code">
                                                            <?= htmlspecialchars($item['tarefa']) ?>
                                                        </code>
                                                    </div>
                                                    
                                                    <form method="POST" class="delete-task-form flex-shrink-0">
                                                        <input type="hidden" name="comando_remover" value="<?= htmlspecialchars($item['tarefa']) ?>">
                                                        <button type="submit" 
                                                                name="remover" 
                                                                class="btn btn-error btn-xs confirm-action"
                                                                data-message="Remover esta tarefa<?= $item['comentario'] ? ' e comentário' : '' ?>?">
                                                            <i class="fas fa-times text-xs"></i>
                                                        </button>
                                                    </form>
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
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-base-200 border-t py-3 mt-4">
        <div class="container mx-auto px-4">
            <div class="text-center text-xs text-base-content/50">
                <p>Sistema de Gerenciamento Crontab &copy; <?= date('Y') ?></p>
            </div>
        </div>
    </footer>

    <!-- Vanilla JavaScript -->
    <script>
        class CrontabManager {
            constructor() {
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.autoDismissAlerts();
            }

            setupEventListeners() {
                // Alert close buttons
                document.querySelectorAll('.alert-close').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        this.closeAlert(e.target.closest('.alert'));
                    });
                });

                // Confirmation for destructive actions
                document.querySelectorAll('.confirm-action').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const message = e.target.dataset.message;
                        if (!confirm(message)) {
                            e.preventDefault();
                        }
                    });
                });

                // Form validation
                this.enhanceForms();
            }

            enhanceForms() {
                const taskForm = document.getElementById('task-form');
                if (taskForm) {
                    taskForm.addEventListener('submit', (e) => {
                        const agendamento = taskForm.querySelector('[name="agendamento"]').value;
                        if (!this.validateCronPattern(agendamento)) {
                            e.preventDefault();
                            this.showAlert('Padrão cron inválido', 'error');
                        }
                    });
                }
            }

            validateCronPattern(pattern) {
                const parts = pattern.trim().split(/\s+/);
                if (parts.length !== 5) return false;
                
                const validators = [
                    (val) => val === '*' || this.isValidCronField(val, 0, 59),
                    (val) => val === '*' || this.isValidCronField(val, 0, 23),
                    (val) => val === '*' || this.isValidCronField(val, 1, 31),
                    (val) => val === '*' || this.isValidCronField(val, 1, 12),
                    (val) => val === '*' || this.isValidCronField(val, 0, 7)
                ];

                return parts.every((part, index) => validators[index](part));
            }

            isValidCronField(value, min, max) {
                if (value === '*') return true;
                if (/^\d+$/.test(value)) {
                    const num = parseInt(value);
                    return num >= min && num <= max;
                }
                if (value.includes(',')) {
                    return value.split(',').every(item => this.isValidCronField(item, min, max));
                }
                if (value.includes('-')) {
                    const [start, end] = value.split('-');
                    return this.isValidCronField(start, min, max) && this.isValidCronField(end, min, max);
                }
                if (value.includes('/')) {
                    const [range, step] = value.split('/');
                    return range === '*' && this.isValidCronField(step, min, max);
                }
                return false;
            }

            closeAlert(alertElement) {
                if (alertElement) {
                    alertElement.classList.add('fade-out');
                    setTimeout(() => {
                        alertElement?.remove();
                    }, 200);
                }
            }

            autoDismissAlerts() {
                document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
                    setTimeout(() => {
                        this.closeAlert(alert);
                    }, 3000);
                });
            }

            showAlert(message, type = 'info') {
                const alertClass = {
                    'success': 'alert-success',
                    'error': 'alert-error',
                    'warning': 'alert-warning',
                    'info': 'alert-info'
                }[type] || 'alert-info';

                const alertHtml = `
                    <div class="alert ${alertClass} shadow-xs rounded-lg py-2 px-3" data-auto-dismiss="3000">
                        <i class="fas fa-${this.getAlertIcon(type)} text-xs"></i>
                        <span class="text-xs">${message}</span>
                        <button class="btn btn-ghost btn-xs alert-close p-0 min-h-0 h-auto">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                `;

                document.getElementById('alerts-container').insertAdjacentHTML('afterbegin', alertHtml);
                this.autoDismissAlerts();
            }

            getAlertIcon(type) {
                const icons = {
                    'success': 'check-circle',
                    'error': 'exclamation-triangle',
                    'warning': 'exclamation-circle',
                    'info': 'info-circle'
                };
                return icons[type] || 'info-circle';
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            new CrontabManager();
        });
    </script>
</body>
</html>
