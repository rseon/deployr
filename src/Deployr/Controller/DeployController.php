<?php

namespace Deployr\Controller;

use Deployr\Tools;

class DeployController extends AbstractController
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
        elseif(isset($_POST['file']) && $_POST['file']) {
            $this->handleSingleFile();
        }
        else {
            $this->assign('error', $this->__('Please select one or more files to publish'));
        }
    }

    /**
     * 
     */
    protected function handleSingleFile()
    {
        $filename = Tools::sanitize($_POST['file']);
        list($filename, $file_src, $file_dest, $hash) = $this->getFile($filename);

        if(!isset($_POST['hash']) || $_POST['hash'] !== $hash) {
            $this->assign('error', $this->__('The hash of the file :file is not valid. Abort.', ['file' => $filename]));
            return;
        }

        $this->assign(compact('filename'));

        if($error = $this->checkFile($this->getExcludes(), $filename, $file_src)) {
            $this->assign('error', $error);
            return;
        }

        $rsync_cmd = implode(PHP_EOL, [
            'rsync -avz --relative '.$this->src_path.DIRECTORY_SEPARATOR.'.'.DIRECTORY_SEPARATOR.$filename.' '.$this->dest_path,
        ]);

        $output = shell_exec($rsync_cmd);
        //$output = 'received';

        $success = strpos($output, 'received') !== false;

        $this->db->log(Tools::sanitize($_POST['commit_author']), Tools::sanitize($_POST['commit_message'] ?? ''), $success, [$filename]);

        $this->assign(compact('file_src', 'file_dest', 'hash', 'rsync_cmd', 'output', 'success'));
    }

    /**
     *
     */
    protected function handleMultipleFiles()
    {
        $this->setView('deploy_multiple');
        $excludes = $this->getExcludes();
        $filenames = $_POST['files'];
        $cmds = [];
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

            if(is_file($file_src)) {
                $cmds[] = 'rsync -avz --relative '.$this->src_path.DIRECTORY_SEPARATOR.'.'.DIRECTORY_SEPARATOR.$filename.' '.$this->dest_path;
            }
        }

        $rsync_cmd = implode(PHP_EOL, $cmds);

        $output = shell_exec($rsync_cmd);
        //$output = 'received';

        $success = strpos($output, 'received') !== false;

        $this->db->log(Tools::sanitize($_POST['commit_author']), Tools::sanitize($_POST['commit_message'] ?? ''), $success, $filenames);

        $this->assign(compact('file_src', 'file_dest', 'hash', 'rsync_cmd', 'output', 'success'));
    }

}
