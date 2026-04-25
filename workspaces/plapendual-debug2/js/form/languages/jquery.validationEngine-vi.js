(function($){
  $.fn.validationEngineLanguage = function(){
  };
  $.validationEngineLanguage = {
    newLang: function(){
      $.validationEngineLanguage.allRules = {
        "required": { // Add your regex rules here, you can take telephone as an example
          "regex": "none",
          "alertText": "* Vui lòng nhập nội dung",
          "alertTextCheckboxMultiple": "* Vui lòng chọn",
          "alertTextCheckboxe": "* Vui lòng chọn",
          "alertTextDateRange": "* Vui lòng chọn ngày"
        },
        "requiredInFunction": { 
          "func": function(field, rules, i, options){
            return (field.val() == "test") ? true : false;
          },
          "alertText": "* Dữ liệu phải đúng"
        },
        "dateRange": {
          "regex": "none",
          "alertText": "* Ngày ",
          "alertText2": "Không hợp lệ"
        },
        "dateTimeRange": {
          "regex": "none",
          "alertText": "* Ngày giờ ",
          "alertText2": "Không hợp lệ"
        },
        "minSize": {
          "regex": "none",
          "alertText": "* Nhập tối thiểu ",
          "alertText2": " ký tự"
        },
        "maxSize": {
          "regex": "none",
          "alertText": "* Nhập tối đa ",
          "alertText2": " ký tự"
        },
        "groupRequired": {
          "regex": "none",
          "alertText": "* Vui lòng phải nhập nội dung",
          "alertTextCheckboxMultiple": "* Vui lòng chọn",
          "alertTextCheckboxe": "* Vui lòng chọn"
        },
        "min": {
          "regex": "none",
          "alertText": "* Giá trị nhỏ nhất là "
        },
        "max": {
          "regex": "none",
          "alertText": "* Giá trị lớn nhất là "
        },
        "past": {
          "regex": "none",
          "alertText": "* Ngày phải trước "
        },
        "future": {
          "regex": "none",
          "alertText": "* Ngày phải sau "
        },  
        "maxCheckbox": {
          "regex": "none",
          "alertText": "* Chỉ được chọn nhiều nhất ",
          "alertText2": " tùy chọn"
        },
        "minCheckbox": {
          "regex": "none",
          "alertText": "* Chọn ít nhất ",
          "alertText2": " tùy chọn"
        },
        "equals": {
          "regex": "none",
          "alertText": "* Dữ liệu không đúng"
        },
        "creditCard": {
          "regex": "none",
          "alertText": "* Thẻ tín dụng không đúng"
        },
        "phone": {
          // credit: jquery.h5validate.js / orefalo
          "regex": /^([\+][0-9]{1,3}([ \.\-])?)?([\(][0-9]{1,6}[\)])?([0-9 \.\-]{1,32})(([A-Za-z \:]{1,11})?[0-9]{1,4}?)$/,
          "alertText": "* Điện thoại không đúng"
        },
        "email": {
          // HTML5 compatible email regex ( http://www.whatwg.org/specs/web-apps/current-work/multipage/states-of-the-type-attribute.html#    e-mail-state-%28type=email%29 )
          "regex": /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
          "alertText": "* Email không đúng"
        },
        "fullname": {
          "regex":/^([a-zA-Z]+[\'\,\.\-]?[a-zA-Z ]*)+[ ]([a-zA-Z]+[\'\,\.\-]?[a-zA-Z ]+)+$/,
          "alertText":"* Phải nhập đầy đủ họ và tên"
        },
        "zip": {
          "regex":/^\d{5}$|^\d{5}-\d{4}$/,
          "alertText":"* Mã bưu điện không đúng"
        },
        "integer": {
          "regex": /^[\-\+]?\d+$/,
          "alertText": "* Số không hợp lệ"
        },
        "number": {
          // Number, including positive, negative, and floating decimal. credit: orefalo
          "regex": /^[\-\+]?((([0-9]{1,3})([,][0-9]{3})*)|([0-9]+))?([\.]([0-9]+))?$/,
          "alertText": "* Số không hợp lệ"
        },
        "date": {                    
          //  Check if date is valid by leap year
      "func": function (field) {
          var pattern = new RegExp(/^(\d{4})[\/\-\.](0?[1-9]|1[012])[\/\-\.](0?[1-9]|[12][0-9]|3[01])$/);
          var match = pattern.exec(field.val());
          if (match == null)
             return false;
  
          var year = match[1];
          var month = match[2]*1;
          var day = match[3]*1;         
          var date = new Date(year, month - 1, day); // because months starts from 0.
  
          return (date.getFullYear() == year && date.getMonth() == (month - 1) && date.getDate() == day);
        },                    
       "alertText": "* Ngày tháng không đúng định dạng YYYY-MM-DD"
        },
        "ipv4": {
          "regex": /^((([01]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))[.]){3}(([0-1]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))$/,
          "alertText": "* Địa chỉ IP không đúng"
        },
        "url": {
          "regex": /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i,
          "alertText": "* Địa chỉ trang web không đúng"
        },
        "onlyNumberSp": {
          "regex": /^[0-9\ ]+$/,
          "alertText": "* Chỉ được nhập số"
        },
        "onlyLetterSp": {
          "regex": /^[a-zA-Z\ \']+$/,
          "alertText": "* Chỉ được nhập chữ"
        },
        "onlyLetterVietnamese": {
          "regex": /^([a-zA-Z]|\u00C0|\u00C1|\u00C2|\u00C3|\u00C8|\u00C9|\u00CA|\u00CC|\u00CD|\u00D2|\u00D3|\u00D4|\u00D5|\u00D9|\u00DA|\u00DD|\u00E0|\u00E1|\u00E2|\u00E3|\u00E8|\u00E9|\u00EA|\u00EC|\u00ED|\u00F2|\u00F3|\u00F4|\u00F5|\u00F9|\u00FA|\u00FD|\u0102|\u0103|\u0110|\u0111|\u0128|\u0129|\u0168|\u0169|\u01A0|\u01A1|\u01AF|\u01B0|\u1EA0|\u1EA1|\u1EA2|\u1EA3|\u1EA4|\u1EA5|\u1EA6|\u1EA7|\u1EA8|\u1EA9|\u1EAA|\u1EAB|\u1EAC|\u1EAD|\u1EAE|\u1EAF|\u1EB0|\u1EB1|\u1EB2|\u1EB3|\u1EB4|\u1EB5|\u1EB6|\u1EB7|\u1EB8|\u1EB9|\u1EBA|\u1EBB|\u1EBC|\u1EBD|\u1EBE|\u1EBF|\u1EC0|\u1EC1|\u1EC2|\u1EC3|\u1EC4|\u1EC5|\u1EC6|\u1EC7|\u1EC8|\u1EC9|\u1ECA|\u1ECB|\u1ECC|\u1ECD|\u1ECE|\u1ECF|\u1ED0|\u1ED1|\u1ED2|\u1ED3|\u1ED4|\u1ED5|\u1ED6|\u1ED7|\u1ED8|\u1ED9|\u1EDA|\u1EDB|\u1EDC|\u1EDD|\u1EDE|\u1EDF|\u1EE0|\u1EE1|\u1EE2|\u1EE3|\u1EE4|\u1EE5|\u1EE6|\u1EE7|\u1EE8|\u1EE9|\u1EEA|\u1EEB|\u1EEC|\u1EED|\u1EEE|\u1EEF|\u1EF0|\u1EF1|\u1EF2|\u1EF3|\u1EF4|\u1EF5|\u1EF6|\u1EF7|\u1EF8|\u1EF9|\ )+$/,
          "alertText": "* Chỉ được nhập chữ"
        },
        "onlyLetterAccentSp":{
          "regex": /^[a-z\u00C0-\u017F\ ]+$/i,
          "alertText": "* Chỉ được nhập chữ"
        },
        "onlyLetterNumber": {
          "regex": /^[0-9a-zA-Z]+$/,
          "alertText": "* Chỉ được nhập số và chữ"
        },
        // --- CUSTOM RULES -- Those are specific to the demos, they can be removed or changed to your likings
        "ajaxUserCall": {
          "url": "ajaxValidateFieldUser",
          // you may want to pass extra data on the ajax call
          "extraData": "name=eric",
          "alertText": "* This user is already taken",
          "alertTextLoad": "* Validating, please wait"
        },
        "ajaxUserCallPhp": {
          "url": "phpajax/ajaxValidateFieldUser.php",
          // you may want to pass extra data on the ajax call
          "extraData": "name=eric",
          // if you provide an "alertTextOk", it will show as a green prompt when the field validates
          "alertTextOk": "* This username is available",
          "alertText": "* This user is already taken",
          "alertTextLoad": "* Validating, please wait"
        },
        "ajaxNameCall": {
          // remote json service location
          "url": "ajaxValidateFieldName",
          // error
          "alertText": "* This name is already taken",
          // if you provide an "alertTextOk", it will show as a green prompt when the field validates
          "alertTextOk": "* This name is available",
          // speaks by itself
          "alertTextLoad": "* Validating, please wait"
        },
         "ajaxNameCallPhp": {
            // remote json service location
            "url": "phpajax/ajaxValidateFieldName.php",
            // error
            "alertText": "* This name is already taken",
            // speaks by itself
            "alertTextLoad": "* Validating, please wait"
          },
        "validate2fields": {
          "alertText": "* Please input HELLO"
        },
        //tls warning:homegrown not fielded 
        "dateFormat":{
          "regex": /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(?:(?:0?[1-9]|1[0-2])(\/|-)(?:0?[1-9]|1\d|2[0-8]))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(0?2(\/|-)29)(\/|-)(?:(?:0[48]00|[13579][26]00|[2468][048]00)|(?:\d\d)?(?:0[48]|[2468][048]|[13579][26]))$/,
          "alertText": "* Ngày không đúng định dạng"
        },
        //tls warning:homegrown not fielded 
        "dateTimeFormat": {
          "regex": /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1}$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^((1[012]|0?[1-9]){1}\/(0?[1-9]|[12][0-9]|3[01]){1}\/\d{2,4}\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1})$/,
          "alertText": "* Invalid Date or Date Format",
          "alertText2": "Expected Format: ",
          "alertText3": "mm/dd/yyyy hh:mm:ss AM|PM or ", 
          "alertText4": "yyyy-mm-dd hh:mm:ss AM|PM"
        },
        "zipcode": {
          "regex": /^[0-9\-]+$/,
          "alertText": "* Chỉ được nhập số và dấu -"
        }
      };
      
    }
  };

  $.validationEngineLanguage.newLang();
  
})(jQuery);
