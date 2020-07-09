<?php

namespace Statamic\Addons\MailerLite;

use Log;
use Statamic\API\Arr;
use Statamic\Extend\Listener;
use MailerLiteApi\MailerLite;

class MailerLiteListener extends Listener
{
    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    public $events = [
        'Form.submission.created' => 'formSubmissionCreated',
    ];

    /**
     * Form Submission
     *
     * @param $submission \Statamic\Forms\Submission
     *
     * @throws
     */
    public function formSubmissionCreated($submission)
    {
        // Setup arrays
        $config = [];

        // Check if there is an API key saved
        if ($config['mailerlite_api_key'] = $this->getConfig('mailerlite_api_key', false)) {

            // Get form configuration values
            $formset_name = $submission->formset()->name();
            $config = $this->getFormConfiguration($formset_name);

            // Check for form setup
            if ($this->checkFormSetup($formset_name)) {

                // Check for Marketing Permissions
                if ($this->checkMarketingPermissions($config, $submission)) {

                    // Add Subscriber to MailerLite
                    $this->addSubscriber($config, $submission);

                }
            }
        }
    }

    /**
     * Is this form setup with mapped fields in our configuration?
     *
     * @param $formset_name string
     *
     * @return bool
     */
    private function checkFormSetup($formset_name)
    {
        return collect($this->getConfig('forms'))->contains(function ($ignore, $value) use ($formset_name) {
            return $formset_name == Arr::get($value, 'form');
        });
    }

    /**
     * Are there any Marketing Permissions fields setup and have they been accepted?
     *
     * @param $config array
     * @param $submission array
     *
     * @return bool
     */
    private function checkMarketingPermissions($config, $submission)
    {
        // Get marketing opt-in field
        $marketing_optin = Arr::get($config, 'marketing_optin_field.0', false);

        // Check if marketing permission field is in submission (which indicates it's checked) or if it's not in use
        if (request()->has($marketing_optin) || !($marketing_optin)) {
            return true;
        }

        // Return false as field is setup but has not been checked
        return false;
    }

    /**
     * Add Subscriber to MailerLite
     *
     * @param $email string
     * @param $submission \Statamic\Forms\Submission
     * @param $config array
     *
     * @return bool
     */
    private function addSubscriber($config, $submission)
    {
        // Setup arrays
        $data = [];

        // Connect to MailerLite
        $mailerliteClient = new MailerLite($this->getConfig('mailerlite_api_key'));

        // Set data for name and email fields
        $data = [
            'name' => $submission->get(Arr::get($config, 'name_fields')),
            'email' => $submission->get(Arr::get($config, 'email_field')),
        ];

        // Check if Automatic Name Split is configured
        if ($auto_split_name = Arr::get($config, 'auto_split_name', true)) {

            // Split name by first space character
            $name_array = explode(' ', $data['name'], 2);

            // Set data
            $data['name'] = $name_array[0];
            $data['last_name'] = $name_array[1];

        }

        // Check if Opt-in field has been set
        if ($marketing_optin = Arr::get($config, 'auto_split_name', true)) {



        }

        // Check for mapped fields
        if ($mapped_fields = Arr::get($config, 'mapped_fields')) {

            // Loop through mapped fields
            $data['fields'] = collect($mapped_fields)->map(function ($item, $key) use ($submission) {
                if (is_null($fieldData = $submission->get($item['mapped_form_fields']))) {
                    return [];
                }

                // convert arrays to strings...Mailchimp don't like no arrays
                return [
                    $item['tag'] => is_array($fieldData) ? implode('|', $fieldData) : $fieldData,
                ];
            });
            die(print_r($data['fields']));
        }

    }

    /**
     * Get the configuration for the submission
     *
     * @param $formset_name string
     *
     * @return mixed
     */
    private function getFormConfiguration($formset_name)
    {
        return collect($this->getConfig('forms'))->first(function ($ignored, $data) use ($formset_name) {
            return $formset_name == Arr::get($data, 'form');
        });
    }
}
