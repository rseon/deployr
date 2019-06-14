<?php

namespace Deployr\Controller;

class LogsController extends AbstractController
{

    /**
     * @return mixed|void
     */
    public function init()
    {
        if(isset($_GET['delete']) && $_GET['delete']) {
            $this->deleteLog((int) $_GET['delete']);
        }

        $logs = $this->db->getBuilder()->getAll('log', [], ['date DESC']);

        $this->assign(compact('logs'));
    }

    /**
     * @param int $id
     */
    protected function deleteLog(int $id)
    {
        if($id) {
            $this->db->getBuilder()->delete('log', [
                'id' => (int) $id
            ]);
        }

        $_SESSION['flash'] = $this->message->get('Log deleted');
        $this->redirect($this->link('logs'));
    }
}