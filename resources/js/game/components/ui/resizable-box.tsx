import React from "react";
import { ResizableBox as ReactResizableBox } from "react-resizable";
import ResizableBoxProps from "../../lib/ui/types/resizable-box-props";

export default class ResizableBox extends React.Component<ResizableBoxProps, any> {

    constructor(props: ResizableBoxProps) {
        super(props);
    }

    render() {
        return (
            <div>
                <ReactResizableBox width={this.props.width} height={this.props.height}>
                    <div
                        style={{
                            ...this.props.style,
                            width: "100%",
                            height: "100%"
                        }}
                        className={this.props.additional_css}
                    >
                        {this.props.children}
                    </div>
                </ReactResizableBox>
            </div>
        )
    }
}
