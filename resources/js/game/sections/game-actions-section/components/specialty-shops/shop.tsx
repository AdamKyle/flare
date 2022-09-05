import React from "react";

export default class Shop extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className='mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]'>
                <div className='cols-start-1 col-span-2'>
                    <div>Specialty Shop for type: {this.props.type}</div>
                </div>
            </div>
        )
    }
}
