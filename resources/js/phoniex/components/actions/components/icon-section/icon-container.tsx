import React from "react";
import IconContainerProps from "./types/icon-container-props";

export default class IconContainer extends React.Component<IconContainerProps> {
    render() {
        return (
            <div className="flex lg:flex-col items-center mx-auto w-2/3 lg:w-10 justify-between lg:items-start lg:mr-4 lg:justify-start lg:mt-3 mt-4 space-y-0 lg:space-y-2">
                <div className="flex lg:flex-col w-full lg:w-auto lg:space-y-2 space-x-2 lg:space-x-0 ">
                    {this.props.children}
                </div>
            </div>
        );
    }
}
