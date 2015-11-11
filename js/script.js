// Surrounds the selected text with text1 and text2.
function surroundText(tag1, tag2, myarea) {
  if (document.selection) { //IE
    myarea.focus();
    var sel = document.selection.createRange();
    sel.text = tag1 + sel.text + tag2;
  } else { //Other Browsers
    var len = myarea.value.length;
    var start = myarea.selectionStart;
    var end = myarea.selectionEnd;
    var scrollTop = myarea.scrollTop;
    var scrollLeft = myarea.scrollLeft;
    var sel = myarea.value.substring(start, end);
    var rep = tag1 + sel + tag2;
    myarea.value = myarea.value.substring(0, start) + rep + myarea.value.substring(end, len);
    myarea.scrollTop = scrollTop;
    myarea.scrollLeft = scrollLeft;
  }
}

// Invert all checkboxes at once by clicking a single checkbox.
function invertAll(headerfield, checkform, mask) {
  for (var i = 0; i < checkform.length; i++) {
    if (typeof(checkform[i].name) === "undefined" || (typeof(mask) !== "undefined" && checkform[i].name.substr(0, mask.length) !== mask))
      continue;

    if (!checkform[i].disabled)
      checkform[i].checked = headerfield.checked;
  }
}

function uncheckglobal(headerfield, checkform) {
  checkform.mod_global.checked = false;
}

function wpf_confirm() {
  var answer = confirm('Are you sure you want to remove this?');
  if (!answer)
    return false;
  else
    return true;
}

//Make sure array has unique values
Array.prototype.mf_contains = function(v) {
  for(var i = 0; i < this.length; i++) {
    if(this[i] === v) return true;
  }

  return false;
};

Array.prototype.mf_unique = function() {
  var arr = [];

  for(var i = 0; i < this.length; i++) {
    if(!arr.mf_contains(this[i]))
      arr.push(this[i]);
  }

  return arr;
}

// Cookies management =3
function getCookie(c_name) {
  var i, x, y, ARRcookies = document.cookie.split(";");

  for (i = 0; i < ARRcookies.length; i++) {
    x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
    y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
    x = x.replace(/^\s+|\s+$/g, "");

    if (x === c_name)
      if(y === null || y == "")
        return [];
      else
        return y.split(',');
  }

  return [];
}

function setCookie(c_name, value, exdays) {
  value = value.join(',');
  var exdate = new Date();
  exdate.setDate(exdate.getDate() + exdays);
  var c_value = value + ((exdays === null) ? "" : "; expires=" + exdate.toUTCString());
  document.cookie = c_name + "=" + c_value;
}

function placeHolder(ele) {
  if (ele.value === ele.defaultValue)
    ele.value = '';
  else if (ele.value === '')
    ele.value = ele.defaultValue;
}
