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
var __read =
    (this && this.__read) ||
    function (o, n) {
        var m = typeof Symbol === "function" && o[Symbol.iterator];
        if (!m) return o;
        var i = m.call(o),
            r,
            ar = [],
            e;
        try {
            while ((n === void 0 || n-- > 0) && !(r = i.next()).done)
                ar.push(r.value);
        } catch (error) {
            e = { error: error };
        } finally {
            try {
                if (r && !r.done && (m = i["return"])) m.call(i);
            } finally {
                if (e) throw e.error;
            }
        }
        return ar;
    };
import React, { Fragment } from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import RenderAtonementDetails from "../../components/gems/components/render-atonement-details";
import clsx from "clsx";
var InventoryItemGemDetails = (function (_super) {
    __extends(InventoryItemGemDetails, _super);
    function InventoryItemGemDetails(props) {
        return _super.call(this, props) || this;
    }
    InventoryItemGemDetails.prototype.atonementChanges = function (
        originalAtonement,
        equippedAtonement,
    ) {
        var _this = this;
        var atonements = Object.entries(originalAtonement.atonements).map(
            function (_a) {
                var _b;
                var _c = __read(_a, 2),
                    key = _c[0],
                    value = _c[1];
                return (_b = {}), (_b[key] = value), _b;
            },
        );
        var equippedAtonements = Object.entries(equippedAtonement).map(
            function (_a) {
                var _b;
                var _c = __read(_a, 2),
                    key = _c[0],
                    value = _c[1];
                return (_b = {}), (_b[key] = value), _b;
            },
        );
        var elements = [];
        atonements.forEach(function (atonement) {
            var atonementName = Object.keys(atonement)[0];
            var atonementValue = atonement[atonementName];
            var value = _this.findAtonementForChange(
                equippedAtonements,
                atonementName,
            );
            var total = parseFloat(atonementValue);
            if (typeof total === "undefined") {
                elements.push(
                    React.createElement(
                        Fragment,
                        null,
                        React.createElement("dt", null, atonementName),
                        React.createElement(
                            "dd",
                            { className: "text-green-700 dark:text-green-500" },
                            "+",
                            (total * 100).toFixed(0),
                            "%",
                        ),
                    ),
                );
            }
            if (total > value) {
                elements.push(
                    React.createElement(
                        Fragment,
                        null,
                        React.createElement("dt", null, atonementName),
                        React.createElement(
                            "dd",
                            { className: "text-green-700 dark:text-green-500" },
                            "+",
                            (value === 0
                                ? total * 100
                                : (total - value) * 100
                            ).toFixed(0),
                            "%",
                        ),
                    ),
                );
            }
            if (value < total) {
                elements.push(
                    React.createElement(
                        Fragment,
                        null,
                        React.createElement("dt", null, atonementName),
                        React.createElement(
                            "dd",
                            { className: "text-red-700 dark:text-red-500" },
                            "-",
                            ((total - value) * 100).toFixed(0),
                            "%",
                        ),
                    ),
                );
            }
            elements.push(
                React.createElement(
                    Fragment,
                    null,
                    React.createElement("dt", null, atonementName),
                    React.createElement(
                        "dd",
                        null,
                        (total * 100).toFixed(0),
                        "%",
                    ),
                ),
            );
        });
        return elements;
    };
    InventoryItemGemDetails.prototype.findAtonementForChange = function (
        equippedAtonements,
        atonementName,
    ) {
        var matchedValue = 0;
        equippedAtonements.forEach(function (equipped) {
            if (equipped.hasOwnProperty(atonementName)) {
                matchedValue = equipped[atonementName];
            }
        });
        return matchedValue;
    };
    InventoryItemGemDetails.prototype.renderAtonementChanges = function (
        originalAtonement,
        equippedAtonement,
    ) {
        if (typeof equippedAtonement === "undefined") {
            return;
        }
        return React.createElement(
            BasicCard,
            null,
            React.createElement(
                "h4",
                { className: "my-4" },
                equippedAtonement.item_name,
                " Atonement Adjustment",
            ),
            React.createElement(
                "dl",
                null,
                this.atonementChanges(
                    originalAtonement,
                    equippedAtonement.data.atonements,
                ),
            ),
        );
    };
    InventoryItemGemDetails.prototype.render = function () {
        return React.createElement(
            "div",
            {
                className: clsx({
                    "grid lg:grid-cols-2 gap-2 max-h-[150px] lg:max-h-full overflow-y-scroll lg:overflow-y-visible":
                        typeof this.props.equipped_atonements[0] !==
                        "undefined",
                }),
            },
            React.createElement(
                "div",
                null,
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement(RenderAtonementDetails, {
                        title: "This Items Atonement",
                        original_atonement: this.props.item_atonement,
                    }),
                    React.createElement(
                        "h4",
                        { className: "my-4" },
                        "Elemental Atonement",
                    ),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Primary Element"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item_atonement.elemental_damage.name,
                        ),
                        React.createElement("dt", null, "Elemental Damage"),
                        React.createElement(
                            "dd",
                            null,
                            (
                                this.props.item_atonement.elemental_damage
                                    .amount * 100
                            ).toFixed(0),
                            "%",
                        ),
                    ),
                ),
            ),
            typeof this.props.equipped_atonements[0] !== "undefined"
                ? React.createElement(
                      "div",
                      null,
                      this.renderAtonementChanges(
                          this.props.item_atonement,
                          this.props.equipped_atonements[0],
                      ),
                      typeof this.props.equipped_atonements[1] !== "undefined"
                          ? React.createElement(
                                Fragment,
                                null,
                                React.createElement("div", {
                                    className:
                                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                                }),
                                this.renderAtonementChanges(
                                    this.props.item_atonement,
                                    this.props.equipped_atonements[1],
                                ),
                            )
                          : null,
                  )
                : null,
        );
    };
    return InventoryItemGemDetails;
})(React.Component);
export default InventoryItemGemDetails;
//# sourceMappingURL=inventory-item-gem-details.js.map
