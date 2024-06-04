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
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import MoveUnits from "../unit-movement/move-units";
import UnitMovement from "./partials/unit-movement";
var CallForReinforcements = (function (_super) {
    __extends(CallForReinforcements, _super);
    function CallForReinforcements(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            processing_unit_request: false,
            kingdoms: [],
            error_message: null,
            success_message: null,
            selected_kingdoms: [],
            selected_units: [],
        };
        _this.moveUnits = new MoveUnits();
        return _this;
    }
    CallForReinforcements.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "kingdoms/units/" +
                    this.props.character_id +
                    "/" +
                    this.props.kingdom_id +
                    "/call-reinforcements",
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        loading: false,
                        kingdoms: result.data,
                    });
                },
                function (error) {
                    _this.setState({ loading: false });
                    console.error(error);
                },
            );
    };
    CallForReinforcements.prototype.callUnits = function () {
        var _this = this;
        this.setState(
            {
                processing_unit_request: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "kingdom/move-reinforcements/" +
                            _this.props.character_id +
                            "/" +
                            _this.props.kingdom_id,
                    )
                    .setParameters({
                        units_to_move: _this.state.selected_units,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                processing_unit_request: false,
                                success_message: result.data.message,
                            });
                        },
                        function (error) {
                            _this.setState({ processing_unit_request: false });
                            if (typeof error.response != "undefined") {
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
    CallForReinforcements.prototype.setAmountToMove = function (selectedUnits) {
        this.setState({
            selected_units: selectedUnits,
        });
    };
    CallForReinforcements.prototype.setKingdoms = function (kingdomsSelected) {
        this.setState({
            selected_kingdoms: kingdomsSelected,
        });
    };
    CallForReinforcements.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Call for reinforcements",
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    handle_action: this.callUnits.bind(this),
                    secondary_button_disabled:
                        this.state.loading ||
                        (this.state.kingdoms.length === 0 &&
                            this.state.selected_units.length === 0),
                    secondary_button_label: "Call Reinforcements",
                },
            },
            this.state.loading
                ? React.createElement(ComponentLoading, null)
                : null,
            this.state.kingdoms.length > 0
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement(UnitMovement, {
                          kingdoms: this.state.kingdoms,
                          update_units_selected:
                              this.setAmountToMove.bind(this),
                          update_kingdoms_selected: this.setKingdoms.bind(this),
                      }),
                      this.state.processing_unit_request
                          ? React.createElement(LoadingProgressBar, null)
                          : null,
                  )
                : React.createElement(
                      InfoAlert,
                      null,
                      "You have no units in other kingdoms to move units from or you have no other kingdoms.",
                  ),
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      { additional_css: "my-4" },
                      this.state.success_message,
                  )
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-4" },
                      this.state.error_message,
                  )
                : null,
        );
    };
    return CallForReinforcements;
})(React.Component);
export default CallForReinforcements;
//# sourceMappingURL=call-for-reinforcements%20.js.map
