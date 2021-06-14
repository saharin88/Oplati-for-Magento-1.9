jQuery(document).ready(function ($) {

    let olatiObj = $('#oplati'),
        checkStatusTimeout = parseInt(olatiObj.data('timeout')),
        formKey = olatiObj.data('formKey')

    let checkStatus = function () {
        $.ajax({
            url: '//' + location.host + '/oplati/processing/checkStatus',
            data: {form_key: formKey},
            dataType: 'json',
            cache: false,
            success: function (resp) {
                if (resp.success) {
                    if (resp.data.status === 0) {
                        setTimeout(checkStatus, checkStatusTimeout);
                    } else if (resp.data.status === 1) {
                        olatiObj.html('<p class="text-success">' + resp.message + '</p>');
                        setTimeout(function () {
                            location.href = '//' + location.host + '/oplati/processing/success';
                        }, 3000);
                    } else {
                        olatiObj.html('<p class="text-danger">' + resp.message + '</p><p><button id="rePayment" class="button">' + Translator.translate('Repeat payment') + '</button><a class="button2" href="/oplati/processing/cancel">' + Translator.translate('Cancel payment') + '</a></p>');
                    }
                } else {
                    console.log(resp.message);
                }
            },
            error: function () {
                console.log('Response error');
            }
        });
    };

    setTimeout(checkStatus, checkStatusTimeout);

    $('body').on('click', 'button#rePayment', function (e) {

        $.ajax({
            url: '//' + location.host + '/oplati/processing/rePayment',
            data: {form_key: formKey},
            dataType: 'json',
            cache: false,
            success: function (resp) {
                if (resp.success) {
                    olatiObj.html($(resp.data).html());
                    setTimeout(checkStatus, checkStatusTimeout);
                } else {
                    alert(Translator.translate('Error'));
                    console.log(resp.message);
                }
            },
            error: function () {
                alert(Translator.translate('Response error'));
            }
        });


    });


});