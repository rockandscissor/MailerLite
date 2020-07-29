# Install
1. Create a folder in `addons/` called `MailerLite`
2. Copy the files to this folder
3. Run `php please update:addons`
4. Head to the Control Panel > Addons > MailerLite > Settings

# Configuration
1. Head to your Control Panel and go to Addons > MailerLite > Settings
2. Enter your MailerLite API key (this can be found in MailerLite Integrations > Developer API)
3. Add a form entry for each form that you want to capture details from and setup your mapped fields (see field descriptions below)

# Field Descriptions
All the fields use Statamic's tag fieldtype which lets you easily add either a single field or multiple fields, just type your form field name in and hit enter to add it.

- **Form** - The form you want to capture details from
- **Subscriber Group** - *(Optional)* The subscriber group you want to add subscribers to
- **Name Field** - Defaults to `name` which you can change if your form uses something different
- **Email Field** - Defaults to `email` which you can change if your form uses something different (single field tag only)
- **Automatically Split Name** - This will split the submitted name by the first space character into MailerLite's `name` and `last_name` fields, *Note:* If `last_name` is mapped separately this setting will be ignored
- **Opt-in Field** - *(Optional)* If you require that a checkbox is checked on your form to subscribe someone to your list you can map that field here (single field tag only)
- **Marketing Permissions Field** - *(Optional)* If you want to setup MailerLite's GDPR compliant marketing permission fields you can map a checkbox array, see [this article](https://help.mailerlite.com/article/show/88106-checkboxes-and-marketing-permissions) on MailerLite for more information (single field tag only)
- **Mapped Fields** - *(Optional)* Here you can map any additional fields you have setup in MailerLite

# LICENSE

[MIT License](http://emd.mit-license.org/)
