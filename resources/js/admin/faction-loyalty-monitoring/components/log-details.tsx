import React from "react";
import LogDetailsProps from "../types/log-details-props";
import LogDetailsState from "../types/log-details-state";

const toggleButtonClasses = "text-xs text-blue-500 underline";

const logBlockClasses =
    "mt-1 max-h-40 overflow-y-auto rounded border border-gray-200 " +
    "bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-gray-800";

export default class LogDetails extends React.Component<
    LogDetailsProps,
    LogDetailsState
> {
    public constructor(props: LogDetailsProps) {
        super(props);

        this.state = {
            open: false,
        };
    }

    fightLogs() {
        return this.props.log?.fight_logs ?? [];
    }

    craftingLogs() {
        return this.props.log?.crafting_logs ?? [];
    }

    toggleOpen() {
        this.setState({
            open: !this.state.open,
        });
    }

    render() {
        const fightLogs = this.fightLogs();
        const craftingLogs = this.craftingLogs();
        const hasLogs = fightLogs.length > 0 || craftingLogs.length > 0;

        if (!hasLogs) {
            return <span className="text-xs text-gray-400">No logs</span>;
        }

        return (
            <span>
                <button
                    className={toggleButtonClasses}
                    onClick={() => this.toggleOpen()}
                    aria-expanded={this.state.open}
                >
                    {this.state.open
                        ? "Hide logs"
                        : `Show logs (${fightLogs.length} fight, ${craftingLogs.length} craft)`}
                </button>
                {this.state.open && (
                    <div className="mt-2 space-y-2">
                        {fightLogs.length > 0 && (
                            <details open>
                                <summary className="cursor-pointer text-xs font-medium">
                                    Fight logs ({fightLogs.length})
                                </summary>
                                <pre className={logBlockClasses}>
                                    {JSON.stringify(fightLogs, null, 2)}
                                </pre>
                            </details>
                        )}
                        {craftingLogs.length > 0 && (
                            <details open>
                                <summary className="cursor-pointer text-xs font-medium">
                                    Crafting logs ({craftingLogs.length})
                                </summary>
                                <pre className={logBlockClasses}>
                                    {JSON.stringify(craftingLogs, null, 2)}
                                </pre>
                            </details>
                        )}
                    </div>
                )}
            </span>
        );
    }
}
