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
import React, { Fragment } from "react";
import ExplorationSection from "../exploration-section";
var SmallExplorationSection = (function (_super) {
    __extends(SmallExplorationSection, _super);
    function SmallExplorationSection(props) {
        return _super.call(this, props) || this;
    }
    SmallExplorationSection.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(ExplorationSection, {
                character: this.props.character,
                manage_exploration: this.props.close_exploration_section,
                monsters: this.props.monsters,
            }),
        );
    };
    return SmallExplorationSection;
})(React.Component);
export default SmallExplorationSection;
//# sourceMappingURL=small-exploration-section.js.map
