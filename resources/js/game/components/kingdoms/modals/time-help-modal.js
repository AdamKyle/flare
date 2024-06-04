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
import { formatNumber } from "../../../lib/game/format-number";
import HelpDialogue from "../../../components/ui/dialogue/help-dialogue";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
var TimeHelpModal = (function (_super) {
    __extends(TimeHelpModal, _super);
    function TimeHelpModal(props) {
        return _super.call(this, props) || this;
    }
    TimeHelpModal.prototype.buildSeconds = function (time) {
        if (this.props.is_in_seconds) {
            return time;
        }
        return time * 60;
    };
    TimeHelpModal.prototype.buildMinutes = function (time) {
        if (this.props.is_in_minutes) {
            return time;
        }
        return time / 60;
    };
    TimeHelpModal.prototype.buildHours = function (time) {
        if (this.props.is_in_minutes) {
            return time / 60;
        }
        var minutes = this.buildMinutes(time);
        return minutes / 60;
    };
    TimeHelpModal.prototype.buildDays = function (time) {
        var hours = this.buildHours(time);
        return hours / 24;
    };
    TimeHelpModal.prototype.render = function () {
        return React.createElement(
            HelpDialogue,
            {
                is_open: true,
                manage_modal: this.props.manage_modal,
                title: "Time Break Down",
                no_scrolling: true,
            },
            React.createElement(
                "div",
                null,
                React.createElement(
                    InfoAlert,
                    { additional_css: "my-4" },
                    React.createElement(
                        "p",
                        null,
                        "The following will show: How many days",
                        " ",
                        React.createElement("strong", null, "or"),
                        " how many hours",
                        " ",
                        React.createElement("strong", null, "or"),
                        " how many minutes",
                        " ",
                        React.createElement("strong", null, "or"),
                        " how many seconds.",
                    ),
                ),
                React.createElement(
                    "dl",
                    null,
                    React.createElement("dt", null, "Days"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.buildDays(this.props.time)),
                    ),
                    React.createElement("dt", null, "Hours"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.buildHours(this.props.time)),
                    ),
                    React.createElement("dt", null, "Minutes"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.buildMinutes(this.props.time)),
                    ),
                    React.createElement("dt", null, "Seconds"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.buildSeconds(this.props.time)),
                    ),
                ),
            ),
        );
    };
    return TimeHelpModal;
})(React.Component);
export default TimeHelpModal;
//# sourceMappingURL=time-help-modal.js.map
