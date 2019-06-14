<?php

namespace Deployr\Controller;

use Deployr\Tools;

class SettingsController extends AbstractController
{

    /**
     * @return mixed|void
     */
    public function init()
    {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveSettings($_POST);
            return;
        }

        $this->assign('available_langs', $this->message->getAvailable());
    }

    /**
     * @param array $post
     */
    protected function saveSettings(array $post)
    {
        $datas = [];
        if(isset($post['lang'])) {
            $datas['lang'] = Tools::sanitize($post['lang']);
        }
        if(isset($post['src_path'])) {
            $datas['src_path'] = Tools::sanitize($post['src_path']);
        }
        if(isset($post['dest_path'])) {
            $datas['dest_path'] = Tools::sanitize($post['dest_path']);
        }
        if(isset($post['excludes'])) {
            $datas['excludes'] = Tools::sanitize($post['excludes']);
        }

        if($datas) {
            $this->db->getBuilder()->delete('settings', [
                'key' => array_keys($datas)
            ]);

            $inserts = [];
            foreach($datas as $key => $value) {
                $inserts[] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }

            $this->db->getBuilder()->insert('settings', $inserts);
        }

        $this->db->log('system', 'Settings updated');

        if(isset($datas['lang'])) {
            $this->message->setLang($datas['lang']);
        }

        $_SESSION['flash'] = $this->message->get('Settings updated');
        $this->redirect($this->link('settings'));
    }

}