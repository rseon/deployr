<?php

namespace Deployr\Controller;

use Deployr\Tools;
use Deployr\Diff;

class DiffController extends AbstractController
{

    protected $src_path;
    protected $dest_path;

    /**
     * @return mixed|void
     */
    public function init()
    {
        $src_path = $this->getSrcPath();
        $dest_path = $this->getDestPath();
        $this->src_path = $src_path;
        $this->dest_path = $dest_path;

        if(isset($_POST['files']) && $_POST['files']) {
            $this->handleMultipleFiles();
        }
        elseif(isset($_GET['file']) && $_GET['file']) {
            $this->handleSingleFile();
        }
        else {
            $this->assign('error', $this->__('Please select one or more files to display their differences'));
        }
    }

    /**
     * 
     */
    protected function handleSingleFile()
    {
        $filename = Tools::sanitize($_GET['file']);
        list($filename, $file_src, $file_dest, $hash) = $this->getFile($filename);

        if(!isset($_GET['hash']) || $_GET['hash'] !== $hash) {
            $this->assign('error', $this->__('The hash of the file :file is not valid. Abort.', ['file' => $filename]));
            return;
        }

        $this->assign(compact('filename'));

        if($error = $this->checkFile($this->getExcludes(), $filename, $file_src)) {
            $this->assign('error', $error);
            return;
        }

        if(!is_file($file_dest)) {
            $exists = false;
            $content = htmlentities(file_get_contents($file_src));
        }
        else {
            $exists = true;
            $content = Diff::toTable(Diff::compareFiles($file_dest, $file_src));
        }

        $this->assign(compact('file_src', 'file_dest', 'hash', 'exists', 'content'));
    }

    /**
     *
     */
    protected function handleMultipleFiles()
    {
        $this->setView('diff_multiple');
        $excludes = $this->getExcludes();
        $filenames = $_POST['files'];
        $files = [];
        foreach($filenames as $i => $filename) {
            $filename = Tools::sanitize($filename);
            list($filename, $file_src, $file_dest, $hash) = $this->getFile($filename);

            if(!isset($_POST['hashes']) || !isset($_POST['hashes'][$i]) || $_POST['hashes'][$i] !== $hash) {
                $this->assign('error', $this->__('The hash of the file :file is not valid. Abort.', ['file' => $filename]));
                return;
            }

            if($error = $this->checkFile($excludes, $filename, $file_src)) {
                $this->assign('error', $error);
                return;
            }

            if(!is_file($file_dest)) {
                $exists = false;
                $content = htmlentities(file_get_contents($file_src));
            }
            else {
                $exists = true;
                $content = Diff::toTable(Diff::compareFiles($file_dest, $file_src));
            }

            $files[] = compact('filename', 'file_src', 'file_dest', 'hash', 'exists', 'content');
        }

        $this->assign(compact('files'));
    }


}
