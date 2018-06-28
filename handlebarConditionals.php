<?php
include './fileManagerClass.php';

class rootFixer {
    private $ifblocks, $unlessblocks;

    public function run($hbs) {
        $this->ifblocks = 0;
        $this->unlessblocks = 0;

        $newContent = array();
        $manager = new fileManager();
        $oldContent = $manager->readFileByLines($hbs->path);

        forEach($oldContent as $line) {
            $ewLine = $this->_getNewLine($line);
            array_push($newContent, $ewLine);
        }

        $manager->write($hbs->path, $newContent);
    }

    private function _getNewLine($line) {
        $start = 0;
        $newLine = '';
        $tagLimits = $this->_getTagLimits($line);

        forEach($tagLimits as $tagPosition => $tagName) {
            $end = (int)$tagPosition;
            $lineBit = substr($line, $start, $end - $start);
            $start = $end;
            $newLine = $newLine . $this->_replaceDotDotSlash($lineBit);
            $this->_countTags($tagName);
        }

        $end = strlen($line);
        $lineBit = substr($line, $start, $end - $start);

        return $newLine . $this->_replaceDotDotSlash($lineBit);
    }

    private function _countTags($tagName) {
        switch($tagName) {
            case '#if': $this->ifblocks++; break;
            case '/if': $this->ifblocks--; break;
            case '#unless': $this->unlessblocks++; break;
            case '/unless': $this->unlessblocks--; break;
            default: die();
        }
    }

    private function _getTagLimits($line) {
        $startOfIfs = $this->_findAllTagsInLine($line, '#if');
        $endOfIfs = $this->_findAllTagsInLine($line, '/if');
        $startOfUnless = $this->_findAllTagsInLine($line, '#unless');
        $endOfUnless = $this->_findAllTagsInLine($line, '/unless');
        $allLimits = array_merge($startOfIfs, $endOfIfs, $startOfUnless, $endOfUnless);
        ksort($allLimits);
        return $allLimits;
    }

    private function _findAllTagsInLine($line, $tag) {
        $results = new StdClass();

        $tempLine = $line;
        $tagPositionInTheLine = 0;
        $tagPosition = strpos($tempLine, $tag);

        while($tagPosition) {
            $tagPositionInTheLine += $tagPosition;
            $results->$tagPositionInTheLine = $tag;
            $tempLine = substr($tempLine, $tagPosition + strlen($tag));
            $tagPosition = strpos($tempLine, $tag);
        }

        return (array)$results;
    }
    
    private function _replaceDotDotSlash($lineBit) {
        $buffer = '';
        $replacedLineBit = '';
        $replacementsCount = 0;
        $tagDepth = $this->ifblocks + $this->unlessblocks;

        for ($i = 0; $i < strlen($lineBit); $i++) {
            if ($lineBit[$i] === '.' && $buffer !== '.') {
                $replacedLineBit = $replacedLineBit . $buffer;
                $buffer = '';
            }
    
            $buffer = $buffer . $lineBit[$i];
    
            if (strlen($buffer) === 3 && $buffer == '../') {
                if ($replacementsCount < $tagDepth) {
                    $replacementsCount++;
                    $buffer = '';
                }
            }
    
            if ($lineBit[$i] !== '.' && $lineBit[$i] !== '/') {
                $replacementsCount = 0;
            }
        }
    
        $replacedLineBit = $replacedLineBit . $buffer;
    
        return $replacedLineBit;
    }
}

?>