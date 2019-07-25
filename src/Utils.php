<?php

namespace Deployr;

trait Utils {


    /**
     * Get link to a page
     *
     * @param string|null $page
     * @param array $params
     * @return string
     */
    public function link(?string $page = '', array $params = []): string
    {
        $param_key = $this->app->getOption('access_key_name');

        $query = [
            $param_key => $_GET[$param_key],
        ];
        if($page) {
            $query[$this->app::PARAM_PAGE] = $page;
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
        return $_GET[$this->app::PARAM_PAGE] ?? '';
    }

    /**
     * Get link to a path.
     *
     * @param string|null $path
     * @return string
     */
    public function path(?string $path = ''): string
    {
        return pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME).$path;
    }

    /**
     * Shortcut to translate a string
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

    /**
     * Shortcut to get src_path from settings
     *
     * @return string
     */
    public function getSrcPath(): string
    {
        return $this->db->getSetting('src_path') ?? '';
    }

    /**
     * Shortcut to get dest_path from settings
     *
     * @return string
     */
    public function getDestPath(): string
    {
        return $this->db->getSetting('dest_path') ?? '';
    }

    /**
     * Shortcut to get exluded files from settings
     *
     * @return array
     */
    public function getExcludes(): array
    {
        $excludes = $this->db->getSetting('excludes');
        if($excludes) {
            return array_map(function($v) {
                return str_replace([PHP_EOL, "\r", "\n"], '', $v);
            }, explode(',', $excludes));
        }
        return [];
    }

    /**
     * Get hashed string
     *
     * @param $str
     * @return string
     */
    public function getHash(string $str): string
    {
        return sha1('|deployr!|'.strlen($str).'|'.$str);
    }

    /**
     * Get a file
     *
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
     * Check a file
     *
     * @param $exclusions
     * @param $filename
     * @param $file_src
     * @return string
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
