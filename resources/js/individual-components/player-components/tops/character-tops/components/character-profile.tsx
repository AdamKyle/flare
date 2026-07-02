import React from "react";
import CharacterProfileProps from "../types/character-profile-props";
import CharacterProfileShell from "./profile/character-profile-shell";

export default class CharacterProfile extends React.Component<CharacterProfileProps> {
    render() {
        return (
            <CharacterProfileShell
                profile={this.props.profile}
                activeTab={this.props.activeTab}
                onTabChange={this.props.onTabChange}
            />
        );
    }
}
