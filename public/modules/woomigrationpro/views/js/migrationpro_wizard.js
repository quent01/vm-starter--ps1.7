/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from MigrationPro
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the MigrationPro is strictly forbidden.
 * In order to obtain a license, please contact us: contact@migration-pro.com
 *
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe MigrationPro
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la MigrationPro est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la MigrationPro a l'adresse: contact@migration-pro.com
 *
 * @author    MigrationPro
 * @copyright Copyright (c) 2012-2019 MigrationPro
 * @license   Commercial license
 * @package   MigrationPro: WooCommerce To PrestaShop
 */

(function ($) {
    "use strict";
    $(document).ready(function () {

        $('ul.steps > li > a').each(function (index) {
            $(this).on("click", function () {
                var step = $(this).attr('rel');
                if (step < 3) {
                    $('.actionBar .buttonNext').show();
                    if (step != 1) {
                        $('.actionBar .buttonPrevious').show();
                    }
                }
            });
        });

        $('.migration-button').on('click', function () {
            generateZip();
        });

        $('.buttonResume').on('click', function () {

            $(".box-stats").remove();
            /*$("#migrationpro_wizard").smartWizard('disableStep', 1);
             $("#migrationpro_wizard").smartWizard('disableStep', 2);
             $("#migrationpro_wizard").smartWizard('enableStep', 3);
             $("#step-1").hide();
             $("#step-2").hide();
             $("#step-3").show();
             $('.actionBar .buttonNext').hide();
             $("#migrationpro_wizard").smartWizard('setCurrentStep', 3);
             displayForm(datas.step_form, 3);
             resizeWizard();
             changePercentByEntityType(datas);
             console.log('resume');
             importData();*/
            $.ajax({
                type: "POST",
                url: validate_url,
                async: false,
                dataType: 'json',
                data: 'resume=1&action=validate_step&ajax=1',
                success: function (datas) {
                    $("#migrationpro_wizard").smartWizard('disableStep', 1);
                    $("#migrationpro_wizard").smartWizard('disableStep', 2);
                    $("#migrationpro_wizard").smartWizard('enableStep', 3);
                    $("#step-1").hide();
                    $("#step-2").hide();
                    $("#step-3").show();
                    $('.actionBar .buttonNext').hide();
                    $("#migrationpro_wizard").smartWizard('setCurrentStep', 3);
                    displayForm(datas.step_form, 3);
                    resizeWizard();
                    changePercentByEntityType(datas);
                    // console.log('resume');
                    setTimeout(importData, 2500);
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    if (XMLHttpRequest.responseText.length > 0)
                        jAlert("TECHNICAL ERROR: \n\nDetails:\nError thrown: " + XMLHttpRequest.responseText + "\n" + 'Text status: ' + textStatus);
                }
            });
        });
        bind_inputs();
        initMigrationProWizard();
    });

    function initMigrationProWizard() {
        $("#migrationpro_wizard").smartWizard({
            selected: 0,
            labelNext: labelNext,
            labelPrevious: labelPrevious,
            labelFinish: labelFinish,
            fixHeight: 1,
            // Events
            onShowStep: onShowStepCallback,
            onLeaveStep: onLeaveStepCallback,
            onFinish: importData,
            transitionEffect: 'slideleft',
            enableAllSteps: false,
            keyNavigation: false,
            noForwardJumping: true,
            enableFinishButton: false,
            hideButtonsOnDisabled: true
        });

        $('.buttonFinish').on('click', function () {
            $('.actionBar .buttonNext').hide();
            $('.actionBar .buttonPrevious').hide();
            $('.actionBar .buttonFinish').hide();
        });
    }

    function disableFinishButton($wiz) {
        var $actionBar = $('.actionBar');
        if ($wiz.smartWizard('currentStep') < 3) {
            $('.buttonFinish', $actionBar).hide();
        }
    }

    function onShowStepCallback() {
        $('.anchor li a').each(function () {
            $(this).closest('li').addClass($(this).attr('class'));
        });

        disableFinishButton($("#migrationpro_wizard"));
        resizeWizard();
    }

    function onFinishCallback(obj, context) {
        $('.error.wizard_error').remove();
        $.ajax({
            type: "POST",
            url: validate_url,
            async: false,
            dataType: 'json',
            data: $('#migrationpro_wizard .stepContainer .content form').serialize() + '&to_step_number=' + context.fromStep + '&action=import_process&ajax=1&step_number=' + context.fromStep,
            success: function (datas) {
                
                if (datas.has_error || datas.has_warning) {

                    if (datas.has_error)
                        displayError(datas.errors, context.fromStep);

                    if (datas.has_warning)
                        displayWarning(datas.warnings, context.fromStep);

                    resizeWizard();
                }
                else {
                    displayForm(datas.step_form, context.fromStep);
                    importData();
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if (XMLHttpRequest.responseText.length > 0)
                    $('#step-' + context.fromStep).prepend(XMLHttpRequest.responseText);
            }
        });
    }

    function onLeaveStepCallback(obj, context) {
        return validateSteps(context.fromStep, context.toStep); // return false to stay on step and true to continue navigation
    }

    function validateSteps(fromStep, toStep) {
        var is_ok = true;
        $('.wizard_error').remove();
        if (is_ok) {
            var form = $('#migrationpro_wizard #step-' + fromStep + ' form');
            $.ajax({
                type: "POST",
                url: validate_url,
                async: false,
                dataType: 'json',
                data: form.serialize() + '&to_step_number=' + toStep + '&step_number=' + fromStep + '&action=validate_step&ajax=1',
                success: function (datas) {
                    if (datas.has_error || datas.has_warning) {
                        is_ok = false;

                        if (datas.has_error)
                            displayError(datas.errors, fromStep);

                        if (datas.has_warning)
                            displayWarning(datas.warnings, fromStep);

                        resizeWizard();
                    }
                    else {
                        displayForm(datas.step_form, toStep);
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    if (XMLHttpRequest.responseText.length > 0)
                        $('#step-' + context.fromStep).prepend(XMLHttpRequest.responseText);
                }
            });
        }

        return is_ok;
    }

    function generateZip(){
        $.ajax({
            type: "POST",
            url: validate_url,
            async: false,
            dataType: 'json',
            data: {
                action: 'generate_zip',
                ajax: 'true'
            },
            success: function () {
                window.location.href=document.getElementsByClassName('migration-button')[0].getAttribute('data-module-path')+"assets/connector.zip";
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if (XMLHttpRequest.responseText.length > 0)
                    $('#step-' + 3).prepend(XMLHttpRequest.responseText);
            }
        });

    }

    function clearCacheAndReIndex() {
        $('#step-3-done').hide();
        // $('#step-3 .row:eq(0)').hide();
        // $('#step-3 .row:eq(1)').hide();
        $.ajax({
            type: "POST",
            url: validate_url,
            async: false,
            dataType: 'json',
            data: {
                action: 'clear_cache',
                ajax: 'true',
                clear_cache: 1
            },
            success: function (datas) {

                if (datas.has_error || datas.has_warning) {

                    if (datas.has_error) {
                        displayError(datas.errors, 3);
                    }

                    if (datas.has_warning)
                        displayWarning(datas.warnings, 3);
                }
                else {
                    $('#step-3-clear').fadeIn('fast');
                    $('.buttonClear ').hide();
                }
                resizeWizard();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if (XMLHttpRequest.responseText.length > 0)
                    $('#step-' + 3).prepend(XMLHttpRequest.responseText);
            }
        });
    }

    (function() {
    window.setdebug= setdebug;
    function setdebug(value) {
        $.ajax({
            type: "POST",
            url: validate_url,
            async: false,
            dataType: 'json',
            data: {
                action: 'debug_on',
                ajax: 'true',
                turn: value
            },
            success: function () {
                if (value==1) {
                    console.log('you set debug mode to On')
                }else{
                    console.log('you set debug mode to Off')
                }
                
            },
            error: function () {
                 console.log('There is a problem, we cant access debug mode, plese check again')
            }
        });   
    }
    })();

    (function() {
        window.setspeed= setspeed;
        function setspeed(value) {
            $.ajax({
                type: "POST",
                url: validate_url,
                async: false,
                dataType: 'json',
                data: {
                    action: 'speed_up',
                    ajax: 'true',
                    speed: value
                },
                success: function () {
                    console.log('you have set migration speed to ' + value)
                },
                error: function () {
                    console.log('There is a problem, we cant set speed, please check again')
                }
            });
        }
    })();

    function importData() {
        $.ajax({
            type: "POST",
            url: validate_url,
            async: false,
            dataType: 'json',
            data: {
                action: 'import_process',
                ajax: 'true'
            },
            success: function (datas) {
                // console.log('importData');
                if (datas.has_error) {

                    if (datas.has_error) {
                        displayError(datas.errors, 3);
                        $('.buttonTry').show();
                    }
                }
                else {

                    if (datas.has_warning)
                        displayWarning(datas.warnings, 3);


                    if (parseInt(datas.percent) !== 100) {
                        setTimeout(importData, 2500); //@TODO dynamic timeOut
                    } else {
                        $(".progress-container").fadeOut('slow');
                        $('#step-3-done').fadeIn('fast');
                        $('.process').hide();
                        $('.buttonClear').show();
                        $(window).unbind('beforeunload');
                        $(document).on('click', '.buttonClear', function () {
                            clearCacheAndReIndex();
                        });

                        resizeWizard();
                    }
                }

                changePercentByEntityType(datas);
                // changePercent(datas.percent);
                resizeWizard();

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if (XMLHttpRequest.responseText.length > 0)
                    $('#step-' + 3).prepend(XMLHttpRequest.responseText);
            }
        });
    }

    function displayWarning(warnings, step_number) {
        $('#migrationpro_wizard .actionBar a.btn').removeClass('disabled');
        $('.warning.wizard_error').remove();
        var str_warning = '<div class="warning wizard_error" style="display:none"><ul>';
        for (var warning in warnings) {
            str_warning += '<li>' + warnings[warning] + '</li>';
        }
        str_warning += '<p><a class="migration-warning-log" style="cursor:pointer" target="_blank" href=' + warningLogPath + '>' + downloadLogText + '</a></p>';

        $('#step-' + step_number).prepend(str_warning + '</ul></div>');
        $('.warning.wizard_error').fadeIn('fast');
        $('#go-top').click();
        bind_inputs();
    }

    function displayError(errors, step_number) {
        $('#migrationpro_wizard .actionBar a.btn').removeClass('disabled');
        $('.error.wizard_error').remove();
        var str_error = '<div class="error wizard_error" style="display:none"><ul>';
        for (var error in errors) {
            str_error += '<li>' + errors[error] + '</li>';
        }
        $('#step-' + step_number).prepend(str_error + '</ul></div>');
        $('.error.wizard_error').fadeIn('fast');
        $('#go-top').click();
        bind_inputs();
    }

    function initSpeedRangeSlider() {
             $("#input_speed_range_slider").ionRangeSlider({
             grid: true,
             from: 2,
             values: ["VerySlow","Slow","Normal", "Fast", "VeryFast", "MigrationProSpeed"]
        });
        $('#input_speed_range_slider').parent().removeClass('col-lg-9').addClass('col-lg-8').css('left','2%')
    }
    function initClickBothOrderCostumer() {
            $("label[for='entities_orders_on']").click(function () {
                $("label[for='entities_customers_on']").click()
            }
          )
            $("label[for='entities_customers_off']").click(function () {
                $("label[for='entities_orders_off']").click()
            }
          )
    }

    function initClickBothClearAndRecentData() {
        $("label[for='clear_data_on']").click(function () {
                $("label[for='migrate_recent_data_off']").click()
            }
        );
        $("label[for='migrate_recent_data_on']").click(function () {
                $("label[for='clear_data_off']").click()
            }
        )
    }

    function initEntitiesSelectAll() {
        var entitiesLabels=$('label[for="entities_select_all_on"]').parents().eq(3)
        $('label[for="entities_select_all_on"]').click(function () {
           for (var i = 0; i<entitiesLabels.find('label[for$=on]').length; i++) {
               entitiesLabels.find('label[for$=on]')[i].click();
           }
        })
        $('label[for="entities_select_all_off"]').click(function () {
           for (var i = 0; i<entitiesLabels.find('label[for$=off]').length; i++) {
               entitiesLabels.find('label[for$=off]')[i].click();
           }
        }) 
    }
    function initAdvancedOptionsSelectAll() {
        var advancedLabels=$('label[for="force_select_all_on"]').parents().eq(3)
        $('label[for="force_select_all_on"]').click(function () {
           for (var i = 0; i<advancedLabels.find('label[for$=on]').length; i++) {
               advancedLabels.find('label[for$=on]')[i].click();
           }
        })
        $('label[for="force_select_all_off"]').click(function () {
           for (var i = 0; i<advancedLabels.find('label[for$=off]').length; i++) {
               advancedLabels.find('label[for$=off]')[i].click();
           }
        }) 
    }

    function confirmationReload(num) {
        if (num==3) {
            $(window).bind('beforeunload', function () {    
                return 'are you sure you want to leave?';
            });   
        }else{
             $(window).unbind('beforeunload')
        }
    }

    function displayForm(step_form, step_number) {
        var form = $('#migrationpro_wizard #step-' + step_number);
        form.html(step_form);
        if (step_number == 2) {
            initSpeedRangeSlider();
            initEntitiesSelectAll();
            initAdvancedOptionsSelectAll();
            initClickBothOrderCostumer();
            initClickBothClearAndRecentData();
            $('.label-tooltip').tooltip();
        } 

            confirmationReload(step_number);


    }

    function resizeWizard() {
        var resizeInterval = setInterval(function () {
            $("#migrationpro_wizard").smartWizard('fixHeight');
            clearInterval(resizeInterval)
        }, 100);

        $(function () {
            var $orders = $("#entities_orders"),
                $customers = $('#entities_customers');
            $orders.closest('div.checkbox').css('margin-left', '20px');
            $orders.change(function () {
                if ($orders.is(':checked')) {
                    $customers.attr('checked', true);
                }
            });
            $customers.change(function () {
                if ($customers.not(':checked')) {
                    $orders.attr('checked', false);
                }
            })
        });
    }

    function bind_inputs() {

        $('input, select').focus(function () {
            $('#migrationpro_wizard .actionBar a.btn').not('.buttonFinish').removeClass('disabled');
        });
        $('.buttonTry').on('click', function () {
            $('.error.wizard_error').fadeOut('fast');
            $(this).hide();
            importData();

        });
    }


    function changePercentByEntityType(currentProcess) {
        var processParentElement = $("#process-" + currentProcess.type);
        // console.log(processParentElement);
        // console.log(processParentElement.find(".subtitle").html());
        var percent = Math.round((currentProcess.imported / currentProcess.total) * 100);
        processParentElement.find(".subtitle").html("imported " + currentProcess.total + "/" + currentProcess.imported);
        processParentElement.find(".progress-bar").css("width", percent + "%");
        processParentElement.find(".value").html(percent + "%");
    }

})(jQuery);