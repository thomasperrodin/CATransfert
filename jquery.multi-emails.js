(function ($) {
    $.fn.multiEmails = function (options) {
        var settings = $.extend({
            fontAwesome: true,
        }, options);

        var keynum;
        var emailList = [];
        var hiddenField = $(this);

        let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

        $(this)
            .before(
                '<input type="text" id="email">'
            ).hide();

        function uniqueEmails(emails) {
            let uniqueEmails = [];
            $.each(emailList, function (i, el) {
                if ($.inArray(el, uniqueEmails) === -1) uniqueEmails.push(el);
            });

            return uniqueEmails;
        }

        $('#email').on('focusout keyup', function(e) {checkMail(e)});

        function checkMail(e) {
            $('.email-error').remove();
            if (window.event) { // IE
                keynum = e.keyCode;
            } else if (e.which) { // Netscape/Firefox/Opera
                keynum = e.which;
            }
            //if (keynum == 188) {
            if (e.type === "focusout" || keynum === 13) {

                let email = $('#email').val().replace(',', '');
                if (re.test(String(email).toLowerCase())) {
                    if ($("#expediteur-email").val().split("@")[1] === "ca-cb.fr") {
                        pushMails(email);
                    } else {
                        re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@ca-cb.fr$/;
                        if (re.test(String(email).toLowerCase())) {
                            pushMails(email);
                        } else {
                            let errrMessage = "<div class='email-error'>L\'email doit être de la forme @ca-cb.fr</div>";
                            pushErr(errrMessage);
                        }
                        re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    }
                } else {
                    let errrMessage = "<div class='email-error'>Adresse email incorrecte</div>";
                    pushErr(errrMessage);
                }
            }
        }

        $(document).on('click', ".remove", function () {
            let index = $(this).data("index");
            emailList.splice(index, 1);
            let displayList = '';
            uniqueEmails(emailList).forEach((value, ind) => {
                displayList += "<li>" + value + "<span class='remove' data-index=" + ind + ">" + ((settings.fontAwesome === true) ? '<i class=\"fas fa-times\"></i>' : 'X') + "</span></li>"
            })
            hiddenField.val(uniqueEmails(emailList));
            $("#show-emails ul").html(displayList);
        });

        function pushMails(email) {
            emailList.push(email);
            let displayList = '';

            uniqueEmails(emailList).forEach((value, ind) => {
                displayList += "<li>" + value + "<span class='remove' data-index=" + ind + ">" + ((settings.fontAwesome === true) ? '<i class=\"fas fa-times\"></i>' : 'X') + "</span></li>"
            })
            let buildEmailList = '<div id="show-emails"><ul>' + displayList + '</ul></div>'
            if ($("#show-emails").length) {
                $("#show-emails").replaceWith(buildEmailList);
            } else {
                $('#email').after(buildEmailList);
            }
            //hiddenField.val(JSON.stringify(uniqueEmails(emailList)));
            hiddenField.val(uniqueEmails(emailList).join(','));
            let parsleyForm = $("#formEnvoi").parsley({
                excluded: 'input[name="destinataire-objet"]'
            });
            if(parsleyForm.validate()) {
                parsleyForm.destroy();
                parsleyForm = $("#formEnvoi").parsley();
            }
            $('#email').val('');
        }

        function pushErr(errrMessage) {
            if ($("#show-emails").length) {
                $("#show-emails").after(errrMessage);
            } else {
                $('#email').after(errrMessage);
            }
        }

    };
}(jQuery))