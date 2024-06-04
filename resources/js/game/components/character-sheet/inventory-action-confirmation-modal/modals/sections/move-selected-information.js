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
import DropDown from "../../../../ui/drop-down/drop-down";
var MoveSelectedInformation = (function (_super) {
    __extends(MoveSelectedInformation, _super);
    function MoveSelectedInformation(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            set_name: "Select a set",
            set_id: null,
        };
        return _this;
    }
    MoveSelectedInformation.prototype.renderSelectedItemNames = function () {
        return this.props.item_names.map(function (name) {
            return React.createElement("li", null, name);
        });
    };
    MoveSelectedInformation.prototype.setName = function (setId) {
        var _this = this;
        if (!this.props.usable_sets) {
            return;
        }
        if (!this.props.update_api_params) {
            return;
        }
        var setName = this.props.usable_sets.filter(function (set) {
            return set.id === setId;
        })[0].name;
        this.setState(
            {
                set_name: setName,
                set_id: setId,
            },
            function () {
                if (!_this.props.usable_sets) {
                    return;
                }
                if (!_this.props.update_api_params) {
                    return;
                }
                _this.props.update_api_params({
                    set_name: setName,
                    set_id: setId,
                });
            },
        );
    };
    MoveSelectedInformation.prototype.buildDropDown = function () {
        var _this = this;
        if (!this.props.usable_sets) {
            return [];
        }
        return this.props.usable_sets.map(function (set) {
            return {
                name: set.name,
                icon_class: "fas fa-shopping-bag",
                on_click: function () {
                    return _this.setName(set.id);
                },
            };
        });
    };
    MoveSelectedInformation.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "div",
                {
                    className:
                        "grid grid-cols-2 gap-4 max-h-[450px] lg:max-h-full overflow-y-scroll lg:overflow-y-auto",
                },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h3",
                        { className: "mb-3" },
                        "Movement Details",
                    ),
                    React.createElement(
                        "p",
                        { className: "mb-3" },
                        "Are you sure you want to do this? This action will move all selected items below.",
                        " ",
                    ),
                    React.createElement(
                        "p",
                        { className: "mb-3" },
                        "Below, the list of items to move, you can select what set to move it to. To the right are a set of rules for making a set equitable.",
                    ),
                    React.createElement(
                        "p",
                        { className: "mb-3" },
                        "Only non equipped sets may be chosen.",
                    ),
                    this.props.usable_sets
                        ? React.createElement(DropDown, {
                              menu_items: this.buildDropDown(),
                              button_title:
                                  this.state.set_name !== null
                                      ? this.state.set_name
                                      : "Move to set",
                          })
                        : null,
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "span",
                        { className: "mb-3" },
                        React.createElement("strong", null, "Items to Move"),
                    ),
                    React.createElement(
                        "ul",
                        { className: "my-3 pl-4 list-disc ml-4" },
                        this.renderSelectedItemNames(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement("h3", { className: "mb-3" }, "Rules"),
                    React.createElement(
                        "p",
                        { className: "mb-3" },
                        "You can move any item to any set from your inventory, but if you plan to equip that set you must follow the rules below.",
                    ),
                    React.createElement(
                        "ul",
                        { className: "mb-3 list-disc ml-4" },
                        React.createElement(
                            "li",
                            null,
                            React.createElement("strong", null, "Hands"),
                            ": 1 or 2 weapons for hands, or 1 or 2 shields or 1 duel wielded weapon (bow, hammer or stave). Guns, Fans, Scratch Awls and Maces follow the same rules",
                        ),
                        React.createElement(
                            "li",
                            null,
                            React.createElement("strong", null, "Armour"),
                            ": 1 of each type, body, head, leggings ...",
                        ),
                        React.createElement(
                            "li",
                            null,
                            React.createElement("strong", null, "Spells"),
                            ": Max of 2 regardless of type.",
                        ),
                        React.createElement(
                            "li",
                            null,
                            React.createElement("strong", null, "Rings"),
                            ": Max of 2",
                        ),
                        React.createElement(
                            "li",
                            null,
                            React.createElement("strong", null, "Trinkets"),
                            ": Max of 1",
                        ),
                        React.createElement(
                            "li",
                            null,
                            React.createElement(
                                "strong",
                                null,
                                "Uniques (green items)",
                            ),
                            ": 1 unique, regardless of type.",
                        ),
                        React.createElement(
                            "li",
                            null,
                            React.createElement(
                                "strong",
                                null,
                                "Mythics (orange items)",
                            ),
                            ": 1 Mythic, if there is no Unique, regardless of type.",
                        ),
                        React.createElement(
                            "li",
                            null,
                            React.createElement(
                                "strong",
                                null,
                                "Comsic (light purple items)",
                            ),
                            ": 1 Cosmic, if there is no Unique OR Mythic, regardless of type.",
                        ),
                        React.createElement(
                            "li",
                            null,
                            React.createElement(
                                "strong",
                                null,
                                "Ancestral Items (purple items)",
                            ),
                            ": 1 Ancestral item only.",
                        ),
                    ),
                    React.createElement(
                        "p",
                        { className: "mb-3" },
                        "The above rules only apply to characters who want to equip the set, You may also use a set as a stash tab with unlimited items.",
                    ),
                ),
            ),
        );
    };
    return MoveSelectedInformation;
})(React.Component);
export default MoveSelectedInformation;
//# sourceMappingURL=move-selected-information.js.map
