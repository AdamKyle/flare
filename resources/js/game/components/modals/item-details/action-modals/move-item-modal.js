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
import Dialogue from "../../../ui/dialogue/dialogue";
import DropDown from "../../../ui/drop-down/drop-down";
var MoveItemModal = (function (_super) {
    __extends(MoveItemModal, _super);
    function MoveItemModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            set_name: null,
            set_id: null,
        };
        return _this;
    }
    MoveItemModal.prototype.setName = function (setId) {
        this.setState({
            set_name: this.props.usable_sets.filter(function (set) {
                return set.id === setId;
            })[0].name,
            set_id: setId,
        });
    };
    MoveItemModal.prototype.move = function () {
        this.props.move_item(this.state.set_id);
        this.props.manage_modal();
    };
    MoveItemModal.prototype.buildDropDown = function () {
        var _this = this;
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
    MoveItemModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: "Move to set",
                secondary_actions: {
                    secondary_button_disabled: this.state.set_id === null,
                    secondary_button_label: "Move",
                    handle_action: this.move.bind(this),
                },
            },
            React.createElement(
                "div",
                {
                    className:
                        "grid grid-cols-2 gap-2 max-h-[450px] lg:max-h-full overflow-y-scroll lg:overflow-y-auto",
                },
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
                React.createElement(
                    "div",
                    null,
                    React.createElement(DropDown, {
                        menu_items: this.buildDropDown(),
                        button_title:
                            this.state.set_name !== null
                                ? this.state.set_name
                                : "Move to set",
                    }),
                ),
            ),
        );
    };
    return MoveItemModal;
})(React.Component);
export default MoveItemModal;
//# sourceMappingURL=move-item-modal.js.map
