const dateFormat = /^\d{4}-(((0[13578]|1[02])-(([012]\d)|3[01]))|((0[469]|11)-(([012]\d)|30))|02-[012]\d)T(([01]\d)|(2[0-4])):[0-5]\d$/m;

$('form.reserve button.submit').each(function(){
    let btn = $(this)
    btn.data('text', btn.text())
    btn.on("click", function (event) {
        let submit = $(this)
        
        event.preventDefault();
        event.stopPropagation();

        addSubmitLock(submit)

        let inputFrom = $('#from');
        let from = inputFrom.val();

        let error = false;
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

        if (!error) {
            submit.prop('disabled', true)
            submit.text('⌛' + submit.data('text'))



            $.ajax({
                url: reasonsForPreventionServiceUrl,
                method: "POST",
                data: { from: from, until: until },
            }
            )
                .fail(function (error) {
                    console.log(error.responseText)
                    //  $('div#debugOutput').html(error.responseText)
                })
                .done(function (result) {
                    console.log(result)
                    // $('div#debugOutput').html(result)

                    let tmpl = '  <div class="row  mb-2"> \
                            <div class="col-sm-3">###label###</div>\
                            <div class="col-sm-9">###content###</div>\
                         </div>'

                    let data = JSON.parse(result).data
                    let dataCount = data.length
                    if (dataCount > 0) {
                        let checkbox = $('input#reasonsForPreventionIgnore')
                        if (checkbox.length > 0 && checkbox[0].checked) {
                            $('form.reserve').submit()
                        } else {
                            submit.prop('disabled', false)
                            submit.text(submit.data('text'))
                            let reasonsForPrevention = $('div.reasonsForPrevention')
                            reasonsForPrevention.empty()
                            let check = tmpl
                                .replace('###label###', '&nbsp;')
                                .replace('###content###', '<input id="reasonsForPreventionIgnore" type="checkbox" />&nbsp;<label for="reasonsForPreventionIgnore" class="' + (checkbox.length > 0 ? 'error' : '') + '">' + reasonsForPreventionIgnore + '</label>')
                            reasonsForPrevention.append(check)
                            let reasons = '<ul>'
                            for (let i = 0; i < dataCount; i++) {
                                reasons += '<li>' + data[i].description + '</li>'
                            }
                            reasons += '</ul>'
                            reasonsForPrevention.append(tmpl
                                .replace('###label###', '<div class="error">' + reasonsForPreventionFound + '</div>')
                                .replace('###content###', reasons))
                        }
                    } else {
                        $('form.reserve').submit()
                    }
                })

        }


    })
});    