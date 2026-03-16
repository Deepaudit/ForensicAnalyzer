<?php
/**
 * Project: Deep Audit / Deep Forensisc Oficial
 * Author: PROF Pablo Cyber
 * Focus: Automated Log Analysis & Threat Detection
 */

namespace DeepAudit;

class ForensicAnalyzer {
    private $logFile;
    private $threats = [];
    private $suspiciousPorts = [4444, 445, 139, 3389, 8888]; // Portas comuns de RATs/Exploits

    public function __construct($filename) {
        if (!file_exists($filename)) {
            throw new \Exception("Erro: Arquivo de log '$filename' não encontrado.");
        }
        $this->logFile = $filename;
    }

    /**
     * Analisa o arquivo em busca de padrões suspeitos
     */
    public function analyze() {
        $handle = fopen($this->logFile, "r");
        while (($line = fgets($handle)) !== false) {
            
            // 1. Detecta Conexões Estabelecidas
            if (strpos($line, 'ESTABLISHED') !== false) {
                $this->extractNetworkData($line);
            }

            // 2. Detecta tentativas de Brute Force ou SQLi simples (se for log web)
            if (preg_match('/union\s+select|admin\'--/i', $line)) {
                $this->threats[] = "[CRÍTICO] Tentativa de Injeção detectada: " . trim($line);
            }
        }
        fclose($handle);
    }

    private function extractNetworkData($line) {
        // Regex para capturar IP e Porta
        if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}):(\d+)/', $line, $matches)) {
            $ip = $matches[1];
            $port = (int)$matches[2];

            if (in_array($port, $this->suspiciousPorts)) {
                $this->threats[] = "[ALERTA] Conexão ativa em porta sensível ($port) detectada para o IP: $ip";
            }
        }
    }

    /**
     * Gera o relatório final
     */
    public function generateReport() {
        echo "====================================================\n";
        echo "   RELATÓRIO DE ANÁLISE FORENSE - DEEP AUDIT\n";
        echo "   Data: " . date('d/m/Y H:i:s') . "\n";
        echo "====================================================\n\n";

        if (empty($this->threats)) {
            echo "[+] Nenhum indicador de comprometimento (IoC) encontrado.\n";
        } else {
            echo "[-] Ameaças Identificadas:\n";
            foreach ($this->threats as $threat) {
                echo " $threat\n";
            }
        }
        echo "\n====================================================\n";
    }
}

// --- EXECUÇÃO ---
try {
    // Exemplo de uso: passando o log gerado pelo seu script Bash ou logs do Apache
    $analyzer = new ForensicAnalyzer("system_snapshot.txt");
    $analyzer->analyze();
    $analyzer->generateReport();
} catch (\Exception $e) {
    echo $e->getMessage();
}
