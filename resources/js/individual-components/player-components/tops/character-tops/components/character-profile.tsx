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
                deferred_loading={this.props.deferred_loading}
                deferred_errors={this.props.deferred_errors}
            />
        );
    }
}
