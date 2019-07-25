<?php

namespace Deployr\Controller;

class LogsController extends AbstractController
{

    /**
     * @return mixed|void
     * @throws \Deployr\Exception\DbException
     */
    public function init()
    {
        if(isset($_GET['delete']) && $_GET['delete']) {
            $this->deleteLog((int) $_GET['delete']);
        }

        $logs = $this->db->getAll('logs', [], 'date DESC');

        $this->assign(compact('logs'));
    }

    /**
     * Delete a log
     *
     * @param int $id
     * @throws \Deployr\Exception\DbException
     */
    protected function deleteLog(int $id)
    {
        if($id) {
            $this->db->delete('logs', [
                'id' => (int) $id
            ]);
        }

        $_SESSION['flash'] = $this->message->get('Log deleted');
        $this->redirect($this->link('logs'));
    }
}