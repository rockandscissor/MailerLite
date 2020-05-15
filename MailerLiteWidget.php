<?php

namespace Statamic\Addons\MailerLite;

use Statamic\Extend\Widget;

class MailerLiteWidget extends Widget
{
    /**
     * The HTML that should be shown in the widget
     *
     * @return string
     */
    public function html()
    {
        return $this->view('widget');
    }
}
