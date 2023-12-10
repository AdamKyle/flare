import React from 'react';
import BasicCardProperties from "./types/basic-card-properties";

export default class BasicCard extends React.Component<BasicCardProperties, any> {
    constructor(props: BasicCardProperties) {
        super(props);
    }

    appendAdditionalClasses(): string {
        if (this.props.additionalClasses) {
            return this.props.additionalClasses;
        }

        return '';
    }

    render() {
        return (
            <div className={'bg-white rounded-sm drop-shadow-md p-6 dark:bg-gray-800 dark:text-gray-400 ' + this.appendAdditionalClasses()}>
                {this.props.children}
            </div>
        );
    }
}
