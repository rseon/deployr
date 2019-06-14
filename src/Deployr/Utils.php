<?php

namespace Deployr;

trait Utils {
	
	/**
     * Link to a page
     *
     * @param string $page
     * @param array $params
     * @return string
     */
    public function link(?string $page = '', array $params = []): string
    {
        $param_key = $this->app->getOption('param_key');

        $query = [
            $param_key => $_GET[$param_key],
        ];
        if($page) {
            $query[$this->app->getOption('param_page')] = $page;
        }

        if($params) {
            $query = array_merge($query, $params);
        }

        return $_SERVER['PHP_SELF'].'?'.http_build_query($query);
    }

    /**
     * Get current page
     *
     * @return string
     */
    public function route(): string
    {
        return $_GET[$this->app->getOption('param_page')] ?? '';
    }

    /**
     * Get link to a path.
     *
     * @param null|string $path
     * @return string
     */
    public function path(?string $path = ''): string
    {
        return pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME).$path;
    }

    /**
     * Alias to translate a string
     *
     * @param string $string
     * @param array $params
     * @return mixed
     */
    public function __(string $string, array $params = [])
    {
        return $this->message->get($string, $params);
    }

    /**
     * Redirect to another page
     *
     * @param string $location
     */
    public function redirect(string $location)
    {
        header('Location: '.$location);
        exit;
    }


    public function getSrcPath(): string
    {
        return $this->db->getSetting('src_path');
    }

    public function getDestPath(): string
    {
        return $this->db->getSetting('dest_path');
    }

    public function getExcludes(): array
    {
        return explode(PHP_EOL, $this->db->getSetting('excludes'));
    }

    public function getHash($str) {
        return sha1('|deployr!|'.strlen($str).'|'.$str);
    }

    /**
     * @param string $filename
     * @return array
     */
    protected function getFile(string $filename): array
    {
        // Security fix
        if(strpos($filename, '..') !== false) {
            header("HTTP/1.1 401 Unauthorized");
            $this->redirect($this->link('index'));
        }

        return [
            $filename,
            $this->src_path.DIRECTORY_SEPARATOR.$filename,
            $this->dest_path.DIRECTORY_SEPARATOR.$filename,
            $this->getHash($filename),
        ];
    }

    /**
     *
     */
    protected function checkFile($exclusions, $filename, $file_src): string
    {
        if(!$filename || !is_file($file_src)) {
            return $this->__('The file :file does not seem to exist. Abort.', ['file' => $filename]);
        }

        // Fichier exclu ?
        $pastouche = false;
        foreach($exclusions as $r) {
            $r = str_replace('*', '', $r);
            if(strpos($filename, $r) !== false) {
                $pastouche = true;
                break;
            }
        }
        if($pastouche) {
            return $this->__('File :file not to publish. Abort.', ['file' => $filename]);
        }

        return '';
    }
}
