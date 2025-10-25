<?php

namespace CrontabManager;

class GerenciadorCrontab
{
    private string $crontabFile = '/tmp/crontab_temp';

    public function listarTarefas(): array
    {
        $output = shell_exec('crontab -l 2>/dev/null');
        if (empty($output)) {
            return [];
        }

        $linhas = explode(PHP_EOL, trim($output));

        // Filtrar apenas linhas que são tarefas agendadas (não comentários)
        return array_filter($linhas, function ($linha) {
            $linha = trim($linha);
            // Ignorar linhas vazias e comentários
            if (empty($linha) || strpos($linha, '#') === 0) {
                return false;
            }

            // Verificar se é uma linha válida de agendamento (tem pelo menos 5 partes)
            $partes = preg_split('/\s+/', $linha);
            return count($partes) >= 5;
        });
    }

    public function listarComentarios(): array
    {
        $output = shell_exec('crontab -l 2>/dev/null');
        if (empty($output)) {
            return [];
        }

        $linhas = explode(PHP_EOL, trim($output));

        // Filtrar apenas comentários
        return array_filter($linhas, function ($linha) {
            $linha = trim($linha);
            return !empty($linha) && strpos($linha, '#') === 0;
        });
    }

    public function listarTarefasComComentarios(): array
    {
        $output = shell_exec('crontab -l 2>/dev/null');
        if (empty($output)) {
            return [];
        }

        $linhas = explode(PHP_EOL, trim($output));
        $resultado = [];
        $comentarioAnterior = null;

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if (empty($linha)) {
                $comentarioAnterior = null;
                continue;
            }

            // Se é um comentário, armazena para a próxima tarefa
            if (strpos($linha, '#') === 0) {
                $comentarioAnterior = $linha;
            }
            // Se é uma tarefa, adiciona com seu comentário (se houver)
            elseif (count(preg_split('/\s+/', $linha)) >= 5) {
                $resultado[] = [
                    'tarefa' => $linha,
                    'comentario' => $comentarioAnterior
                ];
                $comentarioAnterior = null; // Reseta após usar
            } else {
                // Linha que não é comentário nem tarefa válida
                $comentarioAnterior = null;
            }
        }

        return $resultado;
    }

    public function adicionarTarefa(string $agendamento, string $comando, string $comentario = ''): bool
    {
        $validador = new Validadores\ValidadorAgendamento();
        if (!$validador->validar($agendamento)) {
            throw new \InvalidArgumentException("Agendamento inválido: $agendamento");
        }

        $tarefas = $this->listarTodasAsLinhas();

        // Adicionar comentário se fornecido
        if (!empty($comentario)) {
            $tarefas[] = "# $comentario";
        }

        $tarefas[] = "$agendamento $comando";

        return $this->salvarTarefas($tarefas);
    }

    public function removerTarefa(string $comandoBusca): bool
    {
        $tarefasComComentarios = $this->listarTarefasComComentarios();
        $todasAsLinhas = $this->listarTodasAsLinhas();
        $linhasParaManter = [];

        $encontrouTarefa = false;
        $comentarioDaTarefa = null;

        // Identificar a tarefa e seu comentário
        foreach ($tarefasComComentarios as $item) {
            if (strpos($item['tarefa'], $comandoBusca) !== false) {
                $encontrouTarefa = true;
                $comentarioDaTarefa = $item['comentario'];
                break;
            }
        }

        // Se não encontrou a tarefa, retorna false
        if (!$encontrouTarefa) {
            return false;
        }

        // Construir nova lista excluindo a tarefa e seu comentário
        foreach ($todasAsLinhas as $linha) {
            $linhaTrim = trim($linha);

            // Pular a linha se for a tarefa a ser removida
            if (strpos($linhaTrim, $comandoBusca) !== false &&
                count(preg_split('/\s+/', $linhaTrim)) >= 5) {
                continue;
            }

            // Pular a linha se for o comentário associado à tarefa
            if ($comentarioDaTarefa && $linhaTrim === $comentarioDaTarefa) {
                continue;
            }

            $linhasParaManter[] = $linha;
        }

        return $this->salvarTarefas($linhasParaManter);
    }

    public function removerTarefaPorIndice(int $indice): bool
    {
        $tarefasComComentarios = $this->listarTarefasComComentarios();

        if (!isset($tarefasComComentarios[$indice])) {
            return false;
        }

        $tarefaParaRemover = $tarefasComComentarios[$indice]['tarefa'];

        return $this->removerTarefa($tarefaParaRemover);
    }

    public function listarTodasAsLinhas(): array
    {
        $output = shell_exec('crontab -l 2>/dev/null');
        if (empty($output)) {
            return [];
        }

        return explode(PHP_EOL, trim($output));
    }

    public function limparTodasTarefas(): bool
    {
        // Manter apenas comentários que não estão associados a tarefas
        $todasAsLinhas = $this->listarTodasAsLinhas();
        $tarefasComComentarios = $this->listarTarefasComComentarios();

        // Coletar todos os comentários que estão associados a tarefas
        $comentariosDeTarefas = [];
        foreach ($tarefasComComentarios as $item) {
            if ($item['comentario']) {
                $comentariosDeTarefas[] = $item['comentario'];
            }
        }

        // Manter apenas linhas que são comentários não associados a tarefas
        $linhasParaManter = [];
        foreach ($todasAsLinhas as $linha) {
            $linhaTrim = trim($linha);

            // Manter apenas comentários que não estão na lista de comentários de tarefas
            if (!empty($linhaTrim) &&
                strpos($linhaTrim, '#') === 0 &&
                !in_array($linhaTrim, $comentariosDeTarefas)) {
                $linhasParaManter[] = $linha;
            }
        }

        return $this->salvarTarefas($linhasParaManter);
    }

    private function salvarTarefas(array $tarefas): bool
    {
        $conteudo = implode(PHP_EOL, $tarefas);
        if (!empty($conteudo)) {
            $conteudo .= PHP_EOL;
        }

        file_put_contents($this->crontabFile, $conteudo);

        $resultado = shell_exec("crontab {$this->crontabFile} 2>&1");

        if (file_exists($this->crontabFile)) {
            unlink($this->crontabFile);
        }

        return $resultado === null;
    }

    public function fazerBackup(string $diretorioBackup = '/tmp/backups_crontab'): bool
    {
        if (!is_dir($diretorioBackup)) {
            mkdir($diretorioBackup, 0755, true);
        }

        $backup = shell_exec('crontab -l 2>/dev/null');
        $arquivoBackup = $diretorioBackup . '/crontab_backup_' . date('Y-m-d_H-i-s') . '.txt';

        return file_put_contents($arquivoBackup, $backup) !== false;
    }

    public function getEstatisticas(): array
    {
        $todasLinhas = $this->listarTodasAsLinhas();
        $tarefas = $this->listarTarefas();
        $comentarios = $this->listarComentarios();
        $tarefasComComentarios = $this->listarTarefasComComentarios();

        $comentariosAssociados = 0;
        foreach ($tarefasComComentarios as $item) {
            if ($item['comentario']) {
                $comentariosAssociados++;
            }
        }

        return [
            'total_linhas' => count($todasLinhas),
            'total_tarefas' => count($tarefas),
            'total_comentarios' => count($comentarios),
            'comentarios_associados' => $comentariosAssociados,
            'comentarios_soltos' => count($comentarios) - $comentariosAssociados,
            'linhas_vazias' => count(array_filter($todasLinhas, function ($linha) {
                return empty(trim($linha));
            }))
        ];
    }
}
