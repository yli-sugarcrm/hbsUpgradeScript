<?php
include './treeWalkerClass.php';
include './handlebarConditionals.php';

function upgradeHandleBars($path) {
    $treeWalker = new treeWalker($path, array('node_modules'));
    $treeWalker->mapFiles(); // Walk through the tree to the deepest level and create a structure of it
    $hbsFiles = $treeWalker->getFilesList('hbs', false, 'path'); //get all the hbs files with path, true - they will be echoed too (path or name)

    $update1 = new pathFixer();
    forEach($hbsFiles as $hbs) {
        $update1->run($hbs);
    }
}

if (isset($_POST['path'])) {
    upgradeHandleBars($_POST['path']);
}

?>
