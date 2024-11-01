import React from "react";
import AttackButtonsContainerProps from "./types/attack-buttons-container-props";

export default class AttackButtonsContainer extends React.Component<AttackButtonsContainerProps> {
    render() {
        return (
            <div className="mx-auto mt-4 flex flex-col sm:flex-row items-center justify-center w-full lg:w-1/3 gap-y-4 gap-x-3 text-lg leading-none">
                {this.props.children}
            </div>
        );
    }
}
