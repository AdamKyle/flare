import React from "react";
export var renderIcons = function (index, icons) {
    var icon = icons[index];
    return React.createElement(
        "div",
        { className: "text-center mb-10" },
        React.createElement("i", {
            className: icon.icon + " text-7xl",
            style: { color: icon.color },
        }),
        React.createElement("p", { className: "text-lg mt-2" }, icon.title),
    );
};
//# sourceMappingURL=render-icons.js.map
