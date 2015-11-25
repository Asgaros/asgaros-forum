function wpf_confirm() {
    var answer = confirm('Are you sure you want to remove this?');

    if (!answer) {
        return false;
    } else {
        return true;
    }
}

function placeHolder(ele) {
    if (ele.value === ele.defaultValue) {
        ele.value = '';
    } else if (ele.value === '') {
        ele.value = ele.defaultValue;
    }
}
