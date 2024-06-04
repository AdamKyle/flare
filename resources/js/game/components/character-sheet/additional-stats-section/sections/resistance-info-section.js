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
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
var ResistanceInfoSection = (function (_super) {
    __extends(ResistanceInfoSection, _super);
    function ResistanceInfoSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_loading: true,
            error_message: "",
            resistance_info: [],
        };
        return _this;
    }
    ResistanceInfoSection.prototype.componentDidMount = function () {
        var _this = this;
        this.setState(
            {
                error_message: "",
            },
            function () {
                if (_this.props.character === null) {
                    return;
                }
                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            _this.props.character.id +
                            "/resistance-info",
                    )
                    .doAjaxCall(
                        "get",
                        function (response) {
                            _this.setState({
                                is_loading: false,
                                resistance_info: response.data.resistance_info,
                            });
                        },
                        function (error) {
                            _this.setState({ is_loading: false });
                            if (typeof error.response !== "undefined") {
                                _this.setState({
                                    error_message: error.response.data.mmessage,
                                });
                            }
                        },
                    );
            },
        );
    };
    ResistanceInfoSection.prototype.render = function () {
        if (this.props.character === null) {
            return null;
        }
        if (this.state.error_message !== "") {
            return React.createElement(
                DangerAlert,
                { additional_css: "my-4" },
                this.state.error_message,
            );
        }
        if (this.state.is_loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Spell Evasion"),
                React.createElement(
                    "dd",
                    null,
                    (this.state.resistance_info.spell_evasion * 100).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Affix Damage Reduction"),
                React.createElement(
                    "dd",
                    null,
                    (
                        this.state.resistance_info.affix_damage_reduction * 100
                    ).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Enemy Healing Reduction"),
                React.createElement(
                    "dd",
                    null,
                    (
                        this.state.resistance_info.healing_reduction * 100
                    ).toFixed(2),
                    "%",
                ),
            ),
        );
    };
    return ResistanceInfoSection;
})(React.Component);
export default ResistanceInfoSection;
//# sourceMappingURL=resistance-info-section.js.map
