import React, { ReactNode } from "react";
import AttackMessagesProps from "./types/attack-messages-props";
import AttackMessageDeffintion from "./deffinitions/attack-message-deffinition";
import { AttackMessageType } from "./enums/attack-message-type";
import { match } from "ts-pattern";

export default class AttackMessages extends React.Component<AttackMessagesProps> {
    renderMessages(): ReactNode[] | [] {
        return this.props.messages.map((message: AttackMessageDeffintion) => {
            const messageColor = this.fetchColorForType(message.type);

            return <div className={messageColor}>{message.message}</div>;
        });
    }

    fetchColorForType(type: AttackMessageType): string {
        return match(type)
            .with(
                AttackMessageType.ENEMY_ATTACK,
                () => "text-rose-700 dark:text-rose-500",
            )
            .with(
                AttackMessageType.PLAYER_ATTACK,
                () => "text-emerald-700 dark:text-emerald-500",
            )
            .with(
                AttackMessageType.REGULAR,
                () => "text-danube-700 dark:text-danube-500",
            )
            .otherwise(() => "");
    }

    render() {
        return (
            <div
                className="
                    mx-auto mt-4 flex items-center justify-center
                    w-full lg:w-2/3 gap-x-3 text-lg leading-none
                "
            >
                <div className="w-full lg:w-1/2 text-center italic space-y-2">
                    {this.renderMessages()}
                </div>
            </div>
        );
    }
}
