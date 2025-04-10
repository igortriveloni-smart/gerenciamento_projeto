<?php
class Pagination {
    private $totalItems;
    private $itemsPerPage;
    private $currentPage;
    private $totalPages;
    private $pageParam;
    
    public function __construct($totalItems, $itemsPerPage = 10, $pageParam = 'page') {
        $this->totalItems = (int)$totalItems;
        $this->itemsPerPage = (int)$itemsPerPage;
        $this->pageParam = $pageParam;
        $this->currentPage = isset($_GET[$this->pageParam]) ? max(1, (int)$_GET[$this->pageParam]) : 1;
        $this->totalPages = ceil($this->totalItems / $this->itemsPerPage);
    }
    
    public function getOffset() {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }
    
    public function getLimit() {
        return $this->itemsPerPage;
    }
    
    public function render($url) {
        if ($this->totalPages <= 1) return '';
        
        // Remove a âncora da URL base para a paginação
        $baseUrl = preg_replace('/#.*$/', '', $url);
        
        // Verifica se a URL já tem parâmetros
        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';
        
        $html = '<nav aria-label="Navegação de páginas"><ul class="pagination justify-content-center">';
        
        // Botão anterior
        if ($this->currentPage > 1) {
            $html .= sprintf(
                '<li class="page-item"><a class="page-link" href="%s%s%s=%d%s">Anterior</a></li>',
                $baseUrl,
                $separator,
                $this->pageParam,
                $this->currentPage - 1,
                strpos($url, '#') !== false ? substr($url, strpos($url, '#')) : ''
            );
        }
        
        // Números das páginas
        for ($i = 1; $i <= $this->totalPages; $i++) {
            if ($i == $this->currentPage) {
                $html .= sprintf(
                    '<li class="page-item active"><span class="page-link">%d</span></li>',
                    $i
                );
            } else {
                $html .= sprintf(
                    '<li class="page-item"><a class="page-link" href="%s%s%s=%d%s">%d</a></li>',
                    $baseUrl,
                    $separator,
                    $this->pageParam,
                    $i,
                    strpos($url, '#') !== false ? substr($url, strpos($url, '#')) : '',
                    $i
                );
            }
        }
        
        // Botão próximo
        if ($this->currentPage < $this->totalPages) {
            $html .= sprintf(
                '<li class="page-item"><a class="page-link" href="%s%s%s=%d%s">Próximo</a></li>',
                $baseUrl,
                $separator,
                $this->pageParam,
                $this->currentPage + 1,
                strpos($url, '#') !== false ? substr($url, strpos($url, '#')) : ''
            );
        }
        
        $html .= '</ul></nav>';
        return $html;
    }
} 