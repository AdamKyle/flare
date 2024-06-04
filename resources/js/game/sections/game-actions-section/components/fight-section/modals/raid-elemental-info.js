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
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import { startCase } from "lodash";
var RaidElementInfo = (function (_super) {
    __extends(RaidElementInfo, _super);
    function RaidElementInfo(props) {
        return _super.call(this, props) || this;
    }
    RaidElementInfo.prototype.rnderHighestElementCheck = function (key) {
        if (this.props.highest_element === key) {
            return React.createElement("i", {
                className: "fas fa-check text-green-600 dark:text-green-400",
            });
        }
        return null;
    };
    RaidElementInfo.prototype.renderAtonementData = function () {
        var dlElements = [];
        for (var key in this.props.element_atonements) {
            var value = this.props.element_atonements[key];
            dlElements.push(
                React.createElement(
                    React.Fragment,
                    null,
                    React.createElement(
                        "dd",
                        null,
                        startCase(key),
                        " ",
                        this.rnderHighestElementCheck(key),
                        ":",
                    ),
                    React.createElement(
                        "dt",
                        null,
                        (value * 100).toFixed(0),
                        "%",
                    ),
                ),
            );
        }
        return dlElements;
    };
    RaidElementInfo.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.props.monster_name + " Elemental Atonement",
            },
            React.createElement(
                "p",
                { className: "my-4" },
                "Below you will find elemental atonement info about the monster in question. Matching your elemental atonement through the use of",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/gems", target: "_blank" },
                    "Gems ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                ".",
            ),
            React.createElement(
                "p",
                { className: "my-4" },
                "When an enemy attacks, they will do a % of their weapon damage as that elements damage towards you. For example if there attack is 500, and the enemies highest element is 15% in water they will do 15% of 500 towards you as water damage. If your element is Fire, they will do double that damage. If your element is Ice, they will do half damage to you.",
            ),
            React.createElement(
                "p",
                { className: "my-4" },
                "The green Checkmark beside the element name, means this is the core attacking element and you will want the oppisite element to do the most damage. For example if the enemy is Fire based, you want Water. If the element is Water you want Ice.",
            ),
            React.createElement(
                "dl",
                { className: "my-4" },
                this.renderAtonementData(),
            ),
        );
    };
    return RaidElementInfo;
})(React.Component);
export default RaidElementInfo;
//# sourceMappingURL=raid-elemental-info.js.map
