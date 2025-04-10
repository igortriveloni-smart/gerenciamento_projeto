<?php
class Cache {
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }
    
    public function get($key) {
        $filename = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($filename) && (time() - filemtime($filename) < 3600)) { // Cache por 1 hora
            return unserialize(file_get_contents($filename));
        }
        return false;
    }
    
    public function set($key, $data) {
        $filename = $this->cacheDir . md5($key) . '.cache';
        file_put_contents($filename, serialize($data));
    }
    
    public function delete($key) {
        $filename = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
} 