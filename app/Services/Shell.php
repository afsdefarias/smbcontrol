<?php
namespace App\Services;

class Shell {
    /**
     * Executa um comando via sudo e retorna o status e saída.
     */
    public static function execSudo(string $command): array {
        $output = [];
        $return_var = 0;
        
        // Log para auditoria na tabela poderia ser inserido aqui
        
        exec("sudo $command 2>&1", $output, $return_var);
        
        return [
            'success' => $return_var === 0,
            'output' => implode("\n", $output),
            'code' => $return_var
        ];
    }
}
