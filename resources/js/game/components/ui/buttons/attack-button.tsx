import React from "react";

export default class AttackButton extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

    }

    render() {
        return (
            <button type='button' className='rounded-full bg-red-900 w-10 h-10 mx-2'>
                <i className="ra ra-sword"></i>
            </button>
        )
    }
}
