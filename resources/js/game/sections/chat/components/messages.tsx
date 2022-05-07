import React from "react";
import BasicCard from "../../../components/ui/cards/basic-card";

export default class Messages extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <BasicCard additionalClasses={'mb-10'}>
                <div className='bg-gray-800 md:p-4 max-h-[800px] min-h-[200px] overflow-x-auto'>
                    <ul className='ml-5'>
                        {this.props.children}
                    </ul>
                </div>
            </BasicCard>
        )
    }
}
