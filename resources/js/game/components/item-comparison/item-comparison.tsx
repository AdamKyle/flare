import React from "react";
import ItemComparisonProps from "./types/item-comparison-props";
import Comparison from "./comparison";
import ItemNameColorationText from "../../sections/items/item-name/item-name-coloration-text";
import PrimaryOutlineButton from "../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../ui/buttons/success-outline-button";
import ComparisonDetails from "./deffinitions/comparison-details";
import ExpandedComparison from "./expanded-comparison";

export default class ItemComparison extends React.Component<ItemComparisonProps, any> {

    constructor(props: ItemComparisonProps) {
        super(props);

        this.state = {
            loading: false,
            success_message: null,
            error_message: null,
            expanded_comparison_details: null,
        }
    }

    componentDidUpdate(prevProps: Readonly<ItemComparisonProps>, prevState: Readonly<any>, snapshot?: any) {

        if (prevProps.is_showing_expanded_comparison && !this.props.is_showing_expanded_comparison) {
            this.setState({
                expanded_comparison_details: null
            })
        }
    }

    showExpandedComparison(comparison: ComparisonDetails) {
        this.setState({
            expanded_comparison_details: comparison
        }, () => {
            this.props.manage_show_expanded_comparison();
        });
    }

    renderExpandedComparison() {
        return <ExpandedComparison comparison_details={this.state.expanded_comparison_details}  />
    }

    renderColumns() {
        return (
            <div className='grid md:grid-cols-2 gap-2'>
                <div>
                    <h3 className={'mt-4'}>
                        <ItemNameColorationText item={this.props.comparison_info.details[0]} custom_width={true} />
                    </h3>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                    <Comparison comparison={this.props.comparison_info.details[0]} />
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                    <div className="flex items-center">
                        <div className='mr-2'>
                            <PrimaryOutlineButton button_label={'See Expanded Details'} on_click={() => this.showExpandedComparison(this.props.comparison_info.details[0])} />
                        </div>
                        <div>
                            <SuccessOutlineButton button_label={'Buy and Replace'} on_click={() => {}} />
                        </div>
                    </div>
                </div>
                <div>
                    <h3 className={'mt-4'}>
                        <ItemNameColorationText item={this.props.comparison_info.details[1]} custom_width={true} />
                    </h3>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                    <Comparison comparison={this.props.comparison_info.details[1]} />
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                    <div className="flex items-center">
                        <div className='mr-2'>
                            <PrimaryOutlineButton button_label={'See Expanded Details'} on_click={() => this.showExpandedComparison(this.props.comparison_info.details[1])} />
                        </div>
                        <div>
                            <SuccessOutlineButton button_label={'Buy and Replace'} on_click={() => {}} />
                        </div>
                    </div>
                </div>
            </div>
        )
    }

    renderSingleComparison() {
        return (
            <div className='mr-auto ml-auto w-3/5'>
                <h3 className={'mt-4'}>
                    <ItemNameColorationText item={this.props.comparison_info.details[0]} custom_width={true} />
                </h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <Comparison comparison={this.props.comparison_info.details[0]} />
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>

                <div className="flex items-center">
                    <div className='mr-2'>
                        <PrimaryOutlineButton button_label={'See Expanded Details'} on_click={() => this.showExpandedComparison(this.props.comparison_info.details[0])} />
                    </div>
                    <div>
                        <SuccessOutlineButton button_label={'Buy and Replace'} on_click={this.props.handle_buy_and_replace} />
                    </div>
                </div>
            </div>
        )
    }

    renderComparison() {
        if (this.props.comparison_info.details.length > 1) {
            return this.renderColumns();
        }

        return this.renderSingleComparison();
    }

    render() {
        return <>{this.state.expanded_comparison_details !== null ? this.renderExpandedComparison() : this.renderComparison()}</>
    }
}
