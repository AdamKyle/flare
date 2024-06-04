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
import clsx from "clsx";
var BattleMesages = (function (_super) {
    __extends(BattleMesages, _super);
    function BattleMesages(props) {
        return _super.call(this, props) || this;
    }
    BattleMesages.prototype.typeCheck = function (battleType, type) {
        return battleType === type;
    };
    BattleMesages.prototype.render = function () {
        var _this = this;
        return this.props.battle_messages.map(function (battleMessage) {
            return React.createElement(
                "p",
                {
                    className: clsx(
                        {
                            "text-green-700 dark:text-green-400":
                                _this.typeCheck(
                                    battleMessage.type,
                                    "player-action",
                                ),
                        },
                        {
                            "text-red-500 dark:text-red-400": _this.typeCheck(
                                battleMessage.type,
                                "enemy-action",
                            ),
                        },
                        {
                            "text-blue-500 dark:text-blue-400": _this.typeCheck(
                                battleMessage.type,
                                "regular",
                            ),
                        },
                    ),
                },
                battleMessage.message,
            );
        });
    };
    return BattleMesages;
})(React.Component);
export default BattleMesages;
//# sourceMappingURL=battle-mesages.js.map
