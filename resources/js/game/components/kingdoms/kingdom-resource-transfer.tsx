import React from "react";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";
import { serviceContainer } from "../../lib/containers/core-container";
import KingdomResourceTransferAjax from "./ajax/kingdom-resource-transfer-ajax";

export default class KingdomResourceTransfer extends React.Component<any, any> {
    private kingdomResourceTransferRequestAjax: KingdomResourceTransferAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            error_message: null,
            loading: true,
            kingdoms: [],
        };

        this.kingdomResourceTransferRequestAjax = serviceContainer().fetch(
            KingdomResourceTransferAjax,
        );
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div>
                <h3>Kingdom Resource Request</h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                stuff ...
            </div>
        );
    }
}
