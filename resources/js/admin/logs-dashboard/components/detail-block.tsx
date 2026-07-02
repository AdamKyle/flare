import React from "react";
import DetailBlockProps from "../types/detail-block-props";

const preValueClasses =
    "mt-1 max-h-72 overflow-y-auto overflow-x-hidden whitespace-pre-wrap " +
    "break-all rounded bg-gray-100 p-2 text-xs dark:bg-gray-800";

const defaultValueClasses =
    "mt-1 break-words text-sm text-gray-900 dark:text-gray-100";

export default class DetailBlock extends React.Component<DetailBlockProps> {
    render() {
        if (
            this.props.value === null ||
            this.props.value === undefined ||
            this.props.value === ""
        ) {
            return null;
        }

        return (
            <div>
                <dt className="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                    {this.props.label}
                </dt>
                <dd
                    className={
                        this.props.pre ? preValueClasses : defaultValueClasses
                    }
                >
                    {this.props.value}
                </dd>
            </div>
        );
    }
}
