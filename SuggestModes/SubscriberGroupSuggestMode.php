<?php
namespace Statamic\Addons\MailerLite\SuggestModes;

use Statamic\Addons\Suggest\Modes\AbstractMode;
use MailerLiteApi\MailerLite;

class SubscriberGroupSuggestMode extends AbstractMode
{
    public function suggestions()
    {
        // Connect to MailerLite and get Groups
        $mailerliteClient = new MailerLite($this->getConfig('mailerlite_api_key'));
        $groupsApi = $mailerliteClient->groups();
        $allGroups = $groupsApi->get();

        // Create new array for Groups
        $suggestOptions = [];

        // Loop through Groups and put into new array
        foreach ($allGroups as &$group) {

            // Add Group to Suggest Options
            $suggestOptions[] = ['value' => $group->id, 'text' => $group->name];

        }

        // Return the options to Suggest Mode
        return $suggestOptions;
    }
}
