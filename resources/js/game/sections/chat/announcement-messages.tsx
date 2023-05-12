import React from "react";
import Messages from "./components/messages";
import {AnnouncementType} from "./types/game-chat-state";
import AnnouncementMessagesProps from "./types/components/announcement-messages-props";

export default class AnnouncementMessages extends React.Component<AnnouncementMessagesProps, any> {

    constructor(props: AnnouncementMessagesProps) {
        super(props);
    }

    buildMessages() {
        return this.props.announcements.map((message: AnnouncementType) => {
            return <li className='my-2 break-all lg:break-normal text-orange-500' key={message.id}>
                {message.message}
            </li>
        });
    }

    render() {
        return (
            <Messages>
                {this.buildMessages()}
            </Messages>
        )
    }
}
