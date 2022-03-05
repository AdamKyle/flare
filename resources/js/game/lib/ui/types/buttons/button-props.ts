export default interface ButtonProps {

    additional_css?: string

    button_label: string,

    /**
     * This should be the function to be called when the button is clicked.
     */
    on_click: any,
}
