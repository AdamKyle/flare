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
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";
import EventGoalsTab from "./tabs/event-goals-tab";
var MapTabs = (function (_super) {
    __extends(MapTabs, _super);
    function MapTabs(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
            {
                key: "map",
                name: "Map",
            },
            {
                key: "event-goals",
                name: "Event Goals",
            },
        ];
        return _this;
    }
    MapTabs.prototype.render = function () {
        return this.props.use_tabs
            ? React.createElement(
                  Tabs,
                  { tabs: this.tabs, disabled: false },
                  React.createElement(
                      TabPanel,
                      { key: "map" },
                      this.props.children,
                  ),
                  React.createElement(
                      TabPanel,
                      { key: "event-goals" },
                      React.createElement(EventGoalsTab, {
                          character_id: this.props.character_id,
                          user_id: this.props.user_id,
                      }),
                  ),
              )
            : this.props.children;
    };
    return MapTabs;
})(React.Component);
export default MapTabs;
//# sourceMappingURL=map-tabs.js.map
