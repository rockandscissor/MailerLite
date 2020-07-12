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
        // Connect to MailerLite
        $mailerlite = new MailerLite($this->getConfig('mailerlite_api_key'));

        // Set data for name and email fields
        $subscriber_data = [
            'fields' => [
              'name' => $submission->get(Arr::get($config, 'name_fields')),
            ],
            'email' => $submission->get(Arr::get($config, 'email_field')),
        ];


        // Check if Automatic Name Split is configured
        if ($auto_split_name = Arr::get($config, 'auto_split_name', true)) {

            // Split name by first space character
            $name_array = explode(' ', $subscriber_data['fields']['name'], 2);

            // Set data
            $subscriber_data['fields']['name'] = $name_array[0];
            $subscriber_data['fields']['last_name'] = $name_array[1] ?? '';
        }

        // Check if Opt-in field has been set
        if ($marketing_optin = Arr::get($config, 'auto_split_name', true)) {
        }

        // Check for mapped fields
        if ($mapped_fields = Arr::get($config, 'mapped_fields')) {

            // Loop through mapped fields
            collect($mapped_fields)->map(function ($item, $key) use ($submission, $config, &$subscriber_data) {

                // Store the submission data in an array for easy reference later on
                $submission_data = $submission->get(Arr::get($config, false));

                if (!empty($item["mapped_form_fields"])) { // In case there is no mapped form field

                    // Loop through each mapped form field for the given item and store in an array in prep for imploding
                    foreach ($item["mapped_form_fields"] as $form_field) {
                        $array[] = $submission_data[$form_field];
                    }

                    // Add named subscriber field index to the MailerLite payload using a pointed reference to $subscriber_data
                    $subscriber_data['fields'][$item['subscriber_field']] = implode(", ", $array);
                }
            });
        }

        // Use the MailerLite Subscriber API to add the subscriber
        $response = $mailerlite->groups()->addSubscriber($config['subscriber_group'], $subscriber_data);

        // Check response for errors
        if (array_key_exists('error', $response)) {
            \Log::error($response['error']['message']);
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
