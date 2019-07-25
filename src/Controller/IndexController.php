<?php

namespace Deployr\Controller;


class IndexController extends AbstractController
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

        if($error = $this->performChecks()) {
            $this->assign('error', $error);
            return;
        }

        $files = $this->getFiles();

        $this->assign(compact('src_path', 'dest_path', 'files'));
    }


    /**
     * Performs some checks before use.
     *
     * @return string
     */
    protected function performChecks(): string
    {

        // Missing configuration
        if(!$this->src_path || !$this->dest_path) {
            $error = $this->__('Please configure this app before use it');
            $error .= '<br /><a href="'. $this->link('settings') .'">'. $this->__('Go to settings') .'</a>';
            return $error;
        }

        if(!is_dir($this->src_path)) {
            $error = $this->__('Unable to access :type path :path', ['type' => 'source', 'path' => $this->src_path]);
            $error .= '<br /><a href="'. $this->link('settings') .'">'. $this->__('Go to settings') .'</a>';
            return $error;
        }
        if(!is_dir($this->dest_path)) {
            $error = $this->__('Unable to access :type path :path', ['type' => 'destination', 'path' => $this->dest_path]);
            $error .= '<br /><a href="'. $this->link('settings') .'">'. $this->__('Go to settings') .'</a>';
            return $error;
        }

        // Don't use this script in production
        if(strpos($this->app->getRootPath(), $this->dest_path) !== false) {
            header("HTTP/1.1 401 Unauthorized");
            $error = $this->__('Don\'t use this script in production !');
            return $error;
        }

        return '';
    }

    /**
     * List of modified files
     *
     * @return array
     */
    protected function getFiles(): array
    {
        $excludes = $this->getExcludes();
        $rsync_exclude = '';
        foreach($excludes as $e) {
            $rsync_exclude .= ' --exclude "'.$e.'"';
        }
        unset($rsync_exclusions);

        $rsync_cmd = implode(PHP_EOL, [
            'rsync -avn'.$rsync_exclude.' '.$this->src_path.DIRECTORY_SEPARATOR.' '.$this->dest_path.DIRECTORY_SEPARATOR,
        ]);

        $output = shell_exec($rsync_cmd);
        $raw_files = explode(PHP_EOL, $output);

        $files = [];

        foreach($raw_files as $k => $v) {
            if(is_file($this->src_path . DIRECTORY_SEPARATOR . $v)) {
                $files[] = [
                    'filename' => $v,
                    'hash' => $this->getHash($v),
                ];
            }
        }

        return $files;
    }

}