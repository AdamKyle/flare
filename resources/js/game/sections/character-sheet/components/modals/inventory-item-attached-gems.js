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
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import BasicCard from "../../../../components/ui/cards/basic-card";
var InventoryItemAttachedGems = (function (_super) {
    __extends(InventoryItemAttachedGems, _super);
    function InventoryItemAttachedGems(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            attached_gems: [],
        };
        return _this;
    }
    InventoryItemAttachedGems.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "socketed-gems/" +
                    this.props.character_id +
                    "/" +
                    this.props.item_id,
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        attached_gems: result.data.socketed_gems,
                        loading: false,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    InventoryItemAttachedGems.prototype.renderGems = function () {
        return this.state.attached_gems.map(function (gem) {
            return React.createElement(
                BasicCard,
                { additionalClasses: "my-4" },
                React.createElement(
                    "h3",
                    { className: "my-4 text-lime-600 dark:text-lime-500" },
                    gem.name,
                ),
                React.createElement(
                    "dl",
                    null,
                    React.createElement("dt", null, "Tier"),
                    React.createElement("dd", null, gem.tier),
                    React.createElement(
                        "dt",
                        null,
                        gem.primary_atonement_name + " Atonement: ",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        (gem.primary_atonement_amount * 100).toFixed(0),
                        "%",
                    ),
                    React.createElement(
                        "dt",
                        null,
                        gem.secondary_atonement_name + " Atonement: ",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        (gem.secondary_atonement_amount * 100).toFixed(0),
                        "%",
                    ),
                    React.createElement(
                        "dt",
                        null,
                        gem.tertiary_atonement_name + " Atonement: ",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        (gem.tertiary_atonement_amount * 100).toFixed(0),
                        "%",
                    ),
                ),
            );
        });
    };
    InventoryItemAttachedGems.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: "Attached Gems",
                primary_button_disabled: this.state.loading,
            },
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : this.state.attached_gems.length > 0
                  ? React.createElement(
                        "div",
                        { className: "max-h-[350px] overflow-y-scroll" },
                        this.renderGems(),
                    )
                  : React.createElement(
                        "p",
                        { className: "my-4" },
                        "No Attached Gems",
                    ),
        );
    };
    return InventoryItemAttachedGems;
})(React.Component);
export default InventoryItemAttachedGems;
//# sourceMappingURL=inventory-item-attached-gems.js.map
