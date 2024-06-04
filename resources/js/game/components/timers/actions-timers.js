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
import clsx from "clsx";
import React from "react";
import { serviceContainer } from "../../lib/containers/core-container";
import TimerProgressBar from "../ui/progress-bars/timer-progress-bar";
import ActionTimerListeners from "./event-listeners/action-timer-listeners";
var ActionsTimers = (function (_super) {
    __extends(ActionsTimers, _super);
    function ActionsTimers(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            attack_time_out: 0,
            crafting_time_out: 0,
        };
        _this.actionTimerListeners =
            serviceContainer().fetch(ActionTimerListeners);
        _this.actionTimerListeners.initialize(_this, _this.props.user_id);
        _this.actionTimerListeners.register();
        return _this;
    }
    ActionsTimers.prototype.componentDidMount = function () {
        if (this.actionTimerListeners) {
            this.actionTimerListeners.listen();
        }
    };
    ActionsTimers.prototype.updateAttackTimer = function (timeLeft) {
        this.setState({
            attack_time_out: timeLeft,
        });
    };
    ActionsTimers.prototype.updateCraftingTimer = function (timeLeft) {
        this.setState({
            crafting_time_out: timeLeft,
        });
    };
    ActionsTimers.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "relative top-[24px]" },
            React.createElement(
                "div",
                {
                    className: clsx("grid gap-2", {
                        "grid-cols-2":
                            this.state.attack_time_out !== 0 &&
                            this.state.crafting_time_out !== 0,
                    }),
                },
                React.createElement(
                    "div",
                    null,
                    React.createElement(TimerProgressBar, {
                        time_remaining: this.state.attack_time_out,
                        time_out_label: "Attack Timeout",
                        update_time_remaining:
                            this.updateAttackTimer.bind(this),
                    }),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(TimerProgressBar, {
                        time_remaining: this.state.crafting_time_out,
                        time_out_label: "Crafting Timeout",
                        update_time_remaining:
                            this.updateCraftingTimer.bind(this),
                    }),
                ),
            ),
        );
    };
    return ActionsTimers;
})(React.Component);
export default ActionsTimers;
//# sourceMappingURL=actions-timers.js.map
