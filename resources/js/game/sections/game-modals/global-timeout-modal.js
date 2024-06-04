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
import Dialogue from "../../components/ui/dialogue/dialogue";
import TimerProgressBar from "../../components/ui/progress-bars/timer-progress-bar";
var GlobalTimeoutModal = (function (_super) {
    __extends(GlobalTimeoutModal, _super);
    function GlobalTimeoutModal(props) {
        return _super.call(this, props) || this;
    }
    GlobalTimeoutModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            { is_open: true, title: "You're in timeout!" },
            React.createElement(
                "p",
                { className: "my-4" },
                "Child! You need to slow down. You have been so busy you spun your self into a dizzying fit of madness. Take a moment child and rest.",
            ),
            React.createElement(
                "p",
                { className: "my-4 text-red-600 dark:text-red-400" },
                "You have been timed out for two minutes. Refresh and your timer restarts. Try and get around this and you'll be banned. Accept your punishment child and slow down.",
            ),
            React.createElement(TimerProgressBar, {
                time_remaining: 120,
                time_out_label: "Timeout time remaining.",
                update_time_remaining: function () {},
            }),
        );
    };
    return GlobalTimeoutModal;
})(React.Component);
export default GlobalTimeoutModal;
//# sourceMappingURL=global-timeout-modal.js.map
