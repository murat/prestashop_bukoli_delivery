/**
 * BukoliDelivery: module for PrestaShop 1.5-1.6
 *
 * @author    muratbastas <muratbsts@gmail.com>
 * @copyright 2017 muratbastas
 * @license   http://addons.prestashop.com/en/content/12-terms-and-conditions-of-use Terms and conditions of use (EULA)
 */

var delivery_option;
var BukoliDelivery = {
    params: {
        isAddressSelected : false,
        radioId : ''
    },

    init: function () {
        var bukoli_html = '<tr id="bukoli_tr_id"><td id="bukoli_td_id" colspan="4"><div id="jetonDiv" style="max-width:calc(100% - 10px);width:calc(100% - 10px);height:500px;position:relative;"></div><div id="bukoliPointDiv"></div></td></tr>';
        BukoliDelivery.params.radioId = bukolidelivery_carrier_id + ',';
        var bukoli_tr = document.getElementById('bukoli_tr_id');
        if (!bukoli_tr) {
            var $radio = BukoliDelivery.getRadioSelector();

            if ($radio.closest('tr').html() !== null) {
                $radio.closest('tr').after(bukoli_html);
            } else {
                $('label[for="' + $radio.prop('id') + '"]').find('table.resume').append(bukoli_html);
            }
        }

        var jeton = new Jeton({
          targetDiv: document.getElementById("jetonDiv"), // zorunlu
          callbackFunc: BukoliDelivery.jetonSelected, // mandatory
        });
        jeton.Init();
    },

    destroy: function () {
        $("#bukoli_tr_id").remove();
    },

    jetonSelected: function (point) {
        $("#jetonDiv").remove();
        $("#bukoliPointDiv").html('Koliniz ' + point.PointName + ' adresine gonderilecektir.');
        BukoliDelivery.params.isAddressSelected = true;
        $.ajax({
            type: 'POST',
            headers: {"cache-control": "no-cache"},
            url: decodeURIComponent(bukolidelivery_controller),
            async: true,
            cache: false,
            dataType: 'json',
            data: 'action=saveBukoliDetails&PointCode='+point.PointCode,
            // success: function (data) {
            // },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if (textStatus !== 'abort') {
                    var message = "ERROR: adres seçilemedi.\n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus;
                    console.log(message);
                }
            }
        });
    },

    getRadioSelector: function () {
        var radioId = $('input[value="' + BukoliDelivery.params.radioId + '"]').attr('id');
        return $('#' + radioId);
    },

    checkAddressSelected: function () {
        var $radio = BukoliDelivery.getRadioSelector();

        var isRadioChecked = $radio.prop('checked') || ($radio.closest('span').prop('class') === 'checked');

        if (isRadioChecked && !BukoliDelivery.params.isAddressSelected) {
            BukoliDelivery.alertNotice();
            return false;
        }
        return true;
    },

    alertNotice: function () {
        var message = 'Lütfen önce bir adres seçin...';
        if (!!$.prototype.fancybox) {
            var options = {
                type: 'inline',
                autoScale: true,
                minHeight: 30,
                content: '<p class="fancybox-error">' + message + '</p>'
            };

            $.fancybox.open([options], {
                padding: 0
            });
        } else {
            alert(message);
        }
    }
};

$(document).ready(function () {
    delivery_option = $('input[name*="delivery_option"]:checked');
    if (delivery_option.val() === bukolidelivery_carrier_id + ',') {
        BukoliDelivery.init();
    } else {
        BukoliDelivery.destroy();
    }

    $(document).on('change', 'input[name*="delivery_option"]', function () {
        BukoliDelivery.destroy();
        if ($(this).val() === bukolidelivery_carrier_id + ',') {
            BukoliDelivery.init();
        } else {
            BukoliDelivery.destroy();
        }
    });

    var $radio = BukoliDelivery.getRadioSelector();
    $form = $radio.closest('form');
    if ($form.length != 0) {
        $(document).on('submit', $form, function () {
            return BukoliDelivery.checkAddressSelected();
        });
    }
    $(document).on('submit', 'form[name=carrier_area]', function () {
        return BukoliDelivery.checkAddressSelected();
    });
    $(document).on('click', '#HOOK_PAYMENT a', function () {
        return BukoliDelivery.checkAddressSelected();
    });

});
var updateCarrierList = (function () {
    var original = updateCarrierList;
    return function (json) {
        original(json);
        BukoliDelivery.init();
    };
})();
