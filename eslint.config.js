import eslint from '@eslint/js';
import tseslint from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';
import importPlugin from 'eslint-plugin-import';

export default [
    {
        files: [
            "resources/js/**/*.{ts,tsx}",
            "resources/js/dts/**/*.d.ts"
        ],
        ignores: [
            "node_modules/",
            "dist/",
            "**/*.d.ts",
        ],
        languageOptions: {
            parser: tsParser,
        },
        plugins: {
            import: importPlugin,
            '@typescript-eslint': tseslint,
        },
        settings: {
            'import/resolver': {
                alias: {
                    map: [
                        ['components', './resources/js/components'],
                        ['ui', './resources/js/ui'],
                        ['service-container', './resources/js/service-container'],
                    ],
                    extensions: ['.ts', '.tsx', '.js', '.jsx'],
                },
            },
        },
        rules: {
            ...eslint.configs.recommended.rules,
            ...tseslint.configs.recommended.rules,
            "import/order": [
                "warn",
                {
                    groups: [
                        ["builtin", "external"],
                        ["internal", "parent", "sibling", "index"],
                    ],
                    pathGroups: [
                        {
                            pattern: "react",
                            group: "builtin",
                            position: "before",
                        },
                        {
                            pattern: "framer-motion",
                            group: "builtin",
                            position: "after",
                        },
                        {
                            pattern: "tsyringe",
                            group: "builtin",
                            position: "after",
                        },
                        {
                            pattern: "ts-pattern",
                            group: "builtin",
                            position: "after",
                        },
                        {
                            pattern: "configuration/**",
                            group: "internal",
                            position: "before",
                        },
                        {
                            pattern: "event-system/**",
                            group: "internal",
                            position: "after",
                        },
                        {
                            pattern: "ui/**",
                            group: "internal",
                            position: "after",
                        },
                        {
                            pattern: "service-container/**",
                            group: "internal",
                            position: "after",
                        },
                        {
                            pattern: "service-container-provider/**",
                            group: "internal",
                            position: "after",
                        },
                    ],
                    "newlines-between": "always",
                    alphabetize: {
                        order: "asc",
                        caseInsensitive: true,
                    },
                },
            ],
            "no-undef": [
                "off",
                {
                    "globals": [
                        "window",
                        "document",
                        "HTMLElement",
                    ]
                }
            ],
        },
    },
];
