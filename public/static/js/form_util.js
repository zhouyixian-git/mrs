Form = {
    formToMap : function(form) {
        var object = {};
        if (typeof form == 'string')
            form = document.getElementById(form);
        var elements = form.elements;
        for (var i = 0; i < elements.length; i++) {
            var element = elements[i];
            switch (element.type) {
                case "radio":
                    if (element.checked) {
                        object[element.name] = element.value
                    }
                    break;
                case "checkbox":
                    if (element.length == 1) {
                        if (element.checked)
                            object[element.name] = element.value;
                        else
                            object[element.name] = "";
                    } else {
                        if (!object[element.name]) {
                            object[element.name] = ""
                        }
                        ;
                        if (element.checked) {
                            object[element.name] += element.value + ",";
                        }
                    }
                    break;
                default:
                    if (element.type != "submit" && element.type != "button"
                        && element.type != "reset") {
                        object[element.name] = element.value;
                    }
                    break;
            }
        }
        return object;
    },
    mapToForm : function(form, map) {
        if (typeof form == 'string')
            form = document.getElementById(form);
        for (var i = 0; i < form.elements.length; i++) {
            var element = form.elements[i];
            if (typeof (map[element.name]) == 'undefined') {
                continue;
            }
            var val = map[element.name] + "";
            if (val == "null") {
                val = "";
            }

            switch (element.type) {
                case "text":
                case "textarea":
                case "hidden":
                case "password":
                    var regS = new RegExp("<br>", "g");
                    element.value = val.toString().replace(regS, "\n");
                    break;
                case "radio":
                case "checkbox":
                    if (val.indexOf(",") > -1)
                        element.checked = (val.indexOf(element.value) > -1);
                    else
                        element.checked = (element.value == val);
                    break;
                case "select-one":
                    $("#"+element.name).val(val);
                    var text = $("#"+element.name).find("option:selected").text();
                    $("#dk_container_"+element.name+" .dk_toggle").text(text);

                    break;
            }
        }
    },
    checkForm : function(form) {
        if (typeof form == 'string')
            form = document.getElementById(form);
        return doCheck(form);
    },
    formDisable : function(form){
        if (typeof form == 'string')
            form = document.getElementById(form);
        var elements = form.elements;
        for (var i = 0; i < elements.length; i++) {
            var element = elements[i];
            element.setAttribute("disabled","disabled");
        }
    },
    showWaiting : function(){
        $().showUiLoading();
    },
    hideWaiting : function(){
        $().hideUiLoading();
    },
    closeWaiting : function(){
        $().hideUiLoading();
    }
}
