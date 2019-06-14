<?php

namespace Deployr\Controller;

class NotFoundController extends AbstractController
{

    /**
     * @return mixed|void
     */
    public function init()
    {
        $view = $this->view;
    	header("HTTP/1.0 404 Not Found");
        $this->setView('404');
        $this->assign('page', $view);
    }

}
