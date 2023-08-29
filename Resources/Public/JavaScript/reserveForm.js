


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
    })

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
