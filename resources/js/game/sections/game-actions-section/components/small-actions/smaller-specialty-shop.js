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
import Shop from "../specialty-shops/shop";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
var SmallerSpecialtyShop = (function (_super) {
    __extends(SmallerSpecialtyShop, _super);
    function SmallerSpecialtyShop(props) {
        return _super.call(this, props) || this;
    }
    SmallerSpecialtyShop.prototype.getTypeOfShop = function () {
        if (this.props.show_hell_forged_section) {
            return "Hell Forged";
        }
        if (this.props.show_purgatory_chains_section) {
            return "Purgatory Chains";
        }
        if (this.props.show_twisted_earth_section) {
            return "Twisted Earth";
        }
        return null;
    };
    SmallerSpecialtyShop.prototype.render = function () {
        var type = this.getTypeOfShop();
        if (type === null) {
            return React.createElement(
                DangerAlert,
                null,
                "Unknown type of shop to render. Something is wrong.",
            );
        }
        return React.createElement(Shop, {
            type: type,
            character_id: this.props.character.id,
            close_hell_forged: this.props.manage_hell_forged_shop.bind(this),
            close_purgatory_chains:
                this.props.manage_purgatory_chain_shop.bind(this),
        });
    };
    return SmallerSpecialtyShop;
})(React.Component);
export default SmallerSpecialtyShop;
//# sourceMappingURL=smaller-specialty-shop.js.map
