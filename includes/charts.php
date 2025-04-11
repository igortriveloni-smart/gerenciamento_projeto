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
        $etapasConcluidas = count(array_filter($this->etapas, function($etapa) {
            return $etapa['status'] === 'Concluído';
        }));

        $totalTarefas = count($this->tarefas);
        $tarefasConcluidas = count(array_filter($this->tarefas, function($tarefa) {
            return $tarefa['status'] === 'Concluído';
        }));

        return [
            'etapas' => ['percentual' => $totalEtapas > 0 ? ($etapasConcluidas / $totalEtapas) * 100 : 0],
            'tarefas' => ['percentual' => $totalTarefas > 0 ? ($tarefasConcluidas / $totalTarefas) * 100 : 0]
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