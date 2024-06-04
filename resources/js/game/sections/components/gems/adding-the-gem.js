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
import BasicCard from "../../../components/ui/cards/basic-card";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
var AddingTheGem = (function (_super) {
    __extends(AddingTheGem, _super);
    function AddingTheGem(props) {
        return _super.call(this, props) || this;
    }
    AddingTheGem.prototype.canNotAddGemToItem = function () {
        return (
            this.props.socket_data.item_sockets ===
                this.props.socket_data.current_used_slots ||
            this.props.action_disabled
        );
    };
    AddingTheGem.prototype.render = function () {
        var _this = this;
        if (this.props.gem_to_add === null) {
            return null;
        }
        return React.createElement(
            BasicCard,
            null,
            React.createElement(
                "div",
                { className: "grid grid-cols-2 gap-2 my-4" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h3",
                        { className: "my-4 text-lime-600 dark:text-lime-500" },
                        this.props.gem_to_add.name,
                    ),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Tier"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.gem_to_add.tier,
                        ),
                        React.createElement(
                            "dt",
                            null,
                            this.props.gem_to_add.primary_atonement_name +
                                " Atonement: ",
                        ),
                        React.createElement(
                            "dd",
                            null,
                            (
                                this.props.gem_to_add.primary_atonement_amount *
                                100
                            ).toFixed(0),
                            "%",
                        ),
                        React.createElement(
                            "dt",
                            null,
                            this.props.gem_to_add.secondary_atonement_name +
                                " Atonement: ",
                        ),
                        React.createElement(
                            "dd",
                            null,
                            (
                                this.props.gem_to_add
                                    .secondary_atonement_amount * 100
                            ).toFixed(0),
                            "%",
                        ),
                        React.createElement(
                            "dt",
                            null,
                            this.props.gem_to_add.tertiary_atonement_name +
                                " Atonement: ",
                        ),
                        React.createElement(
                            "dd",
                            null,
                            (
                                this.props.gem_to_add
                                    .tertiary_atonement_amount * 100
                            ).toFixed(0),
                            "%",
                        ),
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h3",
                        { className: "my-4" },
                        "Item Socket Data",
                    ),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Item Name:"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.socket_data.item_name,
                        ),
                        React.createElement("dt", null, "Item Sockets:"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.socket_data.item_sockets,
                        ),
                        React.createElement("dt", null, "Sockets In use"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.socket_data.current_used_slots,
                        ),
                    ),
                ),
            ),
            React.createElement(
                "div",
                { className: "my-4" },
                React.createElement(PrimaryButton, {
                    button_label: "Socket gem",
                    on_click: function () {
                        return _this.props.do_action("attach-gem");
                    },
                    disabled: this.canNotAddGemToItem(),
                }),
            ),
        );
    };
    return AddingTheGem;
})(React.Component);
export default AddingTheGem;
//# sourceMappingURL=adding-the-gem.js.map
