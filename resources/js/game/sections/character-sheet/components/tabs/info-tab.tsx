import React, {Fragment} from "react";
import ManualProgressBar from "../../../../components/ui/progress-bars/manual-progress-bar";

export default class InfoTab extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return(
            <Fragment>
            <div className='grid md:grid-cols-2 gap-2'>
                <div>
                    <dl>
                        <dt>Name:</dt>
                        <dd>Sample</dd>
                        <dt>Race:</dt>
                        <dd>Sample</dd>
                        <dt>Class:</dt>
                        <dd>Sample</dd>
                        <dt>Level:</dt>
                        <dd>1000/4000</dd>
                    </dl>
                </div>
                <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <dl>
                        <dt>Max Health:</dt>
                        <dd>1,900,800,000</dd>
                        <dt>Total Attack:</dt>
                        <dd>1,800,000,000</dd>
                        <dt>Heal For:</dt>
                        <dd>1,800,000,000</dd>
                        <dt>AC:</dt>
                        <dd>900,000</dd>
                    </dl>
                </div>
            </div>
            <div className='relative top-[24px]'>
                <div className="flex justify-between mb-1">
                    <span className="font-medium text-orange-700 dark:text-white text-xs">XP</span>
                    <span className="text-xs font-medium text-orange-700 dark:text-white">0/100</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                    <div className="bg-orange-600 h-1.5 rounded-full" style={{width: 50 + '%'}}></div>
                </div>
            </div>
            </Fragment>
        )
    }
}
