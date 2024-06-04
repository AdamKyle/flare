var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
import React from "react";
import Tabs from "../../../../game/components/ui/tabs/tabs";
import TabPanel from "../../../../game/components/ui/tabs/tab-panel";
var TabLayout = (function (_super) {
    __extends(TabLayout, _super);
    function TabLayout(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
            {
                key: "story",
                name: "Story",
            },
            {
                key: "information",
                name: "Information",
            },
            {
                key: "desktop-instructions",
                name: "Desktop Instructions",
            },
        ];
        if (_this.props.is_small) {
            _this.tabs.pop();
            _this.tabs.push({
                key: "mobile-instructions",
                name: "Mobile Instructions",
            });
        }
        return _this;
    }
    TabLayout.prototype.render = function () {
        return React.createElement(
            Tabs,
            { tabs: this.tabs },
            React.createElement(
                TabPanel,
                { key: "story" },
                React.createElement(
                    "div",
                    {
                        className:
                            "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4",
                    },
                    React.createElement("div", {
                        dangerouslySetInnerHTML: {
                            __html: this.props.intro_text,
                        },
                    }),
                ),
            ),
            React.createElement(
                TabPanel,
                { key: "information" },
                React.createElement(
                    "div",
                    {
                        className:
                            "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4 guide-quest-instructions",
                    },
                    React.createElement("div", {
                        dangerouslySetInnerHTML: {
                            __html: this.props.instructions,
                        },
                    }),
                ),
            ),
            !this.props.is_small
                ? React.createElement(
                      TabPanel,
                      { key: "desktop-instructions" },
                      React.createElement(
                          "div",
                          {
                              className:
                                  "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4 guide-quest-instructions",
                          },
                          React.createElement("div", {
                              dangerouslySetInnerHTML: {
                                  __html: this.props.desktop_instructions,
                              },
                          }),
                      ),
                  )
                : React.createElement(
                      TabPanel,
                      { key: "mobile-instructions" },
                      React.createElement(
                          "div",
                          {
                              className:
                                  "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4 guide-quest-instructions",
                          },
                          React.createElement("div", {
                              dangerouslySetInnerHTML: {
                                  __html: this.props.mobile_instructions,
                              },
                          }),
                      ),
                  ),
        );
    };
    return TabLayout;
})(React.Component);
export default TabLayout;
//# sourceMappingURL=tab-labout.js.map
