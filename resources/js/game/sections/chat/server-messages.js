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
import Messages from "./components/messages";
import ItemDetailsModal from "../../components/modals/item-details/item-details-modal";
var ServerMessages = (function (_super) {
    __extends(ServerMessages, _super);
    function ServerMessages(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            slot_id: 0,
            view_item: false,
            is_quest_item: false,
        };
        return _this;
    }
    ServerMessages.prototype.viewItem = function (slotId, isQuest) {
        this.setState({
            slot_id: typeof slotId !== "undefined" ? slotId : 0,
            view_item: !this.state.view_item,
            is_quest_item: typeof isQuest !== "undefined" ? isQuest : false,
        });
    };
    ServerMessages.prototype.buildMessages = function () {
        var _this = this;
        return this.props.server_messages.map(function (message) {
            if (message.event_id !== 0 && message.event_id !== null) {
                return React.createElement(
                    "li",
                    {
                        className:
                            "text-pink-400 my-2 break-word lg:break-normal",
                        key: message.id,
                    },
                    React.createElement(
                        "button",
                        {
                            type: "button",
                            className: "italic underline hover:text-pink-300",
                            onClick: function () {
                                return _this.viewItem(
                                    message.event_id,
                                    message.is_quest_item,
                                );
                            },
                        },
                        message.message,
                        " ",
                        React.createElement("i", { className: "ra ra-anvil" }),
                    ),
                );
            }
            return React.createElement(
                "li",
                {
                    className: "text-pink-400 my-2 break-word lg:break-normal",
                    key: message.id,
                },
                message.message,
            );
        });
    };
    ServerMessages.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(Messages, null, this.buildMessages()),
            this.state.view_item && this.state.slot_id !== 0
                ? React.createElement(ItemDetailsModal, {
                      is_dead: false,
                      is_open: this.state.view_item,
                      manage_modal: this.viewItem.bind(this),
                      character_id: this.props.character_id,
                      slot_id: this.state.slot_id,
                      is_automation_running: this.props.is_automation_running,
                  })
                : null,
        );
    };
    return ServerMessages;
})(React.Component);
export default ServerMessages;
//# sourceMappingURL=server-messages.js.map
