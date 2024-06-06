import React, { Fragment } from "react";
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";
import Ajax from "../../lib/ajax/ajax";
import ServerMessages from "./server-messages";
import { AxiosError, AxiosResponse } from "axios";
import Chat from "./chat";
import GameChatProps from "./types/game-chat-props";
import GameChatState from "./types/game-chat-state";
import ExplorationMessages from "./exploration-messages";
import { DateTime } from "luxon";
import AnnouncementMessages from "./announcement-messages";
import DropDown from "../../components/ui/drop-down/drop-down";

export default class GameChat extends React.Component<
    GameChatProps,
    GameChatState
> {
    private chat: any;

    private serverMessages: any;

    private privateMessages: any;

    private globalMessage: any;

    private npcMessage: any;

    private explorationMessage: any;

    private announcements: any;

    private deleteAnnouncements: any;

    constructor(props: GameChatProps) {
        super(props);

        this.state = {
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

        // @ts-ignore
        this.chat = Echo.join("chat");

        // @ts-ignore
        this.serverMessages = Echo.private(
            "server-message-" + this.props.user_id,
        );

        // @ts-ignore
        this.explorationMessage = Echo.private(
            "exploration-log-update-" + this.props.user_id,
        );

        // @ts-ignore
        this.privateMessages = Echo.private(
            "private-message-" + this.props.user_id,
        );

        // @ts-ignore
        this.npcMessage = Echo.private("npc-message-" + this.props.user_id);

        // @ts-ignore
        this.globalMessage = Echo.join("global-message");

        // @ts-ignore
        this.announcements = Echo.join("announcement-message");

        // @ts-ignore
        this.deleteAnnouncements = Echo.join("delete-announcement-message");
    }

    componentDidMount() {
        this.setState({
            is_silenced: this.props.is_silenced,
            can_talk_again_at: this.props.can_talk_again_at,
        });

        new Ajax().setRoute("last-chats").doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                this.setState({
                    announcements: result.data.announcements,
                });

                const chats = result.data.chat_messages
                    .map((chat: any) => {
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
                    .filter((chat: any) => typeof chat !== "undefined");

                this.setState(
                    {
                        chat: [...this.state.chat, ...chats],
                        announcements: result.data.announcements,
                        updated_tabs:
                            this.canUpdateTabs("Announcements") &&
                            result.data.announcements.length > 0
                                ? [...this.state.updated_tabs, "Announcements"]
                                : this.state.updated_tabs,
                    },
                    () => {
                        if (
                            typeof this.props.update_finished_loading !==
                            "undefined"
                        ) {
                            this.props.update_finished_loading();
                        }
                    },
                );
            },
            (error: AxiosError) => {
                console.error(error);
            },
        );

        // @ts-ignore
        this.explorationMessage.listen(
            "Game.Exploration.Events.ExplorationLogUpdate",
            (event: any) => {
                let messages = JSON.parse(
                    JSON.stringify(this.state.exploration_messages),
                );

                if (messages.length > 1000) {
                    messages.length = 250; // Remove the last 3/4's worth of messages
                }

                messages.unshift({
                    id: (Math.random() + 1).toString(36).substring(7),
                    message: event.message,
                    make_italic: event.makeItalic,
                    is_reward: event.isReward,
                });

                this.setState(
                    {
                        exploration_messages: messages,
                        updated_tabs: this.canUpdateTabs("Exploration")
                            ? [...this.state.updated_tabs, "Exploration"]
                            : this.state.updated_tabs,
                    },
                    () => {
                        this.setTabToUpdated("exploration-messages");
                    },
                );
            },
        );

        // @ts-ignore
        this.serverMessages.listen(
            "Game.Messages.Events.ServerMessageEvent",
            (event: any) => {
                if (event.message === "") {
                    return;
                }

                let messages = JSON.parse(
                    JSON.stringify(this.state.server_messages),
                );

                if (messages.length > 1000) {
                    messages.length = 250; // Remove the last 3/4's worth of messages
                }

                messages.unshift({
                    id:
                        event.id +
                        "-" +
                        (Math.random() + 1).toString(36).substring(7),
                    message: event.message,
                    event_id: event.id,
                });

                this.setState(
                    {
                        server_messages: messages,
                        updated_tabs: this.canUpdateTabs("Server Messages")
                            ? [...this.state.updated_tabs, "Server Messages"]
                            : this.state.updated_tabs,
                    },
                    () => {
                        this.setTabToUpdated("server-messages");
                    },
                );
            },
        );

        this.npcMessage.listen(
            "Game.Messages.Events.NPCMessageEvent",
            (event: any) => {
                const chat = JSON.parse(JSON.stringify(this.state.chat));

                chat.unshift({
                    message: event.message,
                    character_name: event.npcName,
                    type: "npc-message",
                });

                this.setState({
                    chat: chat,
                    updated_tabs: this.canUpdateTabs("Chat")
                        ? [...this.state.updated_tabs, "Chat"]
                        : this.state.updated_tabs,
                });
            },
        );

        this.chat.listen(
            "Game.Messages.Events.MessageSentEvent",
            (event: any) => {
                const chat = JSON.parse(JSON.stringify(this.state.chat));

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

                this.setState(
                    {
                        chat: chat,
                        updated_tabs: this.canUpdateTabs("Chat")
                            ? [...this.state.updated_tabs, "Chat"]
                            : this.state.updated_tabs,
                    },
                    () => {
                        this.setTabToUpdated("chat");
                    },
                );
            },
        );

        this.privateMessages.listen(
            "Game.Messages.Events.PrivateMessageEvent",
            (event: any) => {
                const chat = JSON.parse(JSON.stringify(this.state.chat));

                if (chat.length > 1000) {
                    chat.length = 500;
                }

                chat.unshift({
                    message: event.message,
                    type: "private-message-received",
                    from: event.from,
                });

                this.setState(
                    {
                        chat: chat,
                        updated_tabs: this.canUpdateTabs("Chat")
                            ? [...this.state.updated_tabs, "Chat"]
                            : this.state.updated_tabs,
                    },
                    () => {
                        this.setTabToUpdated("chat");
                    },
                );
            },
        );

        this.globalMessage.listen(
            "Game.Messages.Events.GlobalMessageEvent",
            (event: any) => {
                const chat = JSON.parse(JSON.stringify(this.state.chat));

                if (chat.length > 1000) {
                    chat.length = 500;
                }

                chat.unshift({
                    message: event.message,
                    type: event.specialColor,
                });

                this.setState(
                    {
                        chat: chat,
                        updated_tabs: this.canUpdateTabs("Chat")
                            ? [...this.state.updated_tabs, "Chat"]
                            : this.state.updated_tabs,
                    },
                    () => {
                        this.setTabToUpdated("chat");
                    },
                );
            },
        );

        this.announcements.listen(
            "Game.Messages.Events.AnnouncementMessageEvent",
            (event: any) => {
                const announcements = JSON.parse(
                    JSON.stringify(this.state.announcements),
                );

                announcements.unshift({
                    message: event.message,
                    id: event.id,
                });

                this.setState(
                    {
                        announcements: announcements,
                        updated_tabs: this.canUpdateTabs("Announcements")
                            ? [...this.state.updated_tabs, "Announcements"]
                            : this.state.updated_tabs,
                    },
                    () => {
                        this.setTabToUpdated("announcements-messages");
                    },
                );
            },
        );

        this.deleteAnnouncements.listen(
            "Game.Messages.Events.DeleteAnnouncementEvent",
            (event: any) => {
                const announcements = JSON.parse(
                    JSON.stringify(this.state.announcements),
                );

                const updatedAnnouncements = announcements.filter(
                    (announcement: any) => announcement.id !== event.id,
                );

                this.setState({
                    announcements: updatedAnnouncements,
                });
            },
        );
    }

    canUpdateTabs(name: string) {
        return (
            this.state.selected_chat !== name &&
            !(this.state.updated_tabs as string[]).includes(name)
        );
    }

    componentDidUpdate(
        prevProps: Readonly<any>,
        prevState: Readonly<GameChatState>,
        snapshot?: any,
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
    }

    pushSilencedMethod() {
        if (this.state.is_silenced) {
            const chat = JSON.parse(JSON.stringify(this.state.chat));

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
    }

    pushPrivateMessageSent(messageData: string[]) {
        const chat = JSON.parse(JSON.stringify(this.state.chat));

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
    }

    pushErrorMessage(message: string) {
        const chat = JSON.parse(JSON.stringify(this.state.chat));

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
    }

    resetTabChange(key: string) {
        const tabs = this.state.tabs.map((tab: any) => {
            return tab.key === key ? { ...tab, updated: false } : tab;
        });

        this.setState({
            tabs: tabs,
        });
    }

    setTabToUpdated(key: string) {
        const tabs = this.state.tabs.map((tab: any) => {
            return tab.key === key ? { ...tab, updated: true } : tab;
        });

        this.setState({
            tabs: tabs,
        });
    }

    switchChat(type: string) {
        this.setState({
            selected_chat: type,
            updated_tabs: this.state.updated_tabs.filter(
                (name: string) => name !== type,
            ),
        });
    }

    renderDropDownOptions(): {
        name: string;
        icon_class: string;
        on_click: () => void;
    }[] {
        return [
            {
                name: "Chat",
                icon_class: "fas fa-comments",
                on_click: () => this.switchChat("Chat"),
            },
            {
                name: "Server Messages",
                icon_class: "fas fa-server",
                on_click: () => this.switchChat("Server Messages"),
            },
            {
                name: "Exploration",
                icon_class: "fas fa-dragon",
                on_click: () => this.switchChat("Exploration"),
            },
            {
                name: "Announcements",
                icon_class: "fas fa-scroll",
                on_click: () => this.switchChat("Announcements"),
            },
        ];
    }

    renderSelectedTab(): JSX.Element {
        switch (this.state.selected_chat) {
            case "Announcements":
                return (
                    <AnnouncementMessages
                        announcements={this.state.announcements}
                    />
                );
            case "Exploration":
                return (
                    <ExplorationMessages
                        exploration_messages={this.state.exploration_messages}
                    />
                );
            case "Server Messages":
                return (
                    <ServerMessages
                        server_messages={this.state.server_messages}
                        character_id={this.props.character_id}
                        view_port={this.props.view_port}
                        is_automation_running={this.props.is_automation_running}
                    />
                );
            case "Chat":
            default:
                return (
                    <Chat
                        is_silenced={this.props.is_silenced}
                        can_talk_again_at={this.props.can_talk_again_at}
                        chat={this.state.chat}
                        set_tab_to_updated={this.setTabToUpdated.bind(this)}
                        push_silenced_message={this.pushSilencedMethod.bind(
                            this,
                        )}
                        push_private_message_sent={this.pushPrivateMessageSent.bind(
                            this,
                        )}
                        push_error_message={this.pushErrorMessage.bind(this)}
                    />
                );
        }
    }

    render() {
        if (this.props.is_admin) {
            return (
                <Chat
                    is_silenced={this.props.is_silenced}
                    chat={this.state.chat}
                    can_talk_again_at={this.props.can_talk_again_at}
                    set_tab_to_updated={this.setTabToUpdated.bind(this)}
                    push_silenced_message={this.pushSilencedMethod.bind(this)}
                    push_private_message_sent={this.pushPrivateMessageSent.bind(
                        this,
                    )}
                    push_error_message={this.pushErrorMessage.bind(this)}
                />
            );
        }

        return (
            <Fragment>
                <div className="mt-4 mb-4 md:hidden">
                    <DropDown
                        menu_items={this.renderDropDownOptions()}
                        button_title={"Chat Tabs"}
                        selected_name={this.state.selected_chat}
                        show_alert={true}
                        alert_names={this.state.updated_tabs}
                    />
                    <div className="my-4">{this.renderSelectedTab()}</div>
                </div>
                <div className="mt-4 mb-4 hidden md:block">
                    <Tabs
                        tabs={this.state.tabs}
                        icon_key={"updated"}
                        when_tab_changes={this.resetTabChange.bind(this)}
                    >
                        <TabPanel key={"chat"}>
                            <Chat
                                is_silenced={this.props.is_silenced}
                                can_talk_again_at={this.props.can_talk_again_at}
                                chat={this.state.chat}
                                set_tab_to_updated={this.setTabToUpdated.bind(
                                    this,
                                )}
                                push_silenced_message={this.pushSilencedMethod.bind(
                                    this,
                                )}
                                push_private_message_sent={this.pushPrivateMessageSent.bind(
                                    this,
                                )}
                                push_error_message={this.pushErrorMessage.bind(
                                    this,
                                )}
                            />
                        </TabPanel>

                        <TabPanel key={"server-messages"}>
                            <ServerMessages
                                server_messages={this.state.server_messages}
                                character_id={this.props.character_id}
                                view_port={this.props.view_port}
                                is_automation_running={
                                    this.props.is_automation_running
                                }
                            />
                        </TabPanel>

                        <TabPanel key={"exploration-messages"}>
                            <ExplorationMessages
                                exploration_messages={
                                    this.state.exploration_messages
                                }
                            />
                        </TabPanel>

                        <TabPanel key={"announcements-messages"}>
                            <AnnouncementMessages
                                announcements={this.state.announcements}
                            />
                        </TabPanel>
                    </Tabs>
                </div>
            </Fragment>
        );
    }
}
