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
var BasicClosableCard = (function (_super) {
    __extends(BasicClosableCard, _super);
    function BasicClosableCard(props) {
        return _super.call(this, props) || this;
    }
    BasicClosableCard.prototype.appendAdditionalClasses = function () {
        if (this.props.additionalClasses) {
            return this.props.additionalClasses;
        }
        return "";
    };
    BasicClosableCard.prototype.render = function () {
        return (React.createElement("div", { className: "bg-white rounded-sm drop-shadow-md p-6 dark:bg-gray-800 dark:text-gray-400 " +
                this.appendAdditionalClasses() },
            React.createElement("div", { className: "text-right cursor-pointer text-red-500 relative top-[10px] right-[20px]" },
                React.createElement("button", { onClick: this.props.close_action },
                    React.createElement("i", { className: "fas fa-minus-circle" }))),
            this.props.children));
    };
    return BasicClosableCard;
}(React.Component));
export default BasicClosableCard;
//# sourceMappingURL=basic-closable-card.js.map