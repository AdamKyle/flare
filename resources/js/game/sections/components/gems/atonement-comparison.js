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
import SeerActions from "../../../components/npc-actions/seer-camp/ajax/seer-actions";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import BasicCard from "../../../components/ui/cards/basic-card";
import RenderAtonementDetails from "./components/render-atonement-details";
import RenderAtonementAdjustment from "./components/render-atonement-adjustment";
var AtonementComparison = (function (_super) {
    __extends(AtonementComparison, _super);
    function AtonementComparison(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_replacing: false,
            error_message: null,
        };
        return _this;
    }
    AtonementComparison.prototype.closeModals = function () {
        this.props.manage_modal();
        this.props.manage_parent_modal();
    };
    AtonementComparison.prototype.findAtonementsForReplacing = function () {
        var _this = this;
        return this.props.if_replacing.find(function (data) {
            return data.name_to_replace === _this.props.gem_name;
        });
    };
    AtonementComparison.prototype.replaceGem = function () {
        var _this = this;
        this.setState(
            {
                is_replacing: true,
                error_message: null,
            },
            function () {
                var gemSocketId = _this.findAtonementsForReplacing();
                if (typeof gemSocketId !== "undefined") {
                    SeerActions.replaceGemOnItem(
                        _this,
                        _this.props.selected_item,
                        _this.props.selected_gem,
                        gemSocketId.gem_id,
                    );
                }
            },
        );
    };
    AtonementComparison.prototype.render = function () {
        var atonementForReplacing = this.findAtonementsForReplacing();
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: "Replacement details",
                primary_button_disabled: this.state.is_replacing,
                secondary_actions: {
                    secondary_button_disabled: this.state.is_replacing,
                    secondary_button_label: "Replace The Gem",
                    handle_action: this.replaceGem.bind(this),
                },
            },
            React.createElement(
                "p",
                { className: "my-4" },
                "Below are your Atonement Adjustment Details. Each item can be atoned to a specific element.",
            ),
            React.createElement(
                "p",
                { className: "my-4" },
                "Upon doing so, taking into account your overall gear and the items atonements, your element damage/resistances could change.",
            ),
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-4" },
                      this.state.error_message,
                  )
                : null,
            React.createElement(
                "div",
                { className: "my-4 grid lg:grid-cols-2 gap-2" },
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement(
                        "h3",
                        { className: "my-4" },
                        "With:",
                        " ",
                        React.createElement(
                            "span",
                            { className: "text-lime-600 dark:text-lime-500" },
                            this.props.gem_name,
                        ),
                    ),
                    React.createElement(RenderAtonementDetails, {
                        title: "Original Atonement",
                        original_atonement: this.props.original_atonement,
                    }),
                    React.createElement(
                        "div",
                        { className: "my-4" },
                        React.createElement(
                            "h4",
                            { className: "mb-2" },
                            "Original Elemental Atonement",
                        ),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement(
                                "dt",
                                null,
                                "Elemental Atonement",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                this.props.original_atonement.elemental_damage
                                    .name,
                            ),
                            React.createElement("dt", null, "Elemental Damage"),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.original_atonement
                                        .elemental_damage.amount * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                    ),
                ),
                React.createElement(
                    BasicCard,
                    null,
                    typeof atonementForReplacing !== "undefined"
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement(
                                  "h3",
                                  { className: "my-4" },
                                  "When:",
                                  " ",
                                  React.createElement(
                                      "span",
                                      {
                                          className:
                                              "text-lime-600 dark:text-lime-500",
                                      },
                                      atonementForReplacing.name_to_replace,
                                  ),
                                  " ",
                                  "is replaced",
                              ),
                              React.createElement(RenderAtonementAdjustment, {
                                  atonement_for_comparison:
                                      atonementForReplacing.data.atonements,
                                  original_atonement:
                                      this.props.original_atonement,
                              }),
                              React.createElement(
                                  "div",
                                  { className: "my-4" },
                                  React.createElement(
                                      "h4",
                                      { className: "mb-2" },
                                      "Adjusted Elemental Atonement",
                                  ),
                                  React.createElement(
                                      "dl",
                                      null,
                                      React.createElement(
                                          "dt",
                                          null,
                                          "Elemental Atonement",
                                      ),
                                      React.createElement(
                                          "dd",
                                          null,
                                          atonementForReplacing.data
                                              .elemental_damage.name,
                                      ),
                                      React.createElement(
                                          "dt",
                                          null,
                                          "Elemental Damage",
                                      ),
                                      React.createElement(
                                          "dd",
                                          null,
                                          (
                                              atonementForReplacing.data
                                                  .elemental_damage.amount * 100
                                          ).toFixed(2),
                                          "%",
                                      ),
                                  ),
                              ),
                          )
                        : React.createElement(
                              DangerAlert,
                              null,
                              "Error with finding the atonement to replace.",
                          ),
                ),
            ),
            this.state.is_replacing
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return AtonementComparison;
})(React.Component);
export default AtonementComparison;
//# sourceMappingURL=atonement-comparison.js.map
