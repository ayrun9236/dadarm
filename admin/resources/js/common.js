function showMessage(text){
    alert(text);
}

function getDateFormat(date, format = 'YYYY-MM-DD'){
    return (date) ? moment(date).format(format) : '';
}

Number.prototype.number_format = function () {
    if (this == 0) return 0;

    var reg = /(^[+-]?\d+)(\d{3})/;
    var n = (this + '');

    while (reg.test(n)) n = n.replace(reg, '$1' + ',' + '$2');

    return n;
};