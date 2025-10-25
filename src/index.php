<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CrontabManager\GerenciadorCrontab;
use CrontabManager\Validadores\ValidadorAgendamento;

session_start();

// Processar formulários
if ($_POST) {
    $crontab = new GerenciadorCrontab();
    $validador = new ValidadorAgendamento();

    try {
        if (isset($_POST['adicionar'])) {
            $agendamento = $_POST['agendamento'] ?? '';
            $comando = $_POST['comando'] ?? '';
            $comentario = $_POST['comentario'] ?? '';

            if ($validador->validar($agendamento)) {
                $crontab->adicionarTarefa($agendamento, $comando, $comentario);
                $_SESSION['mensagem'] = 'Tarefa adicionada com sucesso!';
            } else {
                $_SESSION['erro'] = 'Agendamento inválido! Verifique o formato.';
            }
        }

        if (isset($_POST['remover'])) {
            $comandoRemover = $_POST['comando_remover'] ?? '';
            $crontab->removerTarefa($comandoRemover);
            $_SESSION['mensagem'] = 'Tarefa removida com sucesso!';
        }

        if (isset($_POST['backup'])) {
            if ($crontab->fazerBackup()) {
                $_SESSION['mensagem'] = 'Backup realizado com sucesso!';
            } else {
                $_SESSION['erro'] = 'Erro ao fazer backup.';
            }
        }

        if (isset($_POST['limpar_tudo'])) {
            $crontab->limparTodasTarefas();
            $_SESSION['mensagem'] = 'Todas as tarefas foram removidas!';
        }
    } catch (Exception $e) {
        $_SESSION['erro'] = 'Erro: ' . $e->getMessage();
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Carregar dados para a view
$crontab = new GerenciadorCrontab();
$validador = new ValidadorAgendamento();

$tarefas = $crontab->listarTarefas();
$exemplos = $validador->getExemplos();

// Incluir template
include __DIR__ . '/../templates/interface_crontab.php';
