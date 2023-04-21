import React from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import MessagesProps from "../types/components/messages-props";

export default class Messages extends React.Component<MessagesProps, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <BasicCard additionalClasses={'mb-10'}>
                <div className='bg-gray-800 p-4 max-h-[800px] min-h-[200px] overflow-x-auto'>
                    <ul className='ml-5'>
                        {this.props.children}
                    </ul>
                </div>
            </BasicCard>
        )
    }
}
