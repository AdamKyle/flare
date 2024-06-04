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
import React, { Fragment } from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import InfoTab from "./tabs/info-tab";
import CharacterActiveBoons from "./character-active-boons";
import CharacterFactions from "./character-factions";
var CharacterTabs = (function (_super) {
    __extends(CharacterTabs, _super);
    function CharacterTabs(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
            {
                key: "info",
                name: "Info",
            },
            {
                key: "active-boons",
                name: "Active Boons",
            },
            {
                key: "factions",
                name: "Factions",
            },
        ];
        return _this;
    }
    CharacterTabs.prototype.render = function () {
        var _a, _b, _c, _d;
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                Tabs,
                { tabs: this.tabs, full_width: true },
                React.createElement(
                    TabPanel,
                    { key: "info" },
                    React.createElement(InfoTab, {
                        view_port: 0,
                        character: this.props.character,
                        finished_loading: this.props.finished_loading,
                        manage_addition_data: this.props.manage_addition_data,
                    }),
                ),
                React.createElement(
                    TabPanel,
                    { key: "active-boons" },
                    React.createElement(CharacterActiveBoons, {
                        character_id:
                            (_a = this.props.character) === null ||
                            _a === void 0
                                ? void 0
                                : _a.id,
                        finished_loading: this.props.finished_loading,
                    }),
                ),
                React.createElement(
                    TabPanel,
                    { key: "factions" },
                    React.createElement(CharacterFactions, {
                        update_pledge_tab: this.props.update_pledge_tab,
                        character_id:
                            (_b = this.props.character) === null ||
                            _b === void 0
                                ? void 0
                                : _b.id,
                        finished_loading: this.props.finished_loading,
                        is_pledged:
                            (_c = this.props.character) === null ||
                            _c === void 0
                                ? void 0
                                : _c.can_see_pledge_tab,
                        pledged_faction_id:
                            (_d = this.props.character) === null ||
                            _d === void 0
                                ? void 0
                                : _d.pledged_to_faction_id,
                        update_faction_action_tasks:
                            this.props.update_faction_action_tasks,
                    }),
                ),
            ),
        );
    };
    return CharacterTabs;
})(React.Component);
export default CharacterTabs;
//# sourceMappingURL=character-tabs.js.map
