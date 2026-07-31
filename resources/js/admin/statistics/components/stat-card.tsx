import React from "react";

interface StatCardProps {
    label: string;
    value: string | number;
    secondaryValue?: string | number;
    definition?: string;
}

export default class StatCard extends React.Component<StatCardProps> {
    render() {
        return (
            <div className="h-full min-w-0 rounded-sm bg-white p-4 text-left shadow dark:bg-gray-800">
                <div className="flex h-full min-w-0 flex-col gap-3 text-left">
                    <dt className="m-0 text-left text-sm font-semibold leading-5 text-gray-600 dark:text-gray-300">
                        {this.props.label}
                    </dt>
                    <dd className="m-0 min-w-0 text-left">
                        <div className="max-w-full break-words text-left text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                            {this.props.value}
                        </div>
                        {this.props.secondaryValue && (
                            <div className="mt-1 max-w-full break-words text-left text-sm font-semibold leading-5 text-gray-600 dark:text-gray-300">
                                {this.props.secondaryValue}
                            </div>
                        )}
                    </dd>
                    {this.props.definition && (
                        <p className="m-0 text-left text-sm leading-5 text-gray-600 dark:text-gray-400">
                            {this.props.definition}
                        </p>
                    )}
                </div>
            </div>
        );
    }
}
