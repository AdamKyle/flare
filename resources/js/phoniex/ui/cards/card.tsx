import React from "react";
import CardProps from "./types/card-props";

export default class Card extends React.Component<CardProps> {
    constructor(props: CardProps) {
        super(props);
    }

    render() {
        return (
            <div className="bg-white rounded-sm drop-shadow-md dark:bg-gray-800 dark:text-gray-400">
                <div className="p-6">{this.props.children}</div>
            </div>
        );
    }
}
