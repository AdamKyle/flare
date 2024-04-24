import React from "react";
import { ResizableBox as ReactResizableBox } from "react-resizable";
import ResizableBoxProps from "../../lib/ui/types/resizable-box-props";

export default class ResizableBox extends React.Component<
    ResizableBoxProps,
    any
> {
    constructor(props: ResizableBoxProps) {
        super(props);

        this.state = {
            width: this.props.width,
            height: this.props.height,
        };
    }

    onResize = (event: any, { element, size, handle }: any) => {
        this.setState({ width: size.width, height: size.height });
    };

    render() {
        return (
            <div>
                <ReactResizableBox
                    width={this.state.width}
                    height={this.state.height}
                    onResize={this.onResize}
                >
                    <div
                        style={{
                            ...this.props.style,
                            width: this.state.width + "px",
                            height: this.state.height + "px",
                        }}
                        className={this.props.additional_css}
                    >
                        {this.props.children}
                    </div>
                </ReactResizableBox>
            </div>
        );
    }
}
