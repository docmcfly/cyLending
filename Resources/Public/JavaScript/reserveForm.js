const dateFormat = /^\d{4}-(((0[13578]|1[02])-(([012]\d)|3[01]))|((0[469]|11)-(([012]\d)|30))|02-[012]\d)T(([01]\d)|(2[0-4])):[0-5]\d$/m;

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

$("form input[type='submit']").on('click', function (e) {
    let submit = $(this)
    submit.prop('disabled', true)
    submit.val('⌛ ' + submit.val())
    submit.parents("form").submit()
})

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

$('#object').on('change', function () {
    updateMaxQty($(this))
    updateHighPriority($(this))

}
)

updateMaxQty($('#object'))
updateHighPriority($('#object'))


