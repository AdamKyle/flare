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
                            pattern: "**/ui/**",
                            group: "internal",
                            position: "before",
                        },
                        {
                            pattern: "**/components/**",
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
