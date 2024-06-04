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
import FactionFame from "../../components/faction-loyalty/faction-fame";
import TabPanel from "../../components/ui/tabs/tab-panel";
import Tabs from "../../components/ui/tabs/tabs";
var ActionTabs = (function (_super) {
    __extends(ActionTabs, _super);
    function ActionTabs(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
            {
                key: "actions",
                name: "Actions",
            },
            {
                key: "faction-loyalty",
                name: "Faction Loyalty",
            },
        ];
        return _this;
    }
    ActionTabs.prototype.render = function () {
        return this.props.use_tabs
            ? React.createElement(
                  Tabs,
                  { tabs: this.tabs, disabled: false },
                  React.createElement(
                      TabPanel,
                      { key: "actions" },
                      this.props.children,
                  ),
                  React.createElement(
                      TabPanel,
                      { key: "faction-loyalty" },
                      React.createElement(FactionFame, {
                          user_id: this.props.user_id,
                          character_id: this.props.character_id,
                          update_faction_action_tasks:
                              this.props.update_faction_action_tasks,
                          can_craft: this.props.can_craft,
                          can_attack: this.props.can_attack,
                          character_map_id: this.props.character_map_id,
                      }),
                  ),
              )
            : this.props.children;
    };
    return ActionTabs;
})(React.Component);
export default ActionTabs;
//# sourceMappingURL=action-tabs.js.map
