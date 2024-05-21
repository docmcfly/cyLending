/*! CylancerCalendar |  GNU GENERAL PUBLIC LICENSE Version 2 */
class Calendar {

    properties = {

        maxEventBoxes: 7,
        // background color from today
        todayBgColor: '#ded9a1',

        // special color for weekend day out of focus
        primaryLightColor: '#d39c8c',

        // is the color for not important data. By example the other day from the next or the prevoious month.  
        outOfFocusColor: '#C0C0C0',

        // day box height
        dayBoxHeight: '8.2em',

        // font color of the weekend days
        weekendColor: 'var(--bs-primary)',

        // is the 
        appointmentSymbole: ' 🕗',

        // how many month you can switch in the past. (it exists no limit if the value less as one)
        maxPastMonth: 1,

        // how many month you can switch in the future. (it exists no limit if the value less as one)
        maxFutureMonth: 12,

        // default date formatter
        formatter: {
            dateOptions: {"year": "numeric", "month": "numeric", "day": "numeric"},
            timeOptions: {hour: "numeric", minute: "2-digit"},
        },



        previousMonthButtonHook: function (calendar) {},
        nextMonthButtonHook: function (calendar) {},

        // translations
        texts: {
            de: {
                daysOfWeek: ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'],
                daysOfWeekShort: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                btnNextButton: 'Nächster Monat',
                btnPreviousMonth: 'Vorheriger Monat',
                btnToday: 'Heute',
                appointmentsOfTheDay: 'Termine des Tages',
                monthNames: ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
            },
            en: {
                daysOfWeek: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                daysOfWeekShort: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                btnNextButton: 'Next month',
                btnPreviousMonth: 'Previous month',
                btnToday: 'Today',
                appointmentsOfTheDay: 'Appointments of the day',
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            }
        },
    }
    cssStyle() {
        return '\
    :root {\
        --primary-light: '+ this.properties.primaryLightColor + ';\
        --outOfFocus: '+ this.properties.outOfFocusColor + ';\
    }\
    .overflowHidden {\
         overflow: hidden !important;\
         text-overflow: ellipsis !important;\
         white-space: nowrap !important;\
    }\
    div > [data-empty="false"] {\
        cursor: pointer;\
      }\
    .notCurrentMonth{\
    	color: var(--outOfFocus); \
    }\
    .dateBox{\
    	height: '+ this.properties.dayBoxHeight + '; \
    	text-overflow: ellipsis;\
    	overflow: hidden;\
    }\
    [data-eventbox] {\
    	text-overflow: ellipsis;\
        overflow: hidden;\
    	margin: 0;\
    	margin-bottom: 1px;\
    }\
    .today{\
    	background-color: '+ this.properties.todayBgColor + ';\
    }\
    .weekday-5 .dateNumber, .weekday-6 .dateNumber{\
    	color: '+ this.properties.weekendColor + ';\
    }\
    .weekday-5.notCurrentMonth .dateNumber, .weekday-6.notCurrentMonth .dateNumber{\
    	color: var(--primary-light);\
    }\
    .withAppointment::after{\
        content:"'+ this.properties.appointmentSymbole + '"; \
    }\
    .content {\
        color: var(--bs-black);\
    }\
    '
    }

    today = new Date()
    currentDay = new Date();
    monthStartDate = null;
    monthEndDate = null;
    events = new Map()

    constructor(selector, language, properties) {
        this.properties = this.merge(this.properties, properties);
        this.selector = selector
        this.texts = this.merge(this.properties.texts['en'], this.properties.texts[language]);
        this.language = language
    }

    importEvents(events) {
        events = this.cleanEvents(events);

        for (let i = 0, l = events.length; i < l; i++) {
            let event = events[i]
            // get a negative index if the idx is not set.
            if (!('idx' in event) || event.idx < 0) {
                event.idx = -1 * (this.events.size + 1)
            }
            this.events.set(event.idx, event)
        }
        console.log(this.events.size)
        return this
    }

    merge(target, ...sources) {
        for (let source of sources) {
            for (let k in source) {
                let vs = source[k]
                let vt = target[k]
                if (Object(vs) == vs && Object(vt) === vt && !(vs instanceof Function) && !(vt instanceof Function)) {
                    target[k] = this.merge(vt, vs)
                    continue
                }
                target[k] = source[k]
            }
        }
        return target
    }

    t() {
        return this.texts
    }

    hasDateFormat(date) {
        return (/^\d{4}-[01]\d-[0-3]\d$/gm).test(date);
    }

    hasTimeFormat(time) {
        return (/^[0-2]\d:[0-5]\d:[0-5]\d$/gm).test(time)
    }

    hasDateTimeFormat(dateTime) {
        return (/^\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d$/gm).test(dateTime)
    }

    parseDate(date) {
        if (!this.hasDateFormat(date)) {
            return null;
        }
        let [year, month, day] = date.split("-")
        return new Date(year, month - 1, day, 0, 0, 0)
    }

    parseTime(time) {
        if (!this.hasTimeFormat(time)) {
            return null;
        }
        let [hour, minute] = time.split(":")
        return new Date(1970, 0, 1, hour, minute, 0)
    }

    parseMoment(dateTime) {
        if (this.hasDateFormat(dateTime)) {
            return this.parseDate(dateTime)
        }
        if (!(this.hasDateTimeFormat(dateTime))) {
            return null;
        }
        let [date, _time] = dateTime.split(" ")
        let [year, month, day] = date.split("-")
        let [hour, minute, second] = _time.split(":")
        return new Date(year, month - 1, day, hour, minute, 0)
    }

    equalsDate(date1, date2) {
        return (
            date1.getFullYear() === date2.getFullYear() &&
            date1.getMonth() === date2.getMonth() &&
            date1.getDate() === date2.getDate()
        );
    }

    cleanEvents(events) {

        let eventsCount = events.length
        for (let i = 0; i < eventsCount; i++) {
            let event = events[i]

            let start = this.parseMoment(event.start)
            let end = this.parseMoment(event.end)

            if (start === null || end === null) {
                event.valid = false
            }
            event.valid = true
            event.start = start
            event.end = end
        }
        return events
    }

    renderCalendar() {

        let cal = $(this.selector);
        cal.empty();
        let content = ''
        content += '<style>' + this.cssStyle() + '</style>' + "\n";
        content += '<div class="container mb-3">' + "\n";
        content += '    <div class="row mb-3 ">' + "\n";
        content += '        <div class="col-md-3 mb-3">' + "\n";
        content += '            <span class="currentMonth fs-1">month</span>' + "\n";
        content += '        </div>' + "\n"
        content += '        <div class="col-md-3 mb-3">' + "\n";
        content += '            <button class="btn btn-primary toToday overflowHidden" style="width:100%">' + this.t().btnToday + '</button>' + "\n";
        content += '        </div>' + "\n"
        content += '        <div class="col-md-3 col-sm-6  mb-3">' + "\n";
        content += '            <button class="btn btn-primary previousMonth overflowHidden" style="width:100%">' + this.t().btnPreviousMonth + '</button>' + "\n";
        content += '        </div>' + "\n"
        content += '        <div class="col-md-3 col-sm-6 mb-3">' + "\n";
        content += '            <button class="btn btn-primary nextMonth overflowHidden"  style="width:100%">' + this.t().btnNextButton + '</button> ' + "\n";
        content += '        </div>' + "\n"
        content += '    </div>' + "\n"
        content += '</div>' + "\n"
        content += '<div class="container">'
        content += '    <div class="row mb-3 ">' + "\n";
        content += '        <div class="col-md-9">' + "\n";
        content += '          <div class="container gx-0 month mb-4">' + "\n";
        content += '          </div>' + "\n"
        content += '        </div>' + "\n"
        content += '        <div class="col-md-3 ">' + "\n";
        content += '          <div class="container  gx-0 ">' + "\n";
        content += '            <div class="row mb-1 ">' + "\n";
        content += '                <div class="col "><h2 class="border-top border-bottom p-2">' + this.t().appointmentsOfTheDay + '</h2></div>'
        content += '            </div>' + "\n"
        content += '            <div class="row mb-3 ">' + "\n";
        content += '                <div class="container details ">' + "\n";
        content += '                </div>' + "\n"
        content += '            </div>' + "\n"
        content += '        </div>' + "\n"
        content += '    </div>' + "\n"
        content += '</div>' + "\n"
        cal.append(content)

        let btnNextMonth = $(this.selector + ' .btn.nextMonth')
        btnNextMonth.on('click', {calendar: this}, function (event) {
            let calendar = event.data.calendar
            calendar.properties.nextMonthButtonHook(calendar)
            calendar.currentDay.setMonth(calendar.currentDay.getMonth() + 1)
            calendar.renderMonth()
        })

        let btnPreviousMonth = $(this.selector + ' .btn.previousMonth')
        btnPreviousMonth.on('click', {calendar: this}, function (event) {

            let calendar = event.data.calendar
            calendar.properties.previousMonthButtonHook(calendar)
            calendar.currentDay.setMonth(calendar.currentDay.getMonth() - 1)
            calendar.renderMonth()
        })

        $(this.selector + ' .btn.toToday').on('click', {calendar: this}, function (event) {
            let calendar = event.data.calendar
            calendar.currentDay = new Date()
            calendar.currentDay.setDate(1)
            calendar.renderMonth()
            let today = $(".today").get(0)
            today.scrollIntoView({behavior: 'smooth'})
            calendar.updateDetails($(today.parentElement))
        })

        this.renderMonth()
    }


    updateButton(button, active) {
        button.prop("disabled", !active);
        if (active) {
            button.addClass('btn-primary')
            button.removeClass('btn-light')
        } else {
            button.removeClass('btn-primary')
            button.addClass('btn-light')
        }
    }


    renderMonth() {
        // calculate the first day in the month calendar view table: 
        // 1. copy the current day...
        let day = new Date(this.currentDay)
        // 2. set to the first day of the month
        day.setDate(1)
        // 3. subtract from the first month day the week day counter... 
        day.setDate(day.getDate() - ((day.getDay() + 6) % 7))


        let currentMonth = this.currentDay.getMonth();
        let currentMonthTag = $(this.selector + ' .currentMonth')
        currentMonthTag.text(this.t().monthNames[this.currentDay.getMonth()] + " " + this.currentDay.getFullYear());
        currentMonthTag.attr('data-month', this.currentDay.getMonth())
        currentMonthTag.attr('data-year', this.currentDay.getFullYear())

        let cal = $(this.selector + ' .month');
        cal.empty();
        let grid = '' // '<div class="container-fluid" style="border: 1px solid red">' + "\n"
        let hide = false;
        this.monthStartDate = new Date(day);
        for (let i = 0; i < 7; i++) {
            if (i > 1 && ((day.getMonth() > this.currentDay.getMonth() || day.getFullYear() > this.currentDay.getFullYear()) && day.getDay() == 1)) {
                hide = true;
            }
            if (!hide) {

                if (i === 0) { // header
                    grid += '<div class=" row gx-0 " data-week="' + i + '" >' + "\n"
                    for (let j = 0; j < 7; j++) {
                        grid += '<div class="col border-top d-none d-lg-block '
                        if (j > 0) {
                            grid += 'border-start '
                        }
                        grid += '" data-weekday="' + j + '" data-date="' + this.formatDate(day) + '">';
                        grid += '<div  class=" '
                        grid += 'dayBox px-2 weekday weekday-' + j + '" >'
                        grid += this.t().daysOfWeek[j]

                        grid += '</div></div>' + "\n"
                    }
                    grid += '</div>' + "\n"
                    grid += '<div class=" row gx-0" data-week="' + i + '" >' + "\n"
                    for (let j = 0; j < 7; j++) {
                        grid += '<div class="col border-top d-block d-lg-none '
                        if (j > 0) {
                            grid += 'border-start '
                        }
                        grid += '" data-weekday="' + j + '" data-date="' + this.formatDate(day) + '">';
                        grid += '<div  class=" '
                        grid += ' dayBox px-2 weekday weekday-' + j + '" >'
                        grid += this.t().daysOfWeekShort[j]

                        grid += '</div></div>' + "\n"
                    }
                    grid += '</div>' + "\n"
                } else { // day of the month...
                    grid += '<div class="row gx-0" data-week="' + i + '" >' + "\n"
                    for (let j = 0; j < 7; j++) {
                        grid += '<div class="overflowHidden col border-top  '
                        if (j > 0) {
                            grid += 'border-start '
                        }
                        grid += '" data-weekday="' + j + '" data-date="' + this.formatDate(day) + '">';
                        grid += '<div  class=" '
                        if (this.equalsDate(day, this.today)) {
                            grid += 'today '
                        }
                        if (currentMonth !== day.getMonth()) {
                            grid += 'notCurrentMonth '
                        }
                        grid += 'dateBox weekday-' + j + ' ">'
                        grid += '<span class="dateNumber  px-2">' + day.getDate() + '</span>'
                        day.setDate(day.getDate() + 1)
                        for (let k = 0; k < this.properties.maxEventBoxes; k++) {
                            grid += '<div  data-eventbox="' + k + '"><div class="content fs-6 overflowHidden p-0 px-1 m-1 me-2" >&nbsp;</div></div>'
                        }
                        grid += '</div></div>' + "\n"
                    }

                    grid += '</div>' + "\n"
                }

            }
        }
        this.monthEndDate = new Date(day);
        grid += '</div>' + "\n"
        cal.append(grid)

        let currentMonthDate = new Date(this.currentDay)
        currentMonthDate.setDate(15)

        if (this.properties.maxPastMonth > 0) {
            let maxPastMonth = new Date();
            maxPastMonth.setDate(20);
            maxPastMonth.setMonth(maxPastMonth.getMonth() - this.properties.maxPastMonth)
            this.updateButton($(this.selector + ' .btn.previousMonth'), maxPastMonth < currentMonthDate)
        } else {
            this.updateButton($(this.selector + ' .btn.previousMonth'), true)
        }

        if (this.properties.maxFutureMonth > 0) {
            let maxFutureMonth = new Date();
            maxFutureMonth.setDate(10);
            maxFutureMonth.setMonth(maxFutureMonth.getMonth() + this.properties.maxFutureMonth)
            this.updateButton($(this.selector + ' .btn.nextMonth'), currentMonthDate < maxFutureMonth)
        } else {
            this.updateButton($(this.selector + ' .btn.nextMonth'), true)
        }


        $(this.selector + ' [data-date]').on('click', {calendar: this}, function (event) {
            event.data.calendar.updateDetails($(this))
        })

        this.renderEvents()
    }

    idealTextColor(bgColor, striped) {
        if (striped === true) {
            return "#000000"
        }
        var nThreshold = 105;
        var components = this.getRGBComponents(bgColor);
        var bgDelta = (components.R * 0.299) + (components.G * 0.587) + (components.B * 0.114);

        return ((255 - bgDelta) < nThreshold) ? "#000000" : "#ffffff";
    }

    getRGBComponents(color) {

        var r = color.substring(1, 3);
        var g = color.substring(3, 5);
        var b = color.substring(5, 7);

        return {
            R: parseInt(r, 16),
            G: parseInt(g, 16),
            B: parseInt(b, 16)
        };
    }

    contains(event, date) {
        if (event.valid === false) {
            return false
        }
        let startDate = this.toDate(event.start)
        let endDate = this.toDate(event.end)
        let dateTime = date.getTime();
        return dateTime >= startDate.getTime() && dateTime <= endDate.getTime();
    }

    createTooltip(event) {
        return event.title
            + (event.description ? (' | ' + event.description) : '')
            + (event.responsible ? (' | ' + event.responsible) : '')
            + ' | '
            + event.start.toLocaleDateString(this.language, this.properties.formatter.dateOptions)
            + ' '
            + event.start.toLocaleTimeString(this.language, this.properties.formatter.timeOptions)
            + ' - '
            + event.end.toLocaleDateString(this.language, this.properties.formatter.dateOptions)
            + ' '
            + event.end.toLocaleTimeString(this.language, this.properties.formatter.timeOptions)
    }

    updateDetails(obj) {
        let d = obj.attr('data-date')
        let day = this.parseDate(d)
        let currentDay = this.formatDate(day);;
        let details = $(this.selector + ' .details')
        details.empty()
        let add = '<h3 class="p-2" >' + day.toLocaleDateString(this.language) + '</h3>' + "\n"
        let iter = this.events.entries()
        for (const [idx, event] of iter) {
            if (this.contains(event, day)) {
                let backgroundColor = event.backgroundColor
                add += '<div class="mb-2 p-1 text-dark" '
                add += 'title="' + this.createTooltip(event) + '" '
                add += 'style="'
                if (event.striped === true) {
                    add += 'background-image: ' + this.getStripedBackground(backgroundColor) + ';'
                } else {
                    add += 'background-color:' + backgroundColor + ';'
                }
                //  add += 'color:' + this.idealTextColor(backgroundColor, event.striped) + ';'
                add += '">' + "\n"
                add += '<div class="fw-bold px-1 bg-white me-5" >' + event.title + '</div>'
                add += '<div class="small overflowHidden  px-1 bg-white me-5">'
                if (event.responsible) {
                    add += event.responsible + '<br>'
                }
                if (event.description) {
                    add += event.description + '<br>'
                }
                let startDate = this.formatDate(event.start);
                let endDate = this.formatDate(event.end);
                if (startDate !== currentDay || endDate !== currentDay
                    || event.start.getHours() !== 0 || event.start.getMinutes() !== 0
                    || event.end.getHours() !== 0 || event.end.getMinutes() !== 0) {

                    if (startDate !== currentDay || (event.start.getHours() === 0 && event.start.getMinutes() === 0)) {
                        add += event.start.toLocaleDateString(this.language, this.properties.formatter.dateOptions) + ' '
                    }
                    if (event.start.getHours() !== 0 || event.start.getMinutes() !== 0) {
                        add += event.start.toLocaleTimeString(this.language, this.properties.formatter.timeOptions)
                    }
                    add += "&nbsp;-&nbsp;"
                    if (endDate !== currentDay) {
                        add += event.end.toLocaleDateString(this.language, this.properties.formatter.dateOptions) + ' '
                    }
                    if (event.end.getHours() !== 0 || event.end.getMinutes() !== 0) {
                        add += event.end.toLocaleTimeString(this.language, this.properties.formatter.timeOptions)
                    }
                }
                add += '</div>' + "\n"
                add += '</div>' + "\n"
            }
        }
        details.append(add)
        $(".details").get(0).scrollIntoView({behavior: 'smooth'});
    }

    renderEvents() {
        let iter = this.events.entries()
        for (const [idx, event] of iter) {
            this.renderEvent(event)
        }
    }

    toDate(dateTime) {
        return new Date(dateTime.getFullYear(), dateTime.getMonth(), dateTime.getDate());
    }

    renderEvent(event) {

        $('[data-eventbox][data-idx=' + event.idx + ']').each(function (index) {
            let eb = $(this)
            eb.attr('data-empty', 'true')
            eb.attr('data-idx', '')
            eb.attr('title', '')
            eb.html('<div class="content fs-6 overflowHidden p-0 px-1 m-1 me-2" >&nbsp;</div>')
        })

        let start = event.start
        let end = event.end

        if (event.valid === false) {
            return;
        }

        if (start.getTime() > end.getTime()) {
            return;
        }
        if (start.getTime() > this.monthEndDate.getTime() || end.getTime() < this.monthStartDate.getTime()) {
            return;
        }

        // end time is specified and it is 0:00 
        if (event.end.length > 10 && end.getHours() === 0 && end.getMinutes() === 0) {
            end.setDate(end.getDate() - 1) // display the event only to the day before.
        }


        let dc = 1;
        let startDate = this.toDate(start)
        let endDate = this.toDate(end)

        if (start.getTime() < this.monthStartDate.getTime()) {
            startDate = new Date(this.monthStartDate);
        }
        if (end.getTime() > this.monthEndDate.getTime()) {
            endDate = new Date(this.monthEndDate);
        }
        let tmp = new Date(startDate)
        while (tmp.getTime() < endDate.getTime()) {
            tmp.setDate(tmp.getDate() + 1)
            dc++
        }



        let row;
        for (let j = 0; j < this.properties.maxEventBoxes; j++) {
            row = j;
            tmp = new Date(startDate)
            let found = true
            for (let i = 0; i < dc || !found; i++) {
                if ($("[data-date='" + this.formatDate(tmp) + "'] [data-eventbox='" + j + "'").attr("data-empty") === "false") {
                    found = false
                    break;
                }
                tmp.setDate(tmp.getDate() + 1)
            }
            if (found) {
                break
            }
        }
        tmp = new Date(startDate)
        for (let i = 0; i < dc; i++) {
            let dateBox = $("[data-date='" + this.formatDate(tmp) + "']")
            let eventBox = dateBox.find("[data-eventbox='" + row + "']")
            let dateNumber = dateBox.find('span.dateNumber')
            dateNumber.addClass("withAppointment")
            if (i == 0) {
                let titleBox = eventBox.find('div.content')
                titleBox.addClass("bg-white")
                titleBox.text(this.createTooltip(event))
            }
            eventBox.attr('data-empty', 'false')
            eventBox.attr('data-idx', event.idx)
            eventBox.attr('title', this.createTooltip(event))
            if (event.striped === true) {
                eventBox.css("background-image", this.getStripedBackground(event.backgroundColor))
            } else {
                eventBox.css("background-color", event.backgroundColor)
            }
            // eventBox.css("color", this.idealTextColor(event.backgroundColor, event.striped))
            tmp.setDate(tmp.getDate() + 1)
        }
    }

    getStripedBackground(color) {
        return 'repeating-linear-gradient(45deg,transparent 0,transparent .2em, ' + color + ' .2em, ' + color + ' .4em, transparent .4em)'
    }

    formatDate(date) {
        return date.getFullYear().toString().padStart(4, '0') + '-' + (date.getMonth() + 1).toString().padStart(2, '0') + '-' + date.getDate().toString().padStart(2, '0')
    }

    formatTime(time) {
        return time.getHours().toString().padStart(2, '0') + ':' + (time.getMinutes()).toString().padStart(2, '0')
    }

}




