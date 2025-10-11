const dateFormat = /^\d{4}-(((0[13578]|1[02])-(([012]\d)|3[01]))|((0[469]|11)-(([012]\d)|30))|02-[012]\d)T(([01]\d)|(2[0-4])):[0-5]\d$/m;

function hasDateTimeFormat(dateTime) {
    return (/^\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d$/gm).test(dateTime)
}

function parseMoment(dateTime) {
    let [date, _time] = dateTime.split("T")
    let [year, month, day] = date.split("-")
    let [hour, minute, second] = _time.split(":")
    return new Date(parseInt(year), parseInt(month) - 1, parseInt(day), parseInt(hour), parseInt(minute), 0)
}

function formatDate(date) {
    return date.getFullYear().toString().padStart(4, '0') + '-' + (date.getMonth() + 1).toString().padStart(2, '0') + '-' + date.getDate().toString().padStart(2, '0')
}

function formatTime(time) {
    return time.getHours().toString().padStart(2, '0') + ':' + (time.getMinutes()).toString().padStart(2, '0')
}


// qunatity handling
var updateMaxQty = function (select) {
    let objectQty = 1;
    let objectUid = -1;
    select.find("option").each(function () {
        if ($(this).is(':selected')) {
            objectQty = Math.max(parseInt($(this).attr('data-quantity')), objectQty)
            objectUid = parseInt($(this).attr('value'))
        }
    })

    let inputFrom = $('#from');
    let from = inputFrom.val();
    if (from !== undefined) {
        let error = objectUid == -1;

        if (from.match(dateFormat)) {
            from = from.substring(0, 10);
        } else {
            error = true;
        }

        let inputUntil = $('#until');
        let until = inputUntil.val();
        if (until.match(dateFormat)) {
            until = until.substring(0, 10);
        } else {
            error = true;
        }

        updateMaxQtyValue(objectQty)

        if (!error) {
            $.ajax({
                url: getMaxQuantityUrl,
                method: "POST",
                data: { object: objectUid, from: from, until: until },
            }
            )
                .fail(function (error) {
                    console.log(error.responseText)
                })
                .done(function (result) {
                    console.log(result)
                    updateMaxQtyValue(result.result)


                })
        }
    }
}


var updateMaxQtyValue = function (maxQty) {

    let qty = $('#quantity')
    qty.attr('max', maxQty)
    qty.val(Math.min(parseInt(qty.val()), maxQty))

}

// qunatity handling
var updateHighPriority = function (select) {
    let highPriorityAllowed = false;

    select.find("option").each(function () {
        if ($(this).is(':selected')) {
            $(this).attr('data-highprioritypossible') === '1' ? highPriorityAllowed = true : highPriorityAllowed = false
        }
    })
    let hp = $('#highPriority')
    hp.prop('disabled', !highPriorityAllowed)
    if (!highPriorityAllowed) {
        hp.prop('checked', false)
    }

}

var updateApproval = function (e) {
    $.ajax({
        url: getAvailabilityRequestsUri,
        method: "POST",
        data: {},
    }
    )
        .fail(function (error) {
            console.log(error.responseText)
        })
        .done(function (result) {
            let form = $($(e).attr('data-bs-target'))
            form.html(result.availabilityRequestsRender)
            updateApprovalButtons(form)
        })
}

var updateButtons = function (form) {
    let _form = $(form)
    _form.find("button.formAction").on('click', function (e) {
        let submit = $(this)
        submit.prop('disabled', true)
        let uri = submit.attr('data-uri')
        let confirmedWarnings = submit.parent().find('div.messages').find('input.warning-checkbox').filter(':checked').map(function () { return this.value; }).get();
        $.ajax({
            url: uri,
            method: "POST",
            data: {
                'tx_cylending_lending[confirmedWarnings][]': confirmedWarnings
            },
        }
        )
            .fail(function (error) {
                submit.parent().find('div.messages').html(error.responseText)
            })
            .done(function (response) {
                console.log(response)
                if (response.result) {
                    submit.parents('div.form').first().html('<div class="row mt-4 border-bottom"><div class="col">' + response.rendering + '</div></div>')
                } else {
                    let messages = submit.parent().find('div.messages')
                    messages.html(response.rendering)
                    if (messages.find('[data-errorkey]').length == 0) {
                        let checkboxes = messages.find('[data-warningkey] input.warning-checkbox');
                        checkboxes.on('change', function (e) {
                            submit.prop('disabled', !(checkboxes.length > 0 && checkboxes.filter(':checked').length === checkboxes.length))
                        })
                    }
                }
            })
    })
    if (_form.attr('role') == 'lending') {
        _form.find("form").on("submit", function (event) {
            event.preventDefault()
            let _form = $(this)
            let data = $(this).serializeArray()
            $.ajax({
                url: _form.attr('action'),
                method: "POST",
                data: data,
            }
            ).fail(function (error) {
                _form.find('div.messages').html(error.responseText)
            }).done(function (response) {
                console.log(response)
                _form.find('div.messages').html(response.rendering)
                populateForm(_form, response.toReserve)
                updateMaxQty($('#object'))
                updateHighPriority($('#object'))
            })
        })
        $('#object').on('change', function () {
            updateMaxQty($(this))
            updateHighPriority($(this))
        }
        )
        $('#from').change(
            function () {
                let fv = $(this).val()
                if (hasDateTimeFormat(fv)) {
                    let f = parseMoment(fv)
                    let [hours, minutes] = untilOffset.split(":")
                    f.setHours(f.getHours() + parseInt(hours))
                    f.setMinutes(f.getMinutes() + parseInt(minutes))
                    $('#until').val(formatDate(f) + 'T' + formatTime(f));
                    updateMaxQty($('#object'))
                }
            }

        )
        $('#until').change(
            function () {
                updateMaxQty($('#object'))
            }
        )
    }
}

var updateCalendar = function (e) {
    $($(e).attr('data-bs-target')).find('#calendar').first().data('calendar').updateMonth()
}

$('button.nav-link[data-uri]').on("click", function () {
    let button = $(this)
    let refreshMode = button.attr('data-refresh')
    if (refreshMode !== 'off') {
        if (refreshMode == 'single') {
            button.attr('data-refresh', 'off')
        }
        $.ajax({
            url: button.attr("data-uri"),
            method: "POST",
            data: {},
        }
        ).fail(function (error) {
            console.log(error.responseText)
        }).done(function (result) {
            let form = $(button.attr('data-bs-target'))
            form.html(result.rendering)
            updateButtons(form);
        })
    }
})

$('button.nav-link[data-visible-uri]').each(function (e) {
    let button = $(this)
    $.ajax({
        url: button.attr("data-visible-uri"),
        method: "POST",
        data: {},
    }
    ).fail(function (error) {
        console.log(error.responseText)
    }).done(function (response) {
        if (response.isVisible) {
            button.removeClass('d-none')
        }
    })
})


function getByBracketPath(root, path) {
    const keys = path.substring(1, path.length - 1)
        .replace('][', ',')
        .split(',');
    let val = undefined
    for (const key of keys) {
        val = root[key];
        if (val == null) return undefined;
        if (Array.isArray(val) && val.length === 0) {
            break;
        }
    }
    if (typeof val === 'object' && 'uid' in val) {
        return val.uid
    } else {
        return val;
    }
}



function populateForm($form, data) {
    const $el = $form.find('[name^="tx_cylending_lending[toReserve]"]').each(function (e) {
        let $el = $(this)
        let bracketPath = $el.attr('name').substring("tx_cylending_lending[toReserve]".length);
        let value = getByBracketPath(data, bracketPath)
        const type = $el.attr('type');
        const tag = $el.prop('tagName').toLowerCase();

        if (tag === 'select') {
            // Mehrfachauswahl oder Single
            if ($el.prop('multiple') && Array.isArray(value)) {
                $el.val(value);
            } else {
                $el.val(value);
            }
        } else if (type === 'checkbox') {
            if ($el.length > 1) {
                // mehrere checkboxes mit gleichem name
                if (!Array.isArray(value)) {
                    // wenn einzelner boolean erwartet wird
                    $el.prop('checked', !!value);
                } else {
                    $el.each(function () {
                        $(this).prop('checked', value.indexOf($(this).val()) !== -1);
                    });
                }
            } else {
                // einzelne checkbox -> boolean
                $($el).prop('checked', !!value);
            }
        } else if (type === 'radio') {
            $el.each(function () {
                $(this).prop('checked', $(this).val() == value);
            });
        } else if (type !== 'hidden') {
            // input[type=text|email|hidden|number], textarea, etc.
            $el.val(value);
        }
    });

}

