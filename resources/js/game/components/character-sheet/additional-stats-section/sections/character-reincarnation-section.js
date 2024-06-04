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
import { formatNumber } from "../../../../lib/game/format-number";
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
var CharacterReincarnationSection = (function (_super) {
    __extends(CharacterReincarnationSection, _super);
    function CharacterReincarnationSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_loading: true,
            reincarnation_details: [],
            error_message: "",
        };
        return _this;
    }
    CharacterReincarnationSection.prototype.componentDidMount = function () {
        var _this = this;
        if (this.props.character === null) {
            return;
        }
        new Ajax()
            .setRoute(
                "character-sheet/" +
                    this.props.character.id +
                    "/reincarnation-info",
            )
            .doAjaxCall(
                "get",
                function (response) {
                    _this.setState({
                        is_loading: false,
                        reincarnation_details:
                            response.data.reincarnation_details,
                    });
                },
                function (error) {
                    _this.setState({
                        is_loading: false,
                    });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    };
    CharacterReincarnationSection.prototype.render = function () {
        if (this.props.character === null) {
            return null;
        }
        return React.createElement(
            React.Fragment,
            null,
            this.state.is_loading
                ? React.createElement(LoadingProgressBar, null)
                : React.createElement(
                      "div",
                      null,
                      React.createElement(
                          "dl",
                          null,
                          React.createElement("dt", null, "Reincarnated Times"),
                          React.createElement(
                              "dd",
                              null,
                              this.state.reincarnation_details
                                  .reincarnated_times !== null
                                  ? formatNumber(
                                        this.state.reincarnation_details
                                            .reincarnated_times,
                                    )
                                  : 0,
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "Reincarnation Stat Bonus (pts.)",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              this.state.reincarnation_details
                                  .reincarnated_stat_increase !== null
                                  ? formatNumber(
                                        this.state.reincarnation_details
                                            .reincarnated_stat_increase,
                                    )
                                  : 0,
                          ),
                          React.createElement("dt", null, "Base Stat Mod"),
                          React.createElement(
                              "dd",
                              null,
                              (
                                  this.state.reincarnation_details
                                      .base_stat_mod * 100
                              ).toFixed(2),
                              "%",
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "Base Damage Stat Mod",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              (
                                  this.state.reincarnation_details
                                      .base_damage_stat_mod * 100
                              ).toFixed(2),
                              "%",
                          ),
                          React.createElement("dt", null, "XP Penalty"),
                          React.createElement(
                              "dd",
                              null,
                              this.state.reincarnation_details.xp_penalty !==
                                  null
                                  ? (
                                        this.state.reincarnation_details
                                            .xp_penalty * 100
                                    ).toFixed(0)
                                  : 0,
                              "%",
                          ),
                      ),
                  ),
        );
    };
    return CharacterReincarnationSection;
})(React.Component);
export default CharacterReincarnationSection;
//# sourceMappingURL=character-reincarnation-section.js.map
