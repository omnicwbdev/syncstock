<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Crontab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-calendar-alt text-blue-500 mr-3"></i>
                Gerenciador de Crontab
            </h1>
            <p class="text-gray-600">Gerencie suas tarefas agendadas de forma simples e intuitiva</p>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($_SESSION['mensagem']) ?>
            </div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['erro'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($_SESSION['erro']) ?>
            </div>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>

        <!-- Examples Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
            <div class="flex items-center mb-4">
                <i class="fas fa-lightbulb text-blue-500 text-xl mr-3"></i>
                <h2 class="text-xl font-semibold text-gray-800">Exemplos de Agendamento</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-blue-200">
                            <th class="text-left py-2 font-medium text-gray-700">Padrão</th>
                            <th class="text-left py-2 font-medium text-gray-700">Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exemplos as $pattern => $descricao): ?>
                            <tr class="border-b border-blue-100 hover:bg-blue-25">
                                <td class="py-2 font-mono text-blue-600"><?= htmlspecialchars($pattern) ?></td>
                                <td class="py-2 text-gray-700"><?= htmlspecialchars($descricao) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Add Task & Quick Actions -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Add Task Form -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-plus-circle text-green-500 text-xl mr-3"></i>
                        <h2 class="text-xl font-semibold text-gray-800">Adicionar Nova Tarefa</h2>
                    </div>
                    
                    <form method="POST">
                        <div class="space-y-4">
                            <div>
                                <label for="agendamento" class="block text-sm font-medium text-gray-700 mb-1">
                                    Agendamento (Cron Pattern)
                                </label>
                                <input type="text" id="agendamento" name="agendamento" 
                                       placeholder="* * * * *"
                                       value="<?= htmlspecialchars($_POST['agendamento'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                       required>
                            </div>
                            
                            <div>
                                <label for="comando" class="block text-sm font-medium text-gray-700 mb-1">
                                    Comando
                                </label>
                                <input type="text" id="comando" name="comando" 
                                       placeholder="/usr/bin/php /caminho/para/script.php"
                                       value="<?= htmlspecialchars($_POST['comando'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                       required>
                            </div>
                            
                            <div>
                                <label for="comentario" class="block text-sm font-medium text-gray-700 mb-1">
                                    Comentário (opcional)
                                </label>
                                <input type="text" id="comentario" name="comentario" 
                                       placeholder="Descrição da tarefa"
                                       value="<?= htmlspecialchars($_POST['comentario'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            </div>
                            
                            <button type="submit" name="adicionar"
                                    class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <i class="fas fa-plus mr-2"></i>
                                Adicionar Tarefa
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-bolt text-yellow-500 text-xl mr-3"></i>
                        <h2 class="text-xl font-semibold text-gray-800">Ações Rápidas</h2>
                    </div>
                    
                    <div class="space-y-3">
                        <form method="POST">
                            <button type="submit" name="backup" 
                                    onclick="return confirm('Fazer backup do crontab atual?')"
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <i class="fas fa-save mr-2"></i>
                                Fazer Backup
                            </button>
                        </form>
                        
                        <form method="POST">
                            <button type="submit" name="limpar_tudo" 
                                    onclick="return confirm('ATENÇÃO: Isso removerá TODAS as tarefas do crontab. Continuar?')"
                                    class="w-full bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <i class="fas fa-trash mr-2"></i>
                                Limpar Todas as Tarefas
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Existing Tasks -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-tasks text-purple-500 text-xl mr-3"></i>
                            <h2 class="text-xl font-semibold text-gray-800">Tarefas Existentes</h2>
                        </div>
                        <span class="bg-purple-100 text-purple-800 text-sm font-medium px-3 py-1 rounded-full">
                            <?= count($tarefas) ?> tarefa(s)
                        </span>
                    </div>
                    
                    <?php if (empty($tarefas)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                            <p class="text-gray-500 text-lg">Nenhuma tarefa encontrada no crontab.</p>
                            <p class="text-gray-400 text-sm mt-2">Adicione sua primeira tarefa usando o formulário ao lado.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($tarefas as $index => $tarefa): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition duration-200">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-2">
                                                <span class="bg-gray-100 text-gray-600 text-xs font-mono px-2 py-1 rounded mr-3">
                                                    #<?= $index + 1 ?>
                                                </span>
                                                <?php if (strpos($tarefa, '#') === 0): ?>
                                                    <span class="text-sm text-gray-500 italic">
                                                        <i class="fas fa-comment mr-1"></i>
                                                        <?= htmlspecialchars($tarefa) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <code class="bg-gray-800 text-green-400 px-3 py-1 rounded text-sm font-mono">
                                                        <?= htmlspecialchars($tarefa) ?>
                                                    </code>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (strpos($tarefa, '#') !== 0): ?>
                                            <form method="POST" class="ml-4">
                                                <input type="hidden" name="comando_remover" value="<?= htmlspecialchars($tarefa) ?>">
                                                <button type="submit" name="remover" 
                                                        onclick="return confirm('Tem certeza que deseja remover esta tarefa?')"
                                                        class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg transition duration-200 flex items-center justify-center"
                                                        title="Remover Tarefa">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-12 text-center text-gray-500 text-sm">
            <div class="flex items-center justify-center space-x-6 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-code text-gray-400 mr-2"></i>
                    <span>Gerenciador Crontab</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-gray-400 mr-2"></i>
                    <span>Interface Segura</span>
                </div>
            </div>
            <p>&copy; <?= date('Y') ?> - Desenvolvido com PHP e Tailwind CSS</p>
        </footer>
    </div>

    <!-- Custom Styles -->
    <style>
        .bg-blue-25 {
            background-color: #f0f9ff;
        }
        code {
            word-break: break-all;
        }
        .hover\:bg-blue-25:hover {
            background-color: #f0f9ff;
        }
    </style>
</body>
</html>
