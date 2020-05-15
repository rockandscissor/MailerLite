Statamic.conditions.noApiKey = function (fields) {
    return fields['mailerlite_api_key'].length == 0;
};
