import colors from "tailwindcss/colors";
import aspectRatio from "@tailwindcss/aspect-ratio";
import tailWindPros from '@tailwindcss/typography';
import tailwindcssDir from "tailwindcss-dir";

export default {
    mode: 'jit',
    content: [
        './resources/**/*.{js,vue,blade.php,jsx,tsx}',
    ],
    darkMode: "class",
    theme: {
        container: {
            center: true,
            padding: "1rem",
        },
        fontFamily: {
            body: ["Nunito Sans", "sans-serif"],
            heading: ["Nunito", "sans-serif"],
        },
        colors: {
            transparent: "transparent",
            black: colors.black,
            white: colors.white,
            'regent-st-blue': {
                '50': '#f2f7fc',
                '100': '#e1edf8',
                '200': '#cbe0f2',
                '300': '#a1c9e8',
                '400': '#7db1dd',
                '500': '#5e95d3',
                '600': '#4a7cc6',
                '700': '#4069b5',
                '800': '#395694',
                '900': '#324a76',
                '950': '#222f49',
            },
            'artifact-colors': {
                '200': '#e9b8e2',
                '800': '#82326b',
            },
            'cosmic-colors': {
                '600': '#7A6AC1',
                '700': '#6959A9',
            },
            'item-skill-training': {
                '300': '#f7c852',
                '600': '#d36e0c'
            },
            primary: {
                DEFAULT: "#145388",
                100: "#F3F6F9",
                200: "#C4D4E1",
                300: "#A1BACF",
                400: "#5B87AC",
                500: "#145388",
                600: "#124B7A",
                700: "#0C3252",
                800: "#09253D",
                900: "#061929",
            },
            indigo: {
                50: "#eef2ff",
                100: "#e0e7ff",
                200: "#c7d2fe",
                300: "#a5b4fc",
                400: "#818cf8",
                500: "#6366f1",
                600: "#4f46e5",
                700: "#4338ca",
                800: "#3730a3",
                900: "#312e81",
            },
            secondary: "#555555",
            gray: {
                100: "#f8f8f8",
                200: "#eeeeee",
                300: "#dddddd",
                400: "#cccccc",
                500: "#aaaaaa",
                600: "#888888",
                700: "#555555",
                800: "#242526",
                900: "#151515",
            },
        },
        extend: {
            fontSize: {
                "4xl": "2rem",
                "line-height": "3rem",
            },
            borderRadius: {
                xl: "10px",
            },
            boxShadow: {
                DEFAULT: "1px 1px 5px 0 rgba(0, 0, 0, 0.16)",
            },
            screens: {
                'sm': '640px',

                'md': '1024px',

                'md-lg': '1280px',

                '1376': '1376px',

                'lg': '1600px',

                'xl': '1920px',

                '2xl': '2600px',
            },
            colors: {
                orange: {
                    ...colors.orange,
                    DEFAULT: "#fff7ed"
                },
                green: {
                    ...colors.green,
                    DEFAULT: "#28a745",
                },
                fuchsia: {
                    ...colors.fuchsia,
                    DEFAULT: "#E879BA",
                },
                neutral: {
                    ...colors.neutral,
                    DEFAULT: "#fafafa",
                },
                slate: {
                    ...colors.slate,
                    DEFAULT: "#f8fafc",
                },
                red: {
                    ...colors.red,
                    DEFAULT: "#dc3545",
                },
                yellow: {
                    ...colors.yellow,
                    DEFAULT: "#ffc107",
                },
                blue: {
                    ...colors.blue,
                    DEFAULT: "#17a2b8",
                },
                sky: {
                    ...colors.sky,
                    DEFAULT: '#f0f9ff'
                },
                pink: {
                    ...colors.pink,
                    DEFAULT: '#fdf2f8'
                },
                amber: {
                    ...colors.amber,
                    DEFAULT: '#fffbeb'
                },
                lime: {
                    ...colors.lime,
                    DEFAULT: '#FEF7E7'
                }
            },
            spacing: {
                "1/1": "100%",
                "3/4": "75%",
                "9/16": "56.25%",
            },
            animation: {
                "spin-slow": "spin 3s linear infinite",
            },
            typography: (theme) => ({
                default: {
                    css: {
                        color: theme('colors.gray.900'),
                        a: {
                            color: theme('colors.blue.700'),
                            '&:hover': {
                                color: theme('colors.blue.700'),
                            },
                        },
                    },
                },

                dark: {
                    css: {
                        a: {
                            color: theme('colors.blue.300'),
                            '&:hover': {
                                color: theme('colors.blue.400'),
                            },
                        },

                        h1: {
                            color: theme('colors.gray.300'),
                        },
                        h2: {
                            color: theme('colors.gray.300'),
                        },
                        h3: {
                            color: theme('colors.blue.300'),
                        },
                        h4: {
                            color: theme('colors.gray.300'),
                        },
                        h5: {
                            color: theme('colors.gray.300'),
                        },
                        h6: {
                            color: theme('colors.gray.300'),
                        },

                        strong: {
                            color: theme('colors.gray.300'),
                        },

                        code: {
                            color: theme('colors.gray.300'),
                        },

                        figcaption: {
                            color: theme('colors.gray.500'),
                        },
                    },
                },
            }),
        },
    },
    plugins: [tailWindPros, aspectRatio, tailwindcssDir],
    variants: {
        backgroundColor: ({ after }) => after(['disabled']),
        extend: {
            inset: ["direction"],
            float: ["direction"],
            borderWidth: ["direction"],
            margin: ["direction"],
            padding: ["direction"],
            textAlign: ["direction"],
        },
    },
}
