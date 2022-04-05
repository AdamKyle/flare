import React, {Fragment} from "react";
import ManualProgressBarProps from "../../../lib/ui/types/progress-bars/manual-progress-bar-props";

export default class LoadingProgressBar extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Fragment>
                <div className="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 relative mt-5 mb-5">
                    <div className='h-2.5 rounded-full bg-blue-600 dark:bg-blue-500 loading-progress-bar'></div>
                </div>
            </Fragment>
        );
    }
}
