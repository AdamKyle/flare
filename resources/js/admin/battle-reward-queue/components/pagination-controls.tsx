import React from "react";
import PaginationControlsProps from "../types/pagination-controls-props";

export default class PaginationControls extends React.Component<PaginationControlsProps> {
    render() {
        return (
            <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
                <span className="text-sm text-gray-600 dark:text-gray-300">
                    Page {this.props.currentPage} of {this.props.lastPage}
                </span>
                <div className="flex gap-2">
                    <button
                        className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
                        disabled={this.props.currentPage <= 1}
                        onClick={() =>
                            this.props.onPageChange(this.props.currentPage - 1)
                        }
                    >
                        Previous {this.props.label}
                    </button>
                    <button
                        className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
                        disabled={this.props.currentPage >= this.props.lastPage}
                        onClick={() =>
                            this.props.onPageChange(this.props.currentPage + 1)
                        }
                    >
                        Next {this.props.label}
                    </button>
                </div>
            </div>
        );
    }
}
