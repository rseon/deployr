<?php

namespace Deployr\Controller;

use Deployr\Tools;

class SettingsController extends AbstractController
{

    /**
     * @return mixed|void
     * @throws \Deployr\Exception\DbException
     */
    public function init()
    {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveSettings($_POST);
            return;
        }

        $this->assign([
            'available_langs' => $this->message->getAvailableLangs(),
            'lang' => $this->db->getSetting('lang'),
            'src_path' => $this->getSrcPath(),
            'dest_path' => $this->getDestPath(),
            'excludes' => $this->getExcludes(),
        ]);
    }

    /**
     * Save settings, obviously.
     *
     * @param array $post
     * @throws \Deployr\Exception\DbException
     */
    protected function saveSettings(array $post)
    {
        $data = [];
        if(isset($post['lang'])) {
            $data['lang'] = Tools::sanitize($post['lang']);
        }
        if(isset($post['src_path'])) {
            $data['src_path'] = Tools::sanitize($post['src_path']);
        }
        if(isset($post['dest_path'])) {
            $data['dest_path'] = Tools::sanitize($post['dest_path']);
        }
        if(isset($post['excludes'])) {
            $data['excludes'] = Tools::sanitize($post['excludes']);
            $data['excludes'] = implode(',', explode(PHP_EOL, $data['excludes']));
        }

        if($data) {
            $this->db->delete('settings', [
                'key' => array_keys($data)
            ]);

            $inserts = [];
            foreach($data as $key => $value) {
                $inserts[] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }

            $this->db->insert('settings', $inserts);
        }

        $this->db->insertLog('system', 'Settings updated');

        if(isset($data['lang'])) {
            $this->message->setLang($data['lang']);
        }

        $_SESSION['flash'] = $this->message->get('Settings updated');
        $this->redirect($this->link('settings'));
    }

}