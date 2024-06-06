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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
var __read =
    (this && this.__read) ||
    function (o, n) {
        var m = typeof Symbol === "function" && o[Symbol.iterator];
        if (!m) return o;
        var i = m.call(o),
            r,
            ar = [],
            e;
        try {
            while ((n === void 0 || n-- > 0) && !(r = i.next()).done)
                ar.push(r.value);
        } catch (error) {
            e = { error: error };
        } finally {
            try {
                if (r && !r.done && (m = i["return"])) m.call(i);
            } finally {
                if (e) throw e.error;
            }
        }
        return ar;
    };
var __spreadArray =
    (this && this.__spreadArray) ||
    function (to, from, pack) {
        if (pack || arguments.length === 2)
            for (var i = 0, l = from.length, ar; i < l; i++) {
                if (ar || !(i in from)) {
                    if (!ar) ar = Array.prototype.slice.call(from, 0, i);
                    ar[i] = from[i];
                }
            }
        return to.concat(ar || Array.prototype.slice.call(from));
    };
import React, { Fragment } from "react";
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";
import Ajax from "../../lib/ajax/ajax";
import ServerMessages from "./server-messages";
import Chat from "./chat";
import ExplorationMessages from "./exploration-messages";
import { DateTime } from "luxon";
import AnnouncementMessages from "./announcement-messages";
import DropDown from "../../components/ui/drop-down/drop-down";
var GameChat = (function (_super) {
    __extends(GameChat, _super);
    function GameChat(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            chat: [],
            announcements: [],
            server_messages: [],
            exploration_messages: [],
            message: "",
            is_silenced: false,
            can_talk_again_at: null,
            selected_chat: "Chat",
            updated_tabs: [],
            tabs: [
                {
                    key: "chat",
                    name: "Chat",
                    updated: false,
                },
                {
                    key: "server-messages",
                    name: "Server Message",
                    updated: false,
                },
                {
                    key: "exploration-messages",
                    name: "Exploration",
                    updated: false,
                },
                {
                    key: "announcements-messages",
                    name: "Announcements",
                    updated: false,
                },
            ],
        };
        _this.chat = Echo.join("chat");
        _this.serverMessages = Echo.private(
            "server-message-" + _this.props.user_id,
        );
        _this.explorationMessage = Echo.private(
            "exploration-log-update-" + _this.props.user_id,
        );
        _this.privateMessages = Echo.private(
            "private-message-" + _this.props.user_id,
        );
        _this.npcMessage = Echo.private("npc-message-" + _this.props.user_id);
        _this.globalMessage = Echo.join("global-message");
        _this.announcements = Echo.join("announcement-message");
        _this.deleteAnnouncements = Echo.join("delete-announcement-message");
        return _this;
    }
    GameChat.prototype.componentDidMount = function () {
        var _this = this;
        this.setState({
            is_silenced: this.props.is_silenced,
            can_talk_again_at: this.props.can_talk_again_at,
        });
        new Ajax().setRoute("last-chats").doAjaxCall(
            "get",
            function (result) {
                _this.setState({
                    announcements: result.data.announcements,
                });
                var chats = result.data.chat_messages
                    .map(function (chat) {
                        if (chat.name === "The Creator") {
                            return {
                                message: chat.message,
                                character_name: "The Creator",
                                type: "creator-message",
                            };
                        } else {
                            return {
                                color: chat.color,
                                map_name: chat.map,
                                character_name: chat.name,
                                name_tag: chat.name_tag,
                                message: chat.message,
                                x: chat.x_position,
                                y: chat.y_position,
                                type: "chat",
                                hide_location: chat.hide_location,
                                time_stamp: DateTime.fromISO(
                                    chat.created_at,
                                ).toLocaleString(DateTime.DATETIME_MED),
                                chat_text_color: chat.custom_class,
                                chat_is_bold: chat.is_chat_bold,
                                chat_is_italic: chat.is_chat_italic,
                            };
                        }
                    })
                    .filter(function (chat) {
                        return typeof chat !== "undefined";
                    });
                _this.setState(
                    {
                        chat: __spreadArray(
                            __spreadArray([], __read(_this.state.chat), false),
                            __read(chats),
                            false,
                        ),
                        announcements: result.data.announcements,
                        updated_tabs:
                            _this.canUpdateTabs("Announcements") &&
                            result.data.announcements.length > 0
                                ? __spreadArray(
                                      __spreadArray(
                                          [],
                                          __read(_this.state.updated_tabs),
                                          false,
                                      ),
                                      ["Announcements"],
                                      false,
                                  )
                                : _this.state.updated_tabs,
                    },
                    function () {
                        if (
                            typeof _this.props.update_finished_loading !==
                            "undefined"
                        ) {
                            _this.props.update_finished_loading();
                        }
                    },
                );
            },
            function (error) {
                console.error(error);
            },
        );
        this.explorationMessage.listen(
            "Game.Exploration.Events.ExplorationLogUpdate",
            function (event) {
                var messages = JSON.parse(
                    JSON.stringify(_this.state.exploration_messages),
                );
                if (messages.length > 1000) {
                    messages.length = 250;
                }
                messages.unshift({
                    id: (Math.random() + 1).toString(36).substring(7),
                    message: event.message,
                    make_italic: event.makeItalic,
                    is_reward: event.isReward,
                });
                _this.setState(
                    {
                        exploration_messages: messages,
                        updated_tabs: _this.canUpdateTabs("Exploration")
                            ? __spreadArray(
                                  __spreadArray(
                                      [],
                                      __read(_this.state.updated_tabs),
                                      false,
                                  ),
                                  ["Exploration"],
                                  false,
                              )
                            : _this.state.updated_tabs,
                    },
                    function () {
                        _this.setTabToUpdated("exploration-messages");
                    },
                );
            },
        );
        this.serverMessages.listen(
            "Game.Messages.Events.ServerMessageEvent",
            function (event) {
                if (event.message === "") {
                    return;
                }
                var messages = JSON.parse(
                    JSON.stringify(_this.state.server_messages),
                );
                if (messages.length > 1000) {
                    messages.length = 250;
                }
                messages.unshift({
                    id:
                        event.id +
                        "-" +
                        (Math.random() + 1).toString(36).substring(7),
                    message: event.message,
                    event_id: event.id,
                });
                _this.setState(
                    {
                        server_messages: messages,
                        updated_tabs: _this.canUpdateTabs("Server Messages")
                            ? __spreadArray(
                                  __spreadArray(
                                      [],
                                      __read(_this.state.updated_tabs),
                                      false,
                                  ),
                                  ["Server Messages"],
                                  false,
                              )
                            : _this.state.updated_tabs,
                    },
                    function () {
                        _this.setTabToUpdated("server-messages");
                    },
                );
            },
        );
        this.npcMessage.listen(
            "Game.Messages.Events.NPCMessageEvent",
            function (event) {
                var chat = JSON.parse(JSON.stringify(_this.state.chat));
                chat.unshift({
                    message: event.message,
                    character_name: event.npcName,
                    type: "npc-message",
                });
                _this.setState({
                    chat: chat,
                    updated_tabs: _this.canUpdateTabs("Chat")
                        ? __spreadArray(
                              __spreadArray(
                                  [],
                                  __read(_this.state.updated_tabs),
                                  false,
                              ),
                              ["Chat"],
                              false,
                          )
                        : _this.state.updated_tabs,
                });
            },
        );
        this.chat.listen(
            "Game.Messages.Events.MessageSentEvent",
            function (event) {
                var chat = JSON.parse(JSON.stringify(_this.state.chat));
                if (chat.length > 1000) {
                    chat.length = 500;
                }
                if (event.name === "The Creator") {
                    chat.unshift({
                        message: event.message.message,
                        character_name: "The Creator",
                        type: "creator-message",
                    });
                } else {
                    chat.unshift({
                        color: event.message.color,
                        map_name: event.message.map_name,
                        character_name: event.name,
                        name_tag: event.nameTag,
                        message: event.message.message,
                        x: event.message.x_position,
                        y: event.message.y_position,
                        hide_location: event.message.hide_location,
                        time_stamp: DateTime.fromISO(
                            event.message.created_at,
                        ).toLocaleString(DateTime.DATETIME_MED),
                        type: "chat",
                        chat_text_color: event.message.custom_class,
                        chat_is_bold: event.message.is_chat_bold,
                        chat_is_italic: event.message.is_chat_italic,
                    });
                }
                _this.setState(
                    {
                        chat: chat,
                        updated_tabs: _this.canUpdateTabs("Chat")
                            ? __spreadArray(
                                  __spreadArray(
                                      [],
                                      __read(_this.state.updated_tabs),
                                      false,
                                  ),
                                  ["Chat"],
                                  false,
                              )
                            : _this.state.updated_tabs,
                    },
                    function () {
                        _this.setTabToUpdated("chat");
                    },
                );
            },
        );
        this.privateMessages.listen(
            "Game.Messages.Events.PrivateMessageEvent",
            function (event) {
                var chat = JSON.parse(JSON.stringify(_this.state.chat));
                if (chat.length > 1000) {
                    chat.length = 500;
                }
                chat.unshift({
                    message: event.message,
                    type: "private-message-received",
                    from: event.from,
                });
                _this.setState(
                    {
                        chat: chat,
                        updated_tabs: _this.canUpdateTabs("Chat")
                            ? __spreadArray(
                                  __spreadArray(
                                      [],
                                      __read(_this.state.updated_tabs),
                                      false,
                                  ),
                                  ["Chat"],
                                  false,
                              )
                            : _this.state.updated_tabs,
                    },
                    function () {
                        _this.setTabToUpdated("chat");
                    },
                );
            },
        );
        this.globalMessage.listen(
            "Game.Messages.Events.GlobalMessageEvent",
            function (event) {
                var chat = JSON.parse(JSON.stringify(_this.state.chat));
                if (chat.length > 1000) {
                    chat.length = 500;
                }
                chat.unshift({
                    message: event.message,
                    type: event.specialColor,
                });
                _this.setState(
                    {
                        chat: chat,
                        updated_tabs: _this.canUpdateTabs("Chat")
                            ? __spreadArray(
                                  __spreadArray(
                                      [],
                                      __read(_this.state.updated_tabs),
                                      false,
                                  ),
                                  ["Chat"],
                                  false,
                              )
                            : _this.state.updated_tabs,
                    },
                    function () {
                        _this.setTabToUpdated("chat");
                    },
                );
            },
        );
        this.announcements.listen(
            "Game.Messages.Events.AnnouncementMessageEvent",
            function (event) {
                var announcements = JSON.parse(
                    JSON.stringify(_this.state.announcements),
                );
                announcements.unshift({
                    message: event.message,
                    id: event.id,
                });
                _this.setState(
                    {
                        announcements: announcements,
                        updated_tabs: _this.canUpdateTabs("Announcements")
                            ? __spreadArray(
                                  __spreadArray(
                                      [],
                                      __read(_this.state.updated_tabs),
                                      false,
                                  ),
                                  ["Announcements"],
                                  false,
                              )
                            : _this.state.updated_tabs,
                    },
                    function () {
                        _this.setTabToUpdated("announcements-messages");
                    },
                );
            },
        );
        this.deleteAnnouncements.listen(
            "Game.Messages.Events.DeleteAnnouncementEvent",
            function (event) {
                var announcements = JSON.parse(
                    JSON.stringify(_this.state.announcements),
                );
                var updatedAnnouncements = announcements.filter(
                    function (announcement) {
                        return announcement.id !== event.id;
                    },
                );
                _this.setState({
                    announcements: updatedAnnouncements,
                });
            },
        );
    };
    GameChat.prototype.canUpdateTabs = function (name) {
        return (
            this.state.selected_chat !== name &&
            !this.state.updated_tabs.includes(name)
        );
    };
    GameChat.prototype.componentDidUpdate = function (
        prevProps,
        prevState,
        snapshot,
    ) {
        if (this.props.is_silenced === null) {
            return;
        }
        if (this.props.is_silenced !== this.state.is_silenced) {
            this.setState({
                is_silenced: this.props.is_silenced,
                can_talk_again_at: this.props.can_talk_again_at,
            });
        }
    };
    GameChat.prototype.pushSilencedMethod = function () {
        if (this.state.is_silenced) {
            var chat = JSON.parse(JSON.stringify(this.state.chat));
            if (chat.length > 1000) {
                chat.length = 500;
            }
            chat.unshift({
                message:
                    "You child, have been chatting up a storm. Slow down. I'll let you know whe you can talk again ...",
                type: "error-message",
            });
            this.setState({
                chat: chat,
            });
        }
    };
    GameChat.prototype.pushPrivateMessageSent = function (messageData) {
        var chat = JSON.parse(JSON.stringify(this.state.chat));
        if (chat.length >= 1000) {
            chat.length = 500;
        }
        chat.unshift({
            message: "Sent to " + messageData[1] + ": " + messageData[2],
            type: "private-message-sent",
        });
        this.setState({
            message: "",
            chat: chat,
        });
    };
    GameChat.prototype.pushErrorMessage = function (message) {
        var chat = JSON.parse(JSON.stringify(this.state.chat));
        if (chat.length > 1000) {
            chat.length = 500;
        }
        chat.unshift({
            message: message,
            type: "error-message",
        });
        this.setState({
            chat: chat,
        });
    };
    GameChat.prototype.resetTabChange = function (key) {
        var tabs = this.state.tabs.map(function (tab) {
            return tab.key === key
                ? __assign(__assign({}, tab), { updated: false })
                : tab;
        });
        this.setState({
            tabs: tabs,
        });
    };
    GameChat.prototype.setTabToUpdated = function (key) {
        var tabs = this.state.tabs.map(function (tab) {
            return tab.key === key
                ? __assign(__assign({}, tab), { updated: true })
                : tab;
        });
        this.setState({
            tabs: tabs,
        });
    };
    GameChat.prototype.switchChat = function (type) {
        this.setState({
            selected_chat: type,
            updated_tabs: this.state.updated_tabs.filter(function (name) {
                return name !== type;
            }),
        });
    };
    GameChat.prototype.renderDropDownOptions = function () {
        var _this = this;
        return [
            {
                name: "Chat",
                icon_class: "fas fa-comments",
                on_click: function () {
                    return _this.switchChat("Chat");
                },
            },
            {
                name: "Server Messages",
                icon_class: "fas fa-server",
                on_click: function () {
                    return _this.switchChat("Server Messages");
                },
            },
            {
                name: "Exploration",
                icon_class: "fas fa-dragon",
                on_click: function () {
                    return _this.switchChat("Exploration");
                },
            },
            {
                name: "Announcements",
                icon_class: "fas fa-scroll",
                on_click: function () {
                    return _this.switchChat("Announcements");
                },
            },
        ];
    };
    GameChat.prototype.renderSelectedTab = function () {
        switch (this.state.selected_chat) {
            case "Announcements":
                return React.createElement(AnnouncementMessages, {
                    announcements: this.state.announcements,
                });
            case "Exploration":
                return React.createElement(ExplorationMessages, {
                    exploration_messages: this.state.exploration_messages,
                });
            case "Server Messages":
                return React.createElement(ServerMessages, {
                    server_messages: this.state.server_messages,
                    character_id: this.props.character_id,
                    view_port: this.props.view_port,
                    is_automation_running: this.props.is_automation_running,
                });
            case "Chat":
            default:
                return React.createElement(Chat, {
                    is_silenced: this.props.is_silenced,
                    can_talk_again_at: this.props.can_talk_again_at,
                    chat: this.state.chat,
                    set_tab_to_updated: this.setTabToUpdated.bind(this),
                    push_silenced_message: this.pushSilencedMethod.bind(this),
                    push_private_message_sent:
                        this.pushPrivateMessageSent.bind(this),
                    push_error_message: this.pushErrorMessage.bind(this),
                });
        }
    };
    GameChat.prototype.render = function () {
        if (this.props.is_admin) {
            return React.createElement(Chat, {
                is_silenced: this.props.is_silenced,
                chat: this.state.chat,
                can_talk_again_at: this.props.can_talk_again_at,
                set_tab_to_updated: this.setTabToUpdated.bind(this),
                push_silenced_message: this.pushSilencedMethod.bind(this),
                push_private_message_sent:
                    this.pushPrivateMessageSent.bind(this),
                push_error_message: this.pushErrorMessage.bind(this),
            });
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "mt-4 mb-4 md:hidden" },
                React.createElement(DropDown, {
                    menu_items: this.renderDropDownOptions(),
                    button_title: "Chat Tabs",
                    selected_name: this.state.selected_chat,
                    show_alert: true,
                    alert_names: this.state.updated_tabs,
                }),
                React.createElement(
                    "div",
                    { className: "my-4" },
                    this.renderSelectedTab(),
                ),
            ),
            React.createElement(
                "div",
                { className: "mt-4 mb-4 hidden md:block" },
                React.createElement(
                    Tabs,
                    {
                        tabs: this.state.tabs,
                        icon_key: "updated",
                        when_tab_changes: this.resetTabChange.bind(this),
                    },
                    React.createElement(
                        TabPanel,
                        { key: "chat" },
                        React.createElement(Chat, {
                            is_silenced: this.props.is_silenced,
                            can_talk_again_at: this.props.can_talk_again_at,
                            chat: this.state.chat,
                            set_tab_to_updated: this.setTabToUpdated.bind(this),
                            push_silenced_message:
                                this.pushSilencedMethod.bind(this),
                            push_private_message_sent:
                                this.pushPrivateMessageSent.bind(this),
                            push_error_message:
                                this.pushErrorMessage.bind(this),
                        }),
                    ),
                    React.createElement(
                        TabPanel,
                        { key: "server-messages" },
                        React.createElement(ServerMessages, {
                            server_messages: this.state.server_messages,
                            character_id: this.props.character_id,
                            view_port: this.props.view_port,
                            is_automation_running:
                                this.props.is_automation_running,
                        }),
                    ),
                    React.createElement(
                        TabPanel,
                        { key: "exploration-messages" },
                        React.createElement(ExplorationMessages, {
                            exploration_messages:
                                this.state.exploration_messages,
                        }),
                    ),
                    React.createElement(
                        TabPanel,
                        { key: "announcements-messages" },
                        React.createElement(AnnouncementMessages, {
                            announcements: this.state.announcements,
                        }),
                    ),
                ),
            ),
        );
    };
    return GameChat;
})(React.Component);
export default GameChat;
//# sourceMappingURL=game-chat.js.map
