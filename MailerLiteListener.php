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
        'Form.submission.creating' => 'formSubmissionCreating',
    ];

    public function __construct()
    {
        $this->subscriber_data = [
            'fields' => [
                'name' => [],
            ],
            'email' => false,
        ];
    }

    /**
     * Form Submission
     *
     * @param $submission \Statamic\Forms\Submission
     *
     * @throws
     */
    public function formSubmissionCreating($submission)
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
                    return $submission = $this->addSubscriber($config, $submission);
                }
            }
        }

        return;
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
        // Store the submission data in an array for easy reference later on
        $submission_data = $submission->get(Arr::get($config, false));

        // Connect to MailerLite
        $mailerlite = new MailerLite($this->getConfig('mailerlite_api_key'));

        // Set data email field
        $this->subscriber_data['email'] = $submission->get($config['email_field']);

        if (!empty($config['name_field'])) { // Check if name_field is set
            $this->doMapFields('name', $config['name_field'], $submission_data, ' ');
        }

        // Check if Automatic Name Split is configured
        if ($auto_split_name = Arr::get($config, 'auto_split_name', true)) {
            if (count($config['name_field']) === 1) { // If we don't have more than 1 mapped field to the name name field
                // Split name by first space character
                $name_array = explode(' ', $this->subscriber_data['fields']['name'], 2);

                // Set data
                $this->subscriber_data['fields']['name'] = $name_array[0];
                $this->subscriber_data['fields']['last_name'] = $name_array[1] ?? '';
            }
        }

        // Check for mapped fields
        if ($mapped_fields = Arr::get($config, 'mapped_fields')) {
            // Loop through mapped fields
            collect($mapped_fields)->map(function ($item, $key) use ($submission_data) {
                if (!empty($item["mapped_form_fields"])) { // In case there is no mapped form field
                    $this->doMapFields($item['subscriber_field'], $item["mapped_form_fields"], $submission_data);
                }
            });
        }

        // Set options for api parameters
        $subscriber_options = [
            'resubscribe' => true
        ];

        // Check if subscriber group was setup
        if (isset($config['subscriber_group'])) {
          
            // Use the MailerLite Groups API to add the subscriber to a group
            $response = $mailerlite->groups()->addSubscriber($config['subscriber_group'], $this->subscriber_data, $subscriber_options);
        } else {

            // Use the MailerLite Subscriber API to add the subscriber
            $response = $mailerlite->subscribers()->create($this->subscriber_data, $subscriber_options);
        }


        // Check response for errors
        if (array_key_exists('error', $response)) {

            // Generate error to the log
            \Log::error("MailerLite - " . $response->error->message);
        } elseif (empty($response)) {

            // Generate error to the log
            \Log::error("MailerLite - Bad Request");
        }

        // Return the submission
        return [
            'submission' => $submission
        ];
    }

    /**
     * Combine multiple mapped fields
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

    /**
     * Map the fields ready for payload sent to MailerLite
     *
     * @param $fieldName string
     * @param $fieldArr array
     * @param $submission_data array
     * @param $separator string
     *
     */
    private function doMapFields($fieldName, $fieldArr, $submission_data, $separator = ", ")
    {
        foreach ($fieldArr as $field) {
            if (array_key_exists($field, $submission_data)) { // Check if the array key exists
                $field_data[] = $submission_data[$field];
            }
        };

        $field_data = implode($separator, $field_data);
        $this->subscriber_data['fields'][$fieldName] = $field_data;
    }
}
