<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Crontab</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .tarefa-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        .comando {
            font-family: monospace;
            background: #e9ecef;
            padding: 5px;
            border-radius: 3px;
        }
        .exemplos {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .exemplos table {
            width: 100%;
            border-collapse: collapse;
        }
        .exemplos td {
            padding: 5px;
            border-bottom: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📅 Gerenciador de Crontab</h1>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['mensagem']) ?>
            </div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['erro'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['erro']) ?>
            </div>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>
        
        <div class="exemplos">
            <h3>📋 Exemplos de Agendamento</h3>
            <table>
                <?php foreach ($exemplos as $pattern => $descricao): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pattern) ?></strong></td>
                        <td><?= htmlspecialchars($descricao) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="form-section">
            <h2>➕ Adicionar Nova Tarefa</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="agendamento">Agendamento (Cron Pattern):</label>
                    <input type="text" id="agendamento" name="agendamento" 
                           placeholder="* * * * *" value="<?= htmlspecialchars($_POST['agendamento'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="comando">Comando:</label>
                    <input type="text" id="comando" name="comando" 
                           placeholder="/usr/bin/php /caminho/para/script.php" 
                           value="<?= htmlspecialchars($_POST['comando'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="comentario">Comentário (opcional):</label>
                    <input type="text" id="comentario" name="comentario" 
                           placeholder="Descrição da tarefa"
                           value="<?= htmlspecialchars($_POST['comentario'] ?? '') ?>">
                </div>
                
                <button type="submit" name="adicionar">Adicionar Tarefa</button>
            </form>
        </div>
        
        <div class="actions-section">
            <h2>⚙️ Ações Rápidas</h2>
            <form method="POST" style="display: inline;">
                <button type="submit" name="backup" onclick="return confirm('Fazer backup do crontab atual?')">
                    💾 Fazer Backup
                </button>
            </form>
            
            <form method="POST" style="display: inline; margin-left: 10px;">
                <button type="submit" name="limpar_tudo" 
                        onclick="return confirm('ATENÇÃO: Isso removerá TODAS as tarefas do crontab. Continuar?')"
                        style="background-color: #dc3545;">
                    🗑️ Limpar Todas as Tarefas
                </button>
            </form>
        </div>
        
        <div class="tarefas-section">
            <h2>📝 Tarefas Existentes</h2>
            
            <?php if (empty($tarefas)): ?>
                <p>Nenhuma tarefa encontrada no crontab.</p>
            <?php else: ?>
                <?php foreach ($tarefas as $tarefa): ?>
                    <div class="tarefa-item">
                        <div class="comando"><?= htmlspecialchars($tarefa) ?></div>
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="comando_remover" value="<?= htmlspecialchars($tarefa) ?>">
                            <button type="submit" name="remover" 
                                    onclick="return confirm('Remover esta tarefa?')"
                                    style="background-color: #dc3545; padding: 5px 10px; font-size: 12px;">
                                Remover
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
