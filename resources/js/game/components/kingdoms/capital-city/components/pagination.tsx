import React from "react";
import PaginationButton from "./pagination-button";
import PaginationProps from "../types/components/pagination-props";
import PaginationState from "../types/components/pagination-state";

export default class Pagination extends React.Component<
    PaginationProps,
    PaginationState
> {
    renderPagination() {
        const total_pages = Math.ceil(
            this.props.total_items / this.props.items_per_page,
        );

        const pages = [];
        for (let i = 1; i <= total_pages; i++) {
            pages.push(
                <PaginationButton
                    key={i}
                    current_page={this.props.current_page}
                    on_page_change={this.props.on_page_change}
                    page_number={i}
                />,
            );
        }

        return <div className="flex justify-center mt-4">{pages}</div>;
    }

    render() {
        return this.renderPagination();
    }
}
