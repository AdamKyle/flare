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
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import { upperFirst } from "lodash";
import { formatNumber } from "../../../lib/game/format-number";
import Ajax from "../../../lib/ajax/ajax";
var UpgradeWithResources = (function (_super) {
    __extends(UpgradeWithResources, _super);
    function UpgradeWithResources(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            success_message: null,
            error_message: null,
            loading: false,
        };
        return _this;
    }
    UpgradeWithResources.prototype.upgradeBuilding = function () {
        var _this = this;
        this.setState(
            {
                error_message: null,
                success_message: null,
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "kingdoms/" +
                            _this.props.character_id +
                            "/upgrade-building/" +
                            _this.props.building.id,
                    )
                    .setParameters({
                        to_level: 1,
                        paying_with_gold: false,
                    })
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState({
                                loading: false,
                                success_message: response.data.message,
                            });
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                var message = response.data.message;
                                if (response.data.error) {
                                    message = response.data.error;
                                }
                                _this.setState({
                                    loading: false,
                                    error_message: message,
                                });
                            }
                        },
                    );
            },
        );
    };
    UpgradeWithResources.prototype.renderFutureResourceValues = function () {
        var _this = this;
        var resourceValues = [
            "future_clay_increase",
            "future_defence_increase",
            "future_durability_increase",
            "future_iron_increase",
            "future_population_increase",
            "future_stone_increase",
            "future_wood_increase",
        ];
        return resourceValues
            .map(function (value) {
                var buildingValue = _this.props.building[value];
                if (
                    buildingValue !== null &&
                    typeof buildingValue === "number"
                ) {
                    if (buildingValue > 0.0) {
                        return React.createElement(
                            Fragment,
                            null,
                            React.createElement(
                                "dt",
                                null,
                                upperFirst(
                                    value
                                        .replace("future_", "")
                                        .replace("_", " "),
                                ),
                            ),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(buildingValue),
                            ),
                        );
                    }
                }
            })
            .filter(function (element) {
                return typeof element !== "undefined";
            });
    };
    UpgradeWithResources.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      { additional_css: "mb-5" },
                      this.state.success_message,
                  )
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "mb-5" },
                      this.state.error_message,
                  )
                : null,
            React.createElement("h3", null, "After Level Up"),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
            }),
            React.createElement(
                "dl",
                { className: "mb-5" },
                this.renderFutureResourceValues(),
            ),
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
            React.createElement(PrimaryButton, {
                button_label: "Upgrade",
                additional_css: "mr-2",
                on_click: this.upgradeBuilding.bind(this),
                disabled: this.state.loading || this.props.is_in_queue,
            }),
            React.createElement(DangerButton, {
                button_label: "Cancel",
                on_click: this.props.remove_section.bind(this),
                disabled: this.state.loading,
            }),
        );
    };
    return UpgradeWithResources;
})(React.Component);
export default UpgradeWithResources;
//# sourceMappingURL=upgrade-with-resources.js.map
