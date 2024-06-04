export var removeCommas = function (number) {
    return parseInt(number.replace(/,/g, ""));
};
export var formatNumber = function (number) {
    if (number === null) {
        return "0";
    }
    if (typeof number === "undefined") {
        return "0";
    }
    if (typeof number === "string") {
        return parseInt(number)
            .toFixed(0)
            .toString()
            .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    return number
        .toFixed(0)
        .toString()
        .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};
export var percent = function (number) {
    if (number === null) {
        return 0.0;
    }
    if (typeof number === "undefined") {
        return 0.0;
    }
    return parseInt((number * 100).toFixed(2));
};
//# sourceMappingURL=format-number.js.map
