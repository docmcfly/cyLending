
$('#from').change(
    function () {
        let fv = $(this).val()
        if (hasDateTimeFormat(fv)) {
            let f = parseMoment(fv)
            let [hours, minutes] = untilOffset.split(":")
            f.setHours(f.getHours() + parseInt(hours))
            f.setMinutes(f.getMinutes() + parseInt(minutes))
            $('#until').val(formatDate(f) + 'T' + formatTime(f));
        }
        let submit = $('form.reserve button.submit')
        resetSumbit(submit)
        updateSubmitLocks(submit)
    }
)

$('#until').change(
    function () {
        let submit = $('form.reserve button.submit')
        resetSumbit(submit)
        updateSubmitLocks(submit)
    }
)


function hasDateTimeFormat(dateTime) {
    return (/^\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d$/gm).test(dateTime)
}


function parseTime(time) {
    return time.split(":")
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

function isLocked(submit, key) {
    return submit.data(key) == undefined ? false : (submit.data(key) === 'locked' || !submit.data(key).prop('checked'))
}


function isSubmitLocked(submit) {
    let a = isLocked(submit, 'existOverlappingLendingsCheckBox')
    let b = isLocked(submit, 'reasonsForPreventionCheckBox')
    return isLocked(submit, 'existOverlappingLendingsCheckBox') || isLocked(submit, 'reasonsForPreventionCheckBox');
}


function fireSubmit(submit) {
    if (!isSubmitLocked(submit)) {
        submit.parents("form.reserve").submit()
    }
}

function updateSubmitLocks(submit) {
    if (isSubmitLocked(submit)) {
        submit.prop('disabled', true)
        submit.text('⌛' + submit.data('text'))
    } else {
        submit.prop('disabled', false)
        submit.text(submit.data('text'))
    }
}

const dateFormat = /^\d{4}-(((0[13578]|1[02])-(([012]\d)|3[01]))|((0[469]|11)-(([012]\d)|30))|02-[012]\d)T(([01]\d)|(2[0-4])):[0-5]\d$/m;

function getValidDate(dateValue) {
    if (dateValue.match(dateFormat)) {
        return dateValue.substring(0, 10);
    }
    return false
}

function getValidDateTime(dateValue) {
    if (dateValue.match(dateFormat)) {
        return dateValue.replace('T', ' ');
    }
    return false
}

const ERROR = 1
const OK = 2
const NO_CHANGE_OK = 3

function prepareToSubmit(submit) {
    let previousDatesOk = submit.data('datesOk')
    if (previousDatesOk) {
        let from = getValidDateTime($('#from').val())
        let until = getValidDateTime($('#until').val())
        if (from === false || until === false) {
            submit.data('datesOk', false)
            return ERROR
        } else {
            let previousFrom = submit.data('from')
            let previousUntil = submit.data('until')

            submit.data('from', from)
            submit.data('until', until)
            return previousFrom === from && previousUntil === until ? NO_CHANGE_OK : OK
        }
    } else {
        let from = getValidDateTime($('#from').val())
        let until = getValidDateTime($('#until').val())
        if (from === false || until === false) {
            submit.data('datesOk', false)
            return ERROR
        } else {
            submit.data('from', from)
            submit.data('until', until)
            submit.data('datesOk', true)
            return OK
        }
    }
}



$('form.reserve button.submit').each(function () {

    let btn = $(this)
    btn.data('text', btn.text())
    btn.on("click", function (event) {
        let submit = $(this)
        let ps = prepareToSubmit(submit)
        switch (ps) {
            case ERROR:
                return
            case NO_CHANGE_OK:
            case OK:
                event.preventDefault();
                event.stopPropagation();
                secondAjaxCheck(submit, ps)
                break;
        }
    })
})

let tmpl = '    <div class="row  mb-2"> \
                    <div class="col-sm-3">###label###</div>\
                    <div class="col-sm-9">###content###</div>\
                </div>'

function resetSumbit(submit) {
    submit.removeData('existOverlappingLendingsCheckBox')
    let existOverlappingLendings = $('div.existOverlappingLendings')
    existOverlappingLendings.empty()

    let reasonsForPrevention = $('div.reasonsForPrevention')
    reasonsForPrevention.empty()
    submit.removeData('reasonsForPreventionCheckBox')
}


function secondAjaxCheck(submit, ps) {

    // forget the previous checkboxes
    if (ps != NO_CHANGE_OK) {
        resetSumbit(submit)
    }

    let from = submit.data('from')
    let until = submit.data('until')


    if (existOverlappingLendingsUrl && submit.data('existOverlappingLendingsCheckBox') == undefined) {
        submit.data('existOverlappingLendingsCheckBox', 'locked')
        updateSubmitLocks(submit)
        // check event overlapping
        $.ajax({
            url: existOverlappingLendingsUrl,
            method: "POST",
            data: {from: from, until: until},
        }
        ).fail(function (error) {
            console.log(error.responseText)
            //  $('div#debugOutput').html(error.responseText)
        }).done(function (result) {
            console.log(result)
           //  $('div#debugOutput').html(result)
            if (JSON.parse(result).result === true) {
                let existOverlappingLendings = $('div.existOverlappingLendings')
                existOverlappingLendings.empty()
                let check = tmpl
                    .replace('###label###', '&nbsp;')
                    .replace('###content###', '<input id="existOverlappingLendingsIgnore" type="checkbox" class="form-check-input" />&nbsp;<label class="d-inline form-check-label" for="existOverlappingLendingsIgnore">' + ignoreExistLendings + '</label>')
                existOverlappingLendings.append(check)
                let checkbox = $('#existOverlappingLendingsIgnore')
                checkbox.data('name', 'existOverlappingLendingsCheckBox')
                checkbox.change(function (event) {
                    updateSubmitLocks(submit)
                })
                submit.data('existOverlappingLendingsCheckBox', checkbox)

            } else {
                submit.removeData('existOverlappingLendingsCheckBox')
                fireSubmit(submit)
            }
        })
    }



    if (reasonsForPreventionServiceUrl && submit.data('reasonsForPreventionCheckBox') == undefined) {
        submit.data('reasonsForPreventionCheckBox', 'locked')
        updateSubmitLocks(submit)
        // check reasons for prevention
        $.ajax({
            url: reasonsForPreventionServiceUrl,
            method: "POST",
            data: {from: from.substring(0, 10), until: until.substring(0, 10)},
        }).fail(function (error) {
            console.log(error.responseText)
            //  $('div#debugOutput').html(error.responseText)
        }).done(function (result) {
            console.log(result)
            // $('div#debugOutput').html(result)
            let data = JSON.parse(result).data
            let dataCount = data.length
            if (dataCount > 0) {
                let reasonsForPrevention = $('div.reasonsForPrevention')
                reasonsForPrevention.empty()
                let check = tmpl
                    .replace('###label###', '&nbsp;')
                    .replace('###content###', '<input id="reasonsForPreventionIgnore" type="checkbox" class="form-check-input" />&nbsp;<label class="form-check-label" for="reasonsForPreventionIgnore" >' + reasonsForPreventionIgnore + '</label>')
                reasonsForPrevention.append(check)
                let reasons = '<ul>'
                for (let i = 0; i < dataCount; i++) {
                    reasons += '<li>' + data[i].description + '</li>'
                }
                reasons += '</ul>'
                reasonsForPrevention.append(tmpl
                    .replace('###label###', '<div class="error">' + reasonsForPreventionFound + '</div>')
                    .replace('###content###', reasons))

                let checkbox = $('#reasonsForPreventionIgnore')
                checkbox.change(function (event) {
                    updateSubmitLocks(submit)
                })
                submit.data('reasonsForPreventionCheckBox', checkbox)
            } else {
                submit.removeData('reasonsForPreventionCheckBox')
                fireSubmit(submit)
            }

        })

    }

    fireSubmit(submit);


}

// qunatity handling
var updateMaxQty = function (select) {
    let objectQty = 1;

    select.children("option").each(function () {
        if ($(this).is(':selected')) {
            objectQty = Math.max(parseInt($(this).attr('data-quantity')), objectQty)
        }
    })
    let qty = $('#quantity')
    qty.attr('max', objectQty)
    qty.val(Math.min(parseInt(qty.val()), objectQty))

}

$('#object').on('change', function () {updateMaxQty($(this))})
updateMaxQty($('#object'))

