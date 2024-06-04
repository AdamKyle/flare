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
import SuccessOutlineButton from "../../../game/components/ui/buttons/success-outline-button";
import GuideQuest from "./modals/guide-quest";
import { viewPortWatcher } from "../../../game/lib/view-port-watcher";
import { guideQuestServiceContainer } from "./container/guide-quest-container";
import GuideQuestListener from "./event-listeners/guide-quest-listener";
import clsx from "clsx";
import CompletedGuideQuestListener from "./event-listeners/completed-guide-quest-listener";
var GuideButton = (function (_super) {
    __extends(GuideButton, _super);
    function GuideButton(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_modal_open: false,
            show_button: true,
            show_guide_quest_completed: false,
            view_port: 0,
        };
        _this.guideQuestListener =
            guideQuestServiceContainer().fetch(GuideQuestListener);
        _this.guideQuestListener.initialize(_this, _this.props.user_id);
        _this.guideQuestCompletedListener = guideQuestServiceContainer().fetch(
            CompletedGuideQuestListener,
        );
        _this.guideQuestCompletedListener.initialize(
            _this,
            _this.props.user_id,
        );
        _this.guideQuestListener.register();
        _this.guideQuestCompletedListener.register();
        return _this;
    }
    GuideButton.prototype.componentDidMount = function () {
        var _this = this;
        this.setState(
            {
                view_port: window.innerWidth,
            },
            function () {
                viewPortWatcher(_this);
            },
        );
        var self = this;
        setTimeout(
            function () {
                if (self.props.force_open_modal) {
                    self.setState({
                        is_modal_open: true,
                    });
                }
            },
            import.meta.env.VITE_APP_ENV === "production" ? 3500 : 500,
        );
        this.guideQuestListener.listen();
        this.guideQuestCompletedListener.listen();
    };
    GuideButton.prototype.manageGuideQuestModal = function () {
        this.setState({
            is_modal_open: !this.state.is_modal_open,
        });
    };
    GuideButton.prototype.render = function () {
        if (!this.state.show_button) {
            return null;
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "relative" },
                React.createElement(
                    "span",
                    {
                        className: clsx(
                            "fa-stack absolute top-[-10px] left-[-15px]",
                            {
                                hidden: !this.state.show_guide_quest_completed,
                            },
                        ),
                    },
                    React.createElement("i", {
                        className:
                            "fas fa-circle fa-stack-2x text-red-700 dark:text-red-500 fa-beat",
                    }),
                    React.createElement("i", {
                        className:
                            "fas fa-exclamation fa-stack-1x text-yellow-500 dark:text-yello-700",
                    }),
                ),
                React.createElement(SuccessOutlineButton, {
                    button_label: "Guide Quests",
                    on_click: this.manageGuideQuestModal.bind(this),
                    additional_css: "mr-4",
                }),
            ),
            this.state.is_modal_open
                ? React.createElement(GuideQuest, {
                      is_open: this.state.is_modal_open,
                      manage_modal: this.manageGuideQuestModal.bind(this),
                      user_id: this.props.user_id,
                      view_port: this.state.view_port,
                  })
                : null,
        );
    };
    return GuideButton;
})(React.Component);
export default GuideButton;
//# sourceMappingURL=guide-button.js.map
