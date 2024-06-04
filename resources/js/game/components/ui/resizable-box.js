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
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
import React from "react";
import { ResizableBox as ReactResizableBox } from "react-resizable";
var ResizableBox = (function (_super) {
    __extends(ResizableBox, _super);
    function ResizableBox(props) {
        var _this = _super.call(this, props) || this;
        _this.onResize = function (event, _a) {
            var element = _a.element, size = _a.size, handle = _a.handle;
            _this.setState({ width: size.width, height: size.height });
        };
        _this.state = {
            width: _this.props.width,
            height: _this.props.height,
        };
        return _this;
    }
    ResizableBox.prototype.render = function () {
        return (React.createElement("div", null,
            React.createElement(ReactResizableBox, { width: this.state.width, height: this.state.height, onResize: this.onResize },
                React.createElement("div", { style: __assign(__assign({}, this.props.style), { width: this.state.width + "px", height: this.state.height + "px" }), className: this.props.additional_css }, this.props.children))));
    };
    return ResizableBox;
}(React.Component));
export default ResizableBox;
//# sourceMappingURL=resizable-box.js.map