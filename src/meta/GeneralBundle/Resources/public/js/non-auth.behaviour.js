/*global $,Translator,alertify */
/*jslint browser: true*/
$(document).ready(function() {

    /*
     * Login (non-auth) local cookies
     */
    $(".lang").on('click', function(e) {
        e.preventDefault();
        var lang = $(this).attr('rel');
        document.cookie = "igneet_lang=" + lang + "; path=/; expires=Wed, 1 Jan 2020 00:42:42 UTC;";
        location.reload(true);
    });

});