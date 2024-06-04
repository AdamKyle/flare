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
import Dialogue from "../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import React, { Fragment } from "react";
import Ajax from "../../../lib/ajax/ajax";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import AddingTheGem from "./adding-the-gem";
import ReplacingAGem from "./replacing-a-gem";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import { formatNumber } from "../../../lib/game/format-number";
import SeerActions from "../../../components/npc-actions/seer-camp/ajax/seer-actions";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
var ManageGems = (function (_super) {
    __extends(ManageGems, _super);
    function ManageGems(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            gem_to_attach: null,
            when_replacing: [],
            has_gems_on_item: false,
            attached_gems: [],
            if_replacing_atonements: [],
            original_atonement: [],
            socket_data: {},
            tabs: [
                {
                    key: "add-gem",
                    name: "Add Gem",
                },
                {
                    key: "replace_gem",
                    name: "Replace Gem",
                },
            ],
            trading_with_seer: false,
            error_message: null,
        };
        return _this;
    }
    ManageGems.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("gem-comparison/" + this.props.character_id)
            .setParameters({
                slot_id: this.props.selected_item,
                gem_slot_id: this.props.selected_gem,
            })
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        loading: false,
                        attached_gems: result.data.attached_gems,
                        gem_to_attach: result.data.gem_to_attach,
                        when_replacing: result.data.when_replacing,
                        has_gems_on_item: result.data.has_gem_on_item,
                        socket_data: result.data.socket_data,
                        if_replacing_atonements:
                            result.data.if_replacing_atonements,
                        original_atonement: result.data.original_atonement,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    ManageGems.prototype.doAction = function (action) {
        var _this = this;
        if (action === "attach-gem") {
            this.setState(
                {
                    trading_with_seer: true,
                    error_message: null,
                },
                function () {
                    SeerActions.attachGemToItem(
                        _this,
                        _this.props.selected_item,
                        _this.props.selected_gem,
                    );
                },
            );
        }
    };
    ManageGems.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_model,
                title: "Seer Socketing Table",
                primary_button_disabled: this.state.trading_with_seer,
            },
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          InfoAlert,
                          { additional_css: "my-4" },
                          React.createElement(
                              "span",
                              { className: "text-[18px]" },
                              React.createElement(
                                  "strong",
                                  null,
                                  "The cost for Attaching or replacing is:",
                                  " ",
                                  formatNumber(this.props.cost),
                                  " Gold Bars.",
                              ),
                          ),
                      ),
                      this.state.trading_with_seer
                          ? React.createElement(
                                "div",
                                { className: "my-4" },
                                React.createElement(LoadingProgressBar, null),
                            )
                          : null,
                      this.state.error_message !== null
                          ? React.createElement(
                                DangerAlert,
                                { additional_css: "my-4" },
                                this.state.error_message,
                            )
                          : null,
                      React.createElement(
                          Tabs,
                          { tabs: this.state.tabs, disabled: false },
                          React.createElement(
                              TabPanel,
                              { key: "add-gem" },
                              React.createElement(AddingTheGem, {
                                  gem_to_add: this.state.gem_to_attach,
                                  do_action: this.doAction.bind(this),
                                  action_disabled: this.state.trading_with_seer,
                                  socket_data: this.state.socket_data,
                              }),
                          ),
                          React.createElement(
                              TabPanel,
                              { key: "replace-gem" },
                              React.createElement(ReplacingAGem, {
                                  when_replacing: this.state.when_replacing,
                                  gems_you_have: this.state.attached_gems,
                                  action_disabled: this.state.trading_with_seer,
                                  original_atonement:
                                      this.state.original_atonement,
                                  if_replacing:
                                      this.state.if_replacing_atonements,
                                  update_parent: this.props.update_parent,
                                  selected_gem: this.props.selected_gem,
                                  selected_item: this.props.selected_item,
                                  manage_parent_modal: this.props.manage_model,
                                  character_id: this.props.character_id,
                              }),
                          ),
                      ),
                  ),
        );
    };
    return ManageGems;
})(React.Component);
export default ManageGems;
//# sourceMappingURL=manage-gems.js.map
