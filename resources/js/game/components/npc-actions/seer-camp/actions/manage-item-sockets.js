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
import React, { Fragment } from "react";
import Select from "react-select";
var ManageItemSockets = (function (_super) {
    __extends(ManageItemSockets, _super);
    function ManageItemSockets(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            selected_item_slot_id: 0,
        };
        return _this;
    }
    ManageItemSockets.prototype.setSelectedItem = function (data) {
        var _this = this;
        this.setState({
            selected_item_slot_id: data.value,
        }, function () {
            _this.props.update_parent(data.value, "item_selected");
        });
    };
    ManageItemSockets.prototype.itemOptions = function () {
        var options = this.props.items.map(function (item) {
            return {
                label: item.name,
                value: item.slot_id,
            };
        });
        options.unshift({
            label: "Please select an item",
            value: 0,
        });
        return options;
    };
    ManageItemSockets.prototype.selectedItem = function () {
        var _this = this;
        var item = this.props.items.filter(function (item) {
            return item.slot_id === _this.state.selected_item_slot_id;
        });
        if (item.length > 0) {
            return {
                label: item[0].name,
                value: item[0].slot_id,
            };
        }
        return {
            label: "Please select an an item",
            value: 0,
        };
    };
    ManageItemSockets.prototype.render = function () {
        return (React.createElement(Fragment, null,
            React.createElement(Select, { onChange: this.setSelectedItem.bind(this), options: this.itemOptions(), menuPosition: "absolute", menuPlacement: "bottom", styles: {
                    menuPortal: function (base) { return (__assign(__assign({}, base), { zIndex: 9999, color: "#000000" })); },
                }, menuPortalTarget: document.body, value: this.selectedItem() })));
    };
    return ManageItemSockets;
}(React.Component));
export default ManageItemSockets;
//# sourceMappingURL=manage-item-sockets.js.map