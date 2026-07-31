import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsPageShellProps from "../types/tops-page-shell-props";

export default class TopsPageShell extends React.Component<TopsPageShellProps> {
    render() {
        return (
            <div className="space-y-6">
                <BasicCard additionalClasses="border border-gray-200 shadow-sm dark:border-gray-700">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {this.props.title}
                    </h1>
                    <p className="mt-3 max-w-4xl text-base text-gray-700 dark:text-gray-300">
                        {this.props.description}
                    </p>
                    {this.props.resetDescription ? (
                        <p className="mt-4 max-w-5xl text-sm text-gray-600 dark:text-gray-300">
                            {this.props.resetDescription}
                        </p>
                    ) : null}
                </BasicCard>
                {this.props.children}
            </div>
        );
    }
}
