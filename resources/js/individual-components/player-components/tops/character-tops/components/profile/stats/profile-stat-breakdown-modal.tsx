import React from "react";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import ProfileStatBreakdownModalProps from "../../../types/profile/stats/profile-stat-breakdown-modal-props";

export default class ProfileStatBreakdownModal extends React.Component<ProfileStatBreakdownModalProps> {
    componentDidMount(): void {
        document.addEventListener("keydown", this.handleKeyDown);
    }

    componentWillUnmount(): void {
        document.removeEventListener("keydown", this.handleKeyDown);
    }

    handleKeyDown = (event: KeyboardEvent): void => {
        if (event.key === "Escape") {
            this.props.onClose();
        }
    };

    render() {
        const stat = this.props.stat;

        return (
            <div
                className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="profile-stat-breakdown-title"
            >
                <div className="w-full max-w-xl rounded-sm bg-white p-6 shadow-lg dark:bg-gray-800 dark:text-gray-100">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold uppercase text-gray-600 dark:text-gray-400">
                                Stat Breakdown
                            </p>
                            <h2
                                id="profile-stat-breakdown-title"
                                className="text-2xl font-semibold"
                            >
                                {stat.label ?? "Unknown Stat"}
                            </h2>
                        </div>
                        <button
                            type="button"
                            className="rounded-sm border border-gray-300 px-3 py-1 text-sm font-semibold dark:border-gray-600"
                            onClick={this.props.onClose}
                        >
                            Close
                        </button>
                    </div>
                    <p className="mt-4 text-3xl font-bold tabular-nums">
                        {formatTopsValue(stat.value)}
                    </p>
                    <p className="mt-3 text-sm text-gray-700 dark:text-gray-300">
                        {stat.description ??
                            "No public breakdown is available for this stat."}
                    </p>
                </div>
            </div>
        );
    }
}
