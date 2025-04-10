<?php
class ProjectCharts {
    private $projeto;
    private $etapas;
    private $tarefas;
    
    public function __construct($projeto, $etapas, $tarefas) {
        $this->projeto = $projeto;
        $this->etapas = $etapas;
        $this->tarefas = $tarefas;
    }
    
    public function getProgressData() {
        $totalEtapas = count($this->etapas);
        $etapasConcluidas = 0;
        $totalTarefas = count($this->tarefas);
        $tarefasConcluidas = 0;
        
        foreach ($this->etapas as $etapa) {
            if ($etapa['status'] == 'Concluída') {
                $etapasConcluidas++;
            }
        }
        
        foreach ($this->tarefas as $tarefa) {
            if ($tarefa['status'] == 'Concluído') {
                $tarefasConcluidas++;
            }
        }
        
        return [
            'etapas' => [
                'total' => $totalEtapas,
                'concluidas' => $etapasConcluidas,
                'percentual' => $totalEtapas > 0 ? round(($etapasConcluidas / $totalEtapas) * 100) : 0
            ],
            'tarefas' => [
                'total' => $totalTarefas,
                'concluidas' => $tarefasConcluidas,
                'percentual' => $totalTarefas > 0 ? round(($tarefasConcluidas / $totalTarefas) * 100) : 0
            ]
        ];
    }
    
    public function getStatusDistribution() {
        $statusTarefas = [
            'Concluído' => 0,
            'Em Andamento' => 0,
            'Atrasado' => 0,
            'Pendente' => 0,
            'Cancelado' => 0,
            'Aguardando' => 0,    
            'Não Iniciado' => 0   
        ];
        
        foreach ($this->tarefas as $tarefa) {
            $statusTarefas[$tarefa['status']]++;
        }
        
        return $statusTarefas;
    }
} 