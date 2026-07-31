import { StylesConfig } from "react-select";

const topsSelectStyles: StylesConfig<any, boolean> = {
    control: (base) => ({
        ...base,
        backgroundColor: "#ffffff",
        color: "#111827",
    }),
    singleValue: (base) => ({
        ...base,
        color: "#111827",
    }),
    menu: (base) => ({
        ...base,
        backgroundColor: "#ffffff",
        color: "#111827",
    }),
    menuPortal: (base) => ({
        ...base,
        zIndex: 9999,
    }),
    option: (base, state) => ({
        ...base,
        backgroundColor: state.isDisabled
            ? "#ffffff"
            : state.isSelected
              ? "#2684ff"
              : state.isFocused
                ? "#deebff"
                : "#ffffff",
        color: state.isDisabled
            ? "#9ca3af"
            : state.isSelected
              ? "#ffffff"
              : "#111827",
    }),
};

export default topsSelectStyles;
