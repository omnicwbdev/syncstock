<?php

namespace CrontabManager\Validadores;

class ValidadorAgendamento
{
    public function validar(string $agendamento): bool
    {
        $partes = explode(' ', trim($agendamento));

        if (count($partes) !== 5) {
            return false;
        }

        list($minuto, $hora, $dia, $mes, $diaSemana) = $partes;

        $validacoes = [
            'minuto' => $this->validarCampo($minuto, 0, 59),
            'hora' => $this->validarCampo($hora, 0, 23),
            'dia' => $this->validarCampo($dia, 1, 31),
            'mes' => $this->validarCampo($mes, 1, 12),
            'diaSemana' => $this->validarCampo($diaSemana, 0, 7)
        ];

        return !in_array(false, $validacoes, true);
    }

    private function validarCampo(string $valor, int $min, int $max): bool
    {
        if ($valor === '*') {
            return true;
        }

        // Verificar intervalos (ex: 1-5)
        if (strpos($valor, '-') !== false) {
            $partes = explode('-', $valor);
            if (count($partes) !== 2) {
                return false;
            }
            return $this->validarCampo($partes[0], $min, $max) &&
                   $this->validarCampo($partes[1], $min, $max);
        }

        // Verificar steps (ex: */5)
        if (strpos($valor, '/') !== false) {
            $partes = explode('/', $valor);
            if (count($partes) !== 2 || $partes[0] !== '*') {
                return false;
            }
            return is_numeric($partes[1]) && $partes[1] >= $min && $partes[1] <= $max;
        }

        // Verificar lista (ex: 1,3,5)
        if (strpos($valor, ',') !== false) {
            $numeros = explode(',', $valor);
            foreach ($numeros as $numero) {
                if (!is_numeric($numero) || $numero < $min || $numero > $max) {
                    return false;
                }
            }
            return true;
        }

        // Verificar número simples
        return is_numeric($valor) && $valor >= $min && $valor <= $max;
    }

    public function getExemplos(): array
    {
        return [
            '* * * * *' => 'A cada minuto',
            '0 * * * *' => 'A cada hora',
            '0 2 * * *' => 'Diariamente às 2:00 AM',
            '0 2 * * 0' => 'Aos domingos às 2:00 AM',
            '0 2 1 * *' => 'No primeiro dia do mês às 2:00 AM',
            '*/5 * * * *' => 'A cada 5 minutos',
            '0 9-17 * * *' => 'A cada hora das 9:00 às 17:00',
            '0 2 1,15 * *' => 'Nos dias 1 e 15 de cada mês às 2:00 AM'
        ];
    }
}
