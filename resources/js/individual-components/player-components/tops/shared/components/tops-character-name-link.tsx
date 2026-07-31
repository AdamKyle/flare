import React from "react";
import TopsCharacterNameLinkProps from "../types/tops-character-name-link-props";
import { tableCharacterLinkClasses } from "../helpers/tops-rank-styles";

export default class TopsCharacterNameLink extends React.Component<TopsCharacterNameLinkProps> {
    linkClasses(): string {
        if (this.props.variant === "table" || this.props.tableLink === true) {
            return (
                "font-semibold no-underline underline-offset-4 transition-colors hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-regent-st-blue-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 " +
                tableCharacterLinkClasses(this.props.rank ?? 0)
            );
        }

        return "font-semibold text-gray-900 no-underline underline-offset-4 transition-colors hover:text-gray-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-regent-st-blue-400 focus-visible:ring-offset-2 dark:text-gray-100 dark:hover:text-white dark:focus-visible:ring-offset-gray-900";
    }

    render() {
        if (
            this.props.characterId === null ||
            this.props.characterName === null
        ) {
            return (
                <span>{this.props.characterName ?? "Unknown Character"}</span>
            );
        }

        return (
            <a
                className={this.linkClasses()}
                href={"/game/tops/characters/" + this.props.characterId}
            >
                {this.props.characterName}
            </a>
        );
    }
}
