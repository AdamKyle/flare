var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
import React from "react";
var AttackButton = (function (_super) {
    __extends(AttackButton, _super);
    function AttackButton(props) {
        return _super.call(this, props) || this;
    }
    AttackButton.prototype.render = function () {
        return (React.createElement("button", { type: "button", className: "w-10 h-10 mx-2 " + this.props.additional_css, onClick: this.props.on_click, disabled: this.props.disabled },
            React.createElement("i", { className: this.props.icon_class })));
    };
    return AttackButton;
}(React.Component));
export default AttackButton;
//# sourceMappingURL=attack-button.js.map