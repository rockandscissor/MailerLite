<?php

namespace Statamic\Addons\MailerLite;

use Statamic\Extend\Controller;

class MailerLiteController extends Controller
{
    /**
     * Maps to your route definition in routes.yaml
     *
     * @return mixed
     */
    public function index()
    {
        return $this->view('index');
    }
}
