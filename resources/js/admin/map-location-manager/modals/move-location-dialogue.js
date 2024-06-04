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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React from "react";
import Dialogue from "../../../game/components/ui/dialogue/dialogue";
import Select from "react-select";
import PrimaryButton from "../../../game/components/ui/buttons/primary-button";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import MoveLocationAjax from "../ajax/move-location-ajax";
import { gridOverLayContainer } from "../container/grid-overlay-container";
var MoveLocationDialogue = (function (_super) {
    __extends(MoveLocationDialogue, _super);
    function MoveLocationDialogue(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            selected_location_id: 0,
            error_message: null,
            processing: false,
        };
        _this.moveLocationAjax = gridOverLayContainer().fetch(MoveLocationAjax);
        return _this;
    }
    MoveLocationDialogue.prototype.setSelectedLocation = function (data) {
        if (data.value === 0) {
            return;
        }
        this.setState({
            selected_location_id: parseInt(data.value) || 0,
        });
    };
    MoveLocationDialogue.prototype.options = function () {
        return this.props.locations.map(function (location) {
            return {
                label: location.name + (location.is_port ? " (Port)" : ""),
                value: location.id,
            };
        });
    };
    MoveLocationDialogue.prototype.getDefaultOption = function () {
        var _this = this;
        var filteredLocation = this.props.locations.filter(function (location) {
            return location.id === _this.state.selected_location_id;
        });
        if (filteredLocation.length > 0) {
            return [
                {
                    label:
                        filteredLocation[0].name +
                        (filteredLocation[0].is_port ? " (Port)" : ""),
                    value: filteredLocation[0].id,
                },
            ];
        }
        return [
            {
                label: "Please select",
                value: 0,
            },
        ];
    };
    MoveLocationDialogue.prototype.moveLocation = function () {
        var _this = this;
        this.setState(
            {
                processing: true,
            },
            function () {
                _this.moveLocationAjax.moveLocation(
                    _this,
                    _this.state.selected_location_id,
                    _this.props.coordinates,
                );
            },
        );
    };
    MoveLocationDialogue.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                title: "Location Mover",
                handle_close: this.props.closeModal,
            },
            React.createElement(
                "p",
                null,
                "Select a location from below that you want to be moved here (X/Y): ",
                this.props.coordinates.x,
                " /",
                " ",
                this.props.coordinates.y,
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(Select, {
                onChange: this.setSelectedLocation.bind(this),
                options: this.options(),
                menuPosition: "absolute",
                menuPlacement: "bottom",
                styles: {
                    menuPortal: function (base) {
                        return __assign(__assign({}, base), {
                            zIndex: 9999,
                            color: "#000000",
                        });
                    },
                },
                menuPortalTarget: document.body,
                value: this.getDefaultOption(),
            }),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            this.state.processing
                ? React.createElement(LoadingProgressBar, null)
                : null,
            React.createElement(PrimaryButton, {
                button_label: "Move Location",
                on_click: this.moveLocation.bind(this),
                disabled:
                    this.state.processing ||
                    this.state.selected_location_id === 0,
            }),
        );
    };
    return MoveLocationDialogue;
})(React.Component);
export default MoveLocationDialogue;
//# sourceMappingURL=move-location-dialogue.js.map
