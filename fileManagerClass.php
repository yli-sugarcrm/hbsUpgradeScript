<?php

class fileManager {
    public function readFile($path) {
        $content = file_get_contents($path);
        print_r($content);
    }

    public function readFileByLines($path, $list = false) {
        $content = array();
        $file = fopen($path, 'r');
        if ($list) { echo '<br/>'; }
        while (($line = fgets($file)) !== false) {
            if ($list) { print_r(htmlspecialchars($line) . '<br/>');}
            array_push($content, $line);
        }
        if ($list) { echo '<br/>'; }
        fclose($file);
        return $content;
    }

    public function write($path, $content, $extranewline = false) {
        $this->_ensureExistingFolder($path);
        $file = fopen($path, 'w');
        $this->_writeContent($file, $content, $extranewline);
        fclose($file);
    }

    private function _ensureExistingFolder($path) {
        $folderPathEnd = strrpos($path, '/');
        if ($folderPathEnd) {
            $folderPath = substr($path, 0, $folderPathEnd);
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0777);
            }
        }
    }

    private function _writeContent($file, $content, $extranewline) {
        $extra = $extranewline ? "\n" : '';
        forEach($content as $c) {
            fwrite($file, $c . $extra);
        }
    }
}

?>