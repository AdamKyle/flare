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
import DropDown from "../../../ui/drop-down/drop-down";
import StatDetails from "./partials/core-stats/stat-details";
import HolyDetails from "./partials/core-stats/holy-details";
import AmbushAndCounterDetails from "./partials/core-stats/ambush-and-counter-details";
import VoidanceDetails from "./partials/core-stats/voidance-details";
var CoreCharacterStatsSection = (function (_super) {
    __extends(CoreCharacterStatsSection, _super);
    function CoreCharacterStatsSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_loading: true,
            stat_details: [],
            error_message: "",
            stat_type_to_show: "",
        };
        return _this;
    }
    CoreCharacterStatsSection.prototype.componentDidMount = function () {
        var _this = this;
        if (this.props.character === null) {
            return;
        }
        new Ajax()
            .setRoute(
                "character-sheet/" + this.props.character.id + "/stat-details",
            )
            .doAjaxCall(
                "get",
                function (response) {
                    _this.setState({
                        is_loading: false,
                        stat_details: response.data.stat_details,
                    });
                },
                function (error) {
                    _this.setState({ is_loading: false });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    };
    CoreCharacterStatsSection.prototype.setFilterType = function (type) {
        this.setState({
            stat_type_to_show: type,
        });
    };
    CoreCharacterStatsSection.prototype.createTypeFilterDropDown = function () {
        var _this = this;
        return [
            {
                name: "Core Stats",
                icon_class: "ra ra-muscle-fat",
                on_click: function () {
                    return _this.setFilterType("core-stats");
                },
            },
            {
                name: "Holy",
                icon_class: "ra ra-level-three",
                on_click: function () {
                    return _this.setFilterType("holy");
                },
            },
            {
                name: "Ambush & Counter",
                icon_class: "ra ra-blade-bite",
                on_click: function () {
                    return _this.setFilterType("ambush");
                },
            },
            {
                name: "Voidance",
                icon_class: "ra ra-double-team",
                on_click: function () {
                    return _this.setFilterType("voidance");
                },
            },
        ];
    };
    CoreCharacterStatsSection.prototype.renderSection = function () {
        switch (this.state.stat_type_to_show) {
            case "core-stats":
                return React.createElement(StatDetails, {
                    stat_details: this.state.stat_details,
                    character: this.props.character,
                });
            case "holy":
                return React.createElement(HolyDetails, {
                    stat_details: this.state.stat_details,
                });
            case "ambush":
                return React.createElement(AmbushAndCounterDetails, {
                    stat_details: this.state.stat_details,
                });
            case "voidance":
                return React.createElement(VoidanceDetails, {
                    stat_details: this.state.stat_details,
                });
            default:
                return React.createElement(StatDetails, {
                    stat_details: this.state.stat_details,
                    character: this.props.character,
                });
        }
    };
    CoreCharacterStatsSection.prototype.render = function () {
        if (this.props.character === null) {
            return null;
        }
        if (this.state.is_loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                { className: "my-4 max-w-full md:max-w-[25%]" },
                React.createElement(DropDown, {
                    menu_items: this.createTypeFilterDropDown(),
                    button_title: "Stat Type",
                }),
            ),
            this.renderSection(),
        );
    };
    return CoreCharacterStatsSection;
})(React.Component);
export default CoreCharacterStatsSection;
//# sourceMappingURL=core-character-stats-section.js.map
