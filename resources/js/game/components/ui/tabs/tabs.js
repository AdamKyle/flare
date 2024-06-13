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
import React, { Fragment } from "react";
import { Tab } from "@headlessui/react";
import clsx from "clsx";
import { isEqual } from "lodash";
var Tabs = (function (_super) {
    __extends(Tabs, _super);
    function Tabs(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            tabs: [],
        };
        return _this;
    }
    Tabs.prototype.componentDidMount = function () {
        this.setState({
            tabs: this.props.tabs,
        });
    };
    Tabs.prototype.componentDidUpdate = function () {
        if (!isEqual(this.state.tabs, this.props.tabs)) {
            this.setState({
                tabs: this.props.tabs,
            });
        }
    };
    Tabs.prototype.renderIcon = function (tab, selected) {
        if (typeof this.props.icon_key !== "undefined" && !selected) {
            if (this.props.icon_key === "has_logs") {
                if (tab[this.props.icon_key]) {
                    return (React.createElement("span", null,
                        tab.name,
                        " ",
                        React.createElement("i", { className: "ra ra-scroll-unfurled text-yellow-600 dark:text-yellow-400" })));
                }
                return tab.name;
            }
            if (tab[this.props.icon_key]) {
                return (React.createElement("span", null,
                    tab.name,
                    " ",
                    React.createElement("i", { className: "fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400" })));
            }
        }
        else if (selected) {
            if (typeof this.props.icon_key !== "undefined" &&
                typeof tab[this.props.icon_key] !== "undefined") {
                if (this.props.icon_key === "has_logs") {
                    if (tab[this.props.icon_key]) {
                        return (React.createElement("span", null,
                            tab.name,
                            " ",
                            React.createElement("i", { className: "ra ra-scroll-unfurled text-yellow-600 dark:text-yellow-400" })));
                    }
                    return tab.name;
                }
            }
            if (typeof this.props.icon_key !== "undefined" &&
                typeof this.props.when_tab_changes !== "undefined") {
                if (tab[this.props.icon_key]) {
                    this.props.when_tab_changes(tab.key);
                }
            }
        }
        return tab.name;
    };
    Tabs.prototype.renderEachTab = function () {
        var _this = this;
        return this.state.tabs.map(function (tab) {
            return (React.createElement(Tab, { key: tab.key, as: Fragment }, function (_a) {
                var selected = _a.selected;
                return (React.createElement("button", { type: "button", className: clsx("w-full py-2.5 text-sm font-medium focus:outline-none text-slate-800 dark:text-slate-200 text-center", {
                        "border-b-2 border-blue-500 dark:border-blue-400": selected,
                    }, {
                        "hover:border-blue-500 hover:border-b-2 dark:hover:border-blue-400": !selected,
                    }), disabled: typeof _this.props.disabled !== "undefined"
                        ? _this.props.disabled
                        : false }, _this.renderIcon(tab, selected)));
            }));
        });
    };
    Tabs.prototype.render = function () {
        console.log(this.props.additonal_css);
        return (React.createElement(Tab.Group, { onChange: this.props.listen_for_change },
            React.createElement(Tab.List, { className: clsx("w-full grid gap-2 content-center " +
                    this.props.additonal_css, { "md:w-1/3": !this.props.full_width }, { "grid-cols-5": this.state.tabs.length === 5 }, { "grid-cols-4": this.state.tabs.length === 4 }, { "grid-cols-3": this.state.tabs.length === 3 }, { "grid-cols-2": this.state.tabs.length === 2 }) }, this.renderEachTab()),
            React.createElement(Tab.Panels, { className: "mt-5" }, this.props.children)));
    };
    return Tabs;
}(React.Component));
export default Tabs;
//# sourceMappingURL=tabs.js.map