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
import PrimaryButton from "../../components/ui/buttons/primary-button";
import Messages from "./components/messages";
import Ajax from "../../lib/ajax/ajax";
import { generateServerMessage } from "../../lib/ajax/generate-server-message";
import clsx from "clsx";
var Chat = (function (_super) {
    __extends(Chat, _super);
    function Chat(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            message: "",
        };
        return _this;
    }
    Chat.prototype.setMessage = function (e) {
        this.setState({
            message: e.target.value,
        });
    };
    Chat.prototype.sendMessage = function (e) {
        if (typeof e !== "undefined") {
            if (e.key === "Enter") {
                if (this.props.is_silenced) {
                    return this.props.push_silenced_message();
                }
                return this.handleMessage();
            }
        }
    };
    Chat.prototype.postMessage = function () {
        return this.handleMessage();
    };
    Chat.prototype.privateMessage = function (characterName) {
        var _this = this;
        this.setState(
            {
                message: "/m " + characterName + ": ",
            },
            function () {
                _this.chatInput.focus();
            },
        );
    };
    Chat.prototype.publicEntity = function (type) {
        var teleport = false;
        if (type.includes("/pct")) {
            teleport = true;
        }
        this.setState({
            message: "",
        });
        new Ajax()
            .setRoute("public-entity")
            .setParameters({
                attempt_to_teleport: teleport,
            })
            .doAjaxCall(
                "post",
                function (success) {},
                function (error) {},
            );
    };
    Chat.prototype.handleMessage = function () {
        if (this.state.message.includes("/m")) {
            this.sendPrivateMessage();
        } else if (
            this.state.message.includes("/pct") ||
            this.state.message.includes("/pc")
        ) {
            this.publicEntity(this.state.message);
        } else {
            this.sendPublicMessage();
        }
    };
    Chat.prototype.sendPublicMessage = function () {
        var _this = this;
        if (this.state.message === "") {
            return generateServerMessage("message_length_0");
        }
        if (this.props.is_silenced) {
            return this.props.push_silenced_message();
        }
        if (this.state.message.length > 240) {
            return this.props.push_error_message(
                "Woah! message is longer then 240 characters. Lets not get crazy now child!",
            );
        }
        this.setState({
            message: "",
        });
        new Ajax()
            .setRoute("public-message")
            .setParameters({
                message: this.state.message,
            })
            .doAjaxCall(
                "post",
                function (result) {},
                function (error) {
                    _this.handleMessageErrors(error);
                },
            );
    };
    Chat.prototype.sendPrivateMessage = function () {
        var _this = this;
        var messageData = this.state.message.match(
            /^\/m\s+(\w+[\w| ]*):\s*(.*)/,
        );
        if (messageData === null) {
            return generateServerMessage("message_length_0");
        }
        if (this.props.is_silenced) {
            return this.props.push_silenced_message();
        }
        this.props.push_private_message_sent(messageData);
        this.setState({
            message: "",
        });
        new Ajax()
            .setRoute("private-message")
            .setParameters({
                user_name: messageData[1],
                message: messageData[2],
            })
            .doAjaxCall(
                "post",
                function (result) {},
                function (error) {
                    _this.handleMessageErrors(error);
                },
            );
    };
    Chat.prototype.handleMessageErrors = function (error) {
        var response = undefined;
        if (error.hasOwnProperty("response")) {
            response = error.response;
        }
        if (
            (response === null || response === void 0
                ? void 0
                : response.status) === 429
        ) {
            generateServerMessage("chatting_to_much");
        }
        this.props.set_tab_to_updated("server-messages");
    };
    Chat.prototype.renderLocation = function (message) {
        if (message.x === 0 && message.y === 0) {
            return React.createElement(
                Fragment,
                null,
                message.time_stamp,
                " ",
                React.createElement("i", { className: "fas fa-skull" }),
            );
        } else if (message.hide_location) {
            return (
                message.time_stamp + " [" + message.map_name + " " + "***/***]"
            );
        }
        return (
            message.time_stamp +
            " [" +
            message.map_name +
            " " +
            message.x +
            "/" +
            message.y +
            "]"
        );
    };
    Chat.prototype.renderNameTag = function (message) {
        if (message.name_tag === null) {
            return null;
        }
        return React.createElement(
            React.Fragment,
            null,
            "The ",
            React.createElement(
                "span",
                { className: "italic" },
                message.name_tag,
            ),
        );
    };
    Chat.prototype.renderChatMessages = function () {
        var self = this;
        return this.props.chat.map(function (message) {
            switch (message.type) {
                case "chat":
                    if (message.character_name === "The Creator") {
                        return React.createElement(
                            "li",
                            {
                                className:
                                    "mb-2 break-word md:break-normal text-yellow-300 text-xl bold",
                            },
                            "The Creator: ",
                            message.message,
                        );
                    }
                    if (message.chat_text_color !== null) {
                        var color = message.chat_text_color;
                        return React.createElement(
                            "li",
                            {
                                className: clsx(
                                    "mb-2 break-word md:break-normal " + color,
                                    {
                                        "font-extrabold": message.chat_is_bold,
                                        italic: message.chat_is_italic,
                                    },
                                ),
                            },
                            self.renderLocation(message),
                            " ",
                            React.createElement(
                                "button",
                                {
                                    type: "button",
                                    className: "underline",
                                    onClick: function () {
                                        return self.privateMessage(
                                            message.character_name,
                                        );
                                    },
                                },
                                " ",
                                message.character_name,
                                " ",
                                self.renderNameTag(message),
                            ),
                            ": ",
                            message.message,
                        );
                    }
                    return React.createElement(
                        "li",
                        {
                            style: { color: message.color },
                            className: "mb-2 break-word md:break-normal",
                        },
                        self.renderLocation(message),
                        " ",
                        React.createElement(
                            "button",
                            {
                                type: "button",
                                className: "underline",
                                onClick: function () {
                                    return self.privateMessage(
                                        message.character_name,
                                    );
                                },
                            },
                            message.character_name,
                            " ",
                            self.renderNameTag(message),
                        ),
                        ": ",
                        message.message,
                    );
                case "private-message-sent":
                    return React.createElement(
                        "li",
                        {
                            className:
                                "text-fuchsia-400 italic mb-2 break-word md:break-normal",
                        },
                        message.message,
                    );
                case "private-message-received":
                    if (message.from === "The Creator") {
                        return React.createElement(
                            "li",
                            {
                                className:
                                    "text-fuchsia-300 text-xl italic mb-2 break-word md:break-normal",
                            },
                            message.from,
                            ": ",
                            message.message,
                        );
                    }
                    return React.createElement(
                        "li",
                        {
                            className:
                                "text-fuchsia-300 italic mb-2 break-words md:break-normal",
                        },
                        React.createElement(
                            "button",
                            {
                                type: "button",
                                className: "underline",
                                onClick: function () {
                                    return self.privateMessage(message.from);
                                },
                            },
                            message.from,
                        ),
                        ": ",
                        message.message,
                    );
                case "error-message":
                    return React.createElement(
                        "li",
                        {
                            className:
                                "text-red-400 bold mb-2 break-word md:break-normal",
                        },
                        message.message,
                    );
                case "creator-message":
                    return React.createElement(
                        "li",
                        {
                            className:
                                "text-yellow-300 text-xl bold mb-2 break-word md:break-normal",
                        },
                        message.character_name,
                        ": ",
                        message.message,
                    );
                case "global-message":
                    return React.createElement(
                        "li",
                        {
                            className:
                                "text-yellow-400 bold italic mb-2 break-word md:break-normal",
                        },
                        message.message,
                    );
                case "raid-global-message":
                    return React.createElement(
                        "li",
                        {
                            className:
                                "text-regent-st-blue-300 bold italic mb-2 break-word md:break-normal",
                        },
                        message.message,
                    );
                case "npc-message":
                    return React.createElement(
                        "li",
                        {
                            className:
                                "text-sky-400 mb-2 break-word md:break-normal",
                        },
                        message.message,
                    );
                default:
                    return null;
            }
        });
    };
    Chat.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "flex items-center mb-4" },
                React.createElement(
                    "div",
                    { className: "grow pr-4" },
                    React.createElement("input", {
                        type: "text",
                        name: "chat",
                        className: "form-control",
                        onChange: this.setMessage.bind(this),
                        onKeyDown: this.sendMessage.bind(this),
                        value: this.state.message,
                        ref: function (input) {
                            _this.chatInput = input;
                        },
                    }),
                ),
                React.createElement(
                    "div",
                    { className: "flex-none" },
                    React.createElement(PrimaryButton, {
                        button_label: "Send",
                        on_click: this.postMessage.bind(this),
                    }),
                ),
            ),
            React.createElement(
                "div",
                null,
                React.createElement(Messages, null, this.renderChatMessages()),
            ),
        );
    };
    return Chat;
})(React.Component);
export default Chat;
//# sourceMappingURL=chat.js.map
