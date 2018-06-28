<?php
include './fileManagerClass.php';

class pathFixer {
    private $tree;
    private $helperTags;
    private $tempHelpers;

    /**
     * Execute the pathfixer class on a handlerbars file (.hbs).
     * 1st step: read the file's content
     * 2nd step: create a map of the helpers.
     * 3rd step: replace "../" according to the helpers map.
     * 4th step: write the updated content to file.
     * @param hbs The path to a hbs file.
     */
    public function run($hbs) {
        $this->tree = array();
        $this->helperTags = array();

        $newContent = array();
        $manager = new fileManager();
        $oldContent = $manager->readFileByLines($hbs->path);

        forEach($oldContent as $line) {
            $tags = $this->_extractTags($line);
            array_push($this->helperTags, $tags);
        }

        forEach($oldContent as $i=>$line) {
            $newLine = $this->_getUpdatedLine($i, $line);
            //echo htmlentities($newLine) . '<br/>';
            array_push($newContent, $newLine);
        }

        //return false;
        $manager->write($hbs->path, $newContent);
    }

    /***************************************** */
    /********** Create a map of helpers ****** */
    /***************************************** */

    /**
     * Looks through a line and composes a list of tags to be considered.
     * @param line A line from a file's content.
     * @return tag Information about tags found in the line ispected.
     */
    private function _extractTags($line) {
        $temp = $line;
        $tags = array();
        $progressMeter = 0;
        $pos = $this->_getTagStartPos($line);

        while(is_int($pos)) {
            $tag = $this->_getTag($temp, $progressMeter, $pos);
            $progressMeter = $tag->closeat;
            if ($this->_isContextChanger($tag)) {
                array_push($tags, $tag);
                $this->_updateHelperTree($tag);
            }

            $temp = substr($line, $tag->closeat);
            $pos = $this->_getTagStartPos($temp);
        }

        return $tags;
    }

    /**
     * Attempts to find a tag (opening or closing),
     * but it must return the tag which is encountered sooner.
     * If there aren't any tags in the given line, returns false.
     * @param str A chunk from the content.
     * @return position The index of the tag based on the given string.
     */
    private function _getTagStartPos($str) {
        $tagEnd = '{{/';
        $tagStart = '{{#';
        $tagEndPos = strpos($str, $tagEnd);
        $tagStartPos = strpos($str, $tagStart);
        return $this->_jsmin($tagStartPos, $tagEndPos);
    }

    /**
     * Read the tag from the given position
     * and collect any information needed later.
     * @param str A piece from a content's line.
     * @param pos The starting position of the tag.
     * @return tag Tag information object.
     */
    private function _getTag($str, $progressMeter, $pos) {
        $temp = substr($str, $pos);
        $firstSpace = $this->_jsmin(strpos($temp, ' '), strpos($temp, '}}'));
        
        $tag = new StdClass();
        $tag->index = $progressMeter + $pos;
        $tag->parents = array_reverse($this->tree);
        $tag->name = substr($temp, 2, $firstSpace - 2);
        $tag->closeat = $progressMeter + strpos($str, '}}') + 2;

        return $tag;
    }

    /**
     * Checks if the given tag is a tag which creates a new context.
     * To put it simple: checks if it is an 'if', 'unless' or 'each' tag;
     * it doesn't matter if it is closing or opening tag.
     * @param tag Tag information object.
     * @return 
     */
    private function _isContextChanger($tag) {
        $isIf = strpos($tag->name, 'if');
        $isEach = strpos($tag->name, 'each');
        $isUnless = strpos($tag->name, 'unless');
        return $isIf || $isUnless || $isEach;
    }

    /***************************************** */
    /*********** Replace ../ instances ******* */
    /***************************************** */

    /**
     * A single line is stripped into pieces,
     * if there are tags in it, then passed to the replacer.
     * If there aren't, it is passed to the replacer.
     * @param i Line number.
     * @param line A piece of the original content.
     * @return newLine The updated line.
     */
    private function _getUpdatedLine($i, $line) {
        $newline = '';
        $progress = 0;
        $tags = $this->helperTags[$i];

        if ($tags) { $j = 0;
            while(isset($tags[$j])) {
                $tag = $tags[$j];
                $str = substr($line, $progress, $tag->closeat - $progress);
                $newline .= $this->_updatePaths($str);

                $progress = $tag->closeat;
                $this->_updateHelperTree($tag);
                $j++; 
            }

            $str = substr($line, $progress, strlen($line) - $progress);
            $newline .= $this->_updatePaths($str);
        } else {
            $newline .= $this->_updatePaths($line);
        }

        return $newline;
    }

    /**
     * Manage the helper hierarchy tree.
     * This tree will be appended to each tag,
     * so we could easily check what parrents the tag is having.
     * @param tag Tag information object.
     */
    private function _updateHelperTree($tag) {
        if (strpos($tag->name, '#') === 0) {
            array_push($this->tree, $tag->name);
        } else {
            array_pop($this->tree);
        }
    }

    /**
     * This is where the actual replacement takes place.
     * @param str A string.
     */
    private function _updatePaths($str) {
        $path = '';
        $pathIndex = strpos($str, '../');
        $containsPath = is_int($pathIndex);

        if ($containsPath) {
            $newstr = substr($str, 0, $pathIndex);
        } else {
            $newstr = $str;
        }

        $k = 0;

        while($containsPath) {
            $k = 0;
            while(substr($str, $pathIndex + $k, 3) === '../') {
                $path .= substr($str, $pathIndex + $k, 3);
                $k += 3;
            }

            $newstr .= $this->_changePath($path);
            $path = '';

            $str = substr($str, $pathIndex + $k);
            $pathIndex = strpos($str, '../');
            $containsPath = is_int($pathIndex);
            if (!$containsPath) {
                $str = substr($str, $pathIndex);
                $newstr .= $str;
            }
        }

        return $newstr;
    }

    private function _changePath($path) {
        $reverseTree = array_reverse($this->tree);

        //echo '<br>' . json_encode($reverseTree);
        for ($i = 0; $i < strlen($path) / 3; $i++) {
            if (isset($reverseTree[$i])) {
                if ($reverseTree[$i] === '#each') {
                    //do nothing
                } else {
                    $path = substr($path, 3);
                }
            }
        }

        return $path;
    }

    /***************************************** */
    /************* Utility ******************* */
    /***************************************** */

    /**
     * Method "min" returns false/nothing if one of the value is not a number.
     * I need the functionality of js Math.min, which disregards false values.
     * @param a First number.
     * @param b Second number.
     * @return min The smaller of the 2 numbers.
     */
    private function _jsmin($a, $b) {
        if (is_int($a) && is_int($b)) {
            $min = min($a, $b);
        } else if (is_int($a)) {
            $min = $a;
        } else if (is_int($b)) {
            $min = $b;
        } else {
            $min = false;
        }
        return $min;
    }
}
?>
