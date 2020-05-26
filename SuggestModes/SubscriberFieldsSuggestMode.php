<?php
namespace Statamic\Addons\MailerLite\SuggestModes;

use Statamic\Addons\Suggest\Modes\AbstractMode;
use MailerLiteApi\MailerLite;

class SubscriberFieldsSuggestMode extends AbstractMode
{
    public function suggestions()
    {
        // Connect to MailerLite and get Fields
        $mailerliteClient = new MailerLite($this->getConfig('mailerlite_api_key'));
        $fieldsApi = $mailerliteClient->fields();
        $allFields = $fieldsApi->get();

        // Create new array for Fields
        $suggestOptions = [];

        // Loop through Fields and put into new array
        foreach ($allFields as &$field) {

            // Check this isn't the name or email field
            if (!($field->key == 'name' || $field->key == 'email' || $field->key == 'marketing_permissions')) {

                // Add Field to Suggest Options
                $suggestOptions[] = ['value' => $field->key, 'text' => $field->title];

            }

        }

        // Return the options to Suggest Mode
        return $suggestOptions;
    }
}
