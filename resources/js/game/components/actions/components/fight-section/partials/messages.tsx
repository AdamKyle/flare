import React, { ReactNode } from "react";

import AttackMessageDeffintion from "../deffinitions/attack-message-deffinition";
import { fetchAttackMessageColorForType } from "../helpers/fetch-attack-message-color-for-type";
import AttackMessagesProps from "../types/attack-messages-props";

const Messages = (props: AttackMessagesProps): ReactNode => {
    return props.messages.map((message: AttackMessageDeffintion) => {
        const messageColor = fetchAttackMessageColorForType(message.type);

        return <div className={messageColor}>{message.message}</div>;
    });
};

export default Messages;
