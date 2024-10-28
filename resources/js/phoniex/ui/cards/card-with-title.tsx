import React, { ReactNode } from "react";
import CardWithTitleProps from "./types/card-with-title-props";
import Seperator from "../seperatror/seperator";
import { TitleSize } from "./enums/title-size";
import { match } from "ts-pattern";

export default class CardWithTitle extends React.Component<CardWithTitleProps> {
    constructor(props: CardWithTitleProps) {
        super(props);
    }

    renderTitle(): ReactNode {
        return match(this.props.title_size)
            .with(TitleSize.H1, () => (
                <h1 className="w-full">{this.props.title}</h1>
            ))
            .with(TitleSize.H2, () => (
                <h2 className="w-full">{this.props.title}</h2>
            ))
            .with(TitleSize.H3, () => (
                <h3 className="w-full">{this.props.title}</h3>
            ))
            .with(TitleSize.H4, () => (
                <h4 className="w-full">{this.props.title}</h4>
            ))
            .with(TitleSize.H5, () => (
                <h5 className="w-full">{this.props.title}</h5>
            ))
            .otherwise(() => <h3 className="w-full">{this.props.title}</h3>);
    }

    render() {
        return (
            <div className="bg-white rounded-sm drop-shadow-md dark:bg-gray-800 dark:text-gray-400">
                <div className="p-6">
                    {this.renderTitle()}
                    <Seperator />
                    {this.props.children}
                </div>
            </div>
        );
    }
}
