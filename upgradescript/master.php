<?php
include './treeWalkerClass.php';
include './handlebarConditionals.php';

function upgradeHandleBars($path) {
    $treeWalker = new treeWalker($path);
    $treeWalker->mapFiles(); // Walk through the tree to the deepest level and create a structure of it
    $hbsFiles = $treeWalker->getFilesList('hbs', true, 'path'); //get all the hbs files with path, true - they will be echoed too (path or name)

    $update1 = new rootFixer();
    forEach($hbsFiles as $hbs) {
        $update1->run($hbs);
    }
}

if (isset($_POST['path'])) {
    upgradeHandleBars($_POST['path']);
}

// LIST OF FOLDERS AND SUBFOLDERS
//$treeWalker->getFolderList(false);

// RUN SCRIPT ON THIS
//$kx = new treeWalker('./ent', array('node_modules'));

//READ LINE BY LINE
//$ks->readFileByLines($el);
?>
