import colors from "tailwindcss/colors";
import aspectRatio from "@tailwindcss/aspect-ratio";
import tailWindPros from '@tailwindcss/typography';
import tailwindcssDir from "tailwindcss-dir";
import tailwindScrollBar from 'tailwind-scrollbar'

export default {
    mode: 'jit',
    content: [
        './resources/**/*.{js,vue,blade.php,jsx,tsx,ts}',
        './app/Flare/View/Livewire/**/*.php',
        './vendor/rappasoft/laravel-livewire-tables/resources/views/**/*.blade.php'
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
                DEFAULT: "#fcf6fd",
                '100': '#f9ebfc',
                '200': '#f3d7f7',
                '300': '#ecb7f0',
                '400': '#e08ce6',
                '500': '#cf5fd6',
                '600': '#b53fba',
                '700': '#a034a2',
                '800': '#7d2a7e',
                '900': '#682767',
                '950': '#430f42',
            },
            'cosmic-colors': {
                DEFAULT: '#fcf3ff',
                '100': '#f9e7ff',
                '200': '#f3cdff',
                '300': '#eba7ff',
                '400': '#e172ff',
                '500': '#d03df8',
                '600': '#b61cdd',
                '700': '#9b14b7',
                '800': '#84139a',
                '900': '#6b157a',
                '950': '#470052',
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
                DEFAULT: "#eef2ff",
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
            rose: {
                DEFAULT: '#fff1f2',
                '100': '#ffe4e6',
                '200': '#fecdd3',
                '300': '#fda4af',
                '400': '#fb7185',
                '500': '#f43f5e',
                '600': '#e11d48',
                '700': '#be123c',
                '800': '#9f1239',
                '900': '#881337',
                '950': '#4c0519',
            },
            emerald: {
                DEFAULT: '#ecfdf5',
                '100': '#d1fae5',
                '200': '#a7f3d0',
                '300': '#6ee7b7',
                '400': '#34d399',
                '500': '#10b981',
                '600': '#059669',
                '700': '#047857',
                '800': '#065f46',
                '900': '#064e3b',
                '950': '#022c22',
            },
            danube: {
                DEFAULT: '#f1f7fd',
                '100': '#e0eef9',
                '200': '#c9e1f4',
                '300': '#a4ceec',
                '400': '#78b2e2',
                '500': '#5997d9',
                '600': '#447dcc',
                '700': '#3a6abb',
                '800': '#355698',
                '900': '#2f4a79',
                '950': '#212e4a',
            },
            'mango-tango': {
                DEFAULT: '#fffaec',
                '100': '#fff4d3',
                '200': '#ffe5a5',
                '300': '#ffd16d',
                '400': '#ffb132',
                '500': '#ff980a',
                '600': '#ec7600',
                '700': '#cc5d02',
                '800': '#a1480b',
                '900': '#823d0c',
                '950': '#461c04',
            },
            'marigold': {
                DEFAULT: '#f9f7ed',
                '100': '#f0ead1',
                '200': '#e2d6a6',
                '300': '#d1ba73',
                '400': '#c2a14d',
                '500': '#b68f40',
                '600': '#9a7034',
                '700': '#7c542c',
                '800': '#68462b',
                '900': '#5a3c29',
                '950': '#331f15',
            },
            'wisp-pink': {
                DEFAULT: '#fdf2f8',
                '100': '#fce7f2',
                '200': '#fbcfe7',
                '300': '#f9a8d4',
                '400': '#f472b9',
                '500': '#ec48a1',
                '600': '#db2789',
                '700': '#be1873',
                '800': '#9d1760',
                '900': '#831852',
                '950': '#50072f',
            }

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
                'sm': '568px',

                'md': '768px',

                'lg': '1024px',

                'xl': '1366px'
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
                progress: 'progress 1s infinite linear',
            },
            keyframes: {
                progress: {
                    '0%': {transform: 'translateX(0) scaleX(0)'},
                    '40%': {transform: 'translateX(0) scaleX(0.4)'},
                    '100%': {transform: 'translateX(100%) scaleX(0.5)'},
                },
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
    plugins: [tailWindPros, aspectRatio, tailwindcssDir, tailwindScrollBar],
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
