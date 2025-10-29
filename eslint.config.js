import eslint from '@eslint/js';
import tseslint from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';
import importPlugin from 'eslint-plugin-import';
import stylistic from '@stylistic/eslint-plugin';
import reactHooks from 'eslint-plugin-react-hooks';
import jsxA11y from 'eslint-plugin-jsx-a11y';
import prettierPlugin from 'eslint-plugin-prettier';
import unusedImports from 'eslint-plugin-unused-imports';
import noEarlyReturnHook from './eslint-rules/no-early-return-before-hook.js';

export default [
  {
    files: [
      'resources/js/**/*.{ts,tsx}',
      'resources/js/dts/**/*.d.ts',
    ],
    ignores: [
      'node_modules/',
      'dist/',
      '**/*.d.ts',
    ],
    languageOptions: {
      parser: tsParser,
    },
    plugins: {
      import: importPlugin,
      '@typescript-eslint': tseslint,
      '@stylistic': stylistic,
      'react-hooks': reactHooks,
      'jsx-a11y': jsxA11y,
      prettier: prettierPlugin,
      'unused-imports': unusedImports,
      'local-hooks': {
        rules: {
          'no-early-return-before-hook': noEarlyReturnHook,
        },
      },
    },
    settings: {
      'import/resolver': {
        alias: {
          map: [
            ['event-system', './resources/js/even-system'],
            ['axios', './resources/js/api-handler'],
            ['game-data', './resources/js/game-data'],
            ['components', './resources/js/components'],
            ['ui', './resources/js/ui'],
            ['service-container', './resources/js/service-container'],
            ['websockets', './resources/js/websocket-handler'],
            ['game-utils', './resources/js/game/util'],
            ['screen-manager', './resources/js/screen-manager'],
          ],
          extensions: ['.ts', '.tsx', '.js', '.jsx'],
        },
      },
    },
    rules: {
      ...eslint.configs.recommended.rules,
      ...tseslint.configs.recommended.rules,
      ...prettierPlugin.configs.recommended.rules,

      '@typescript-eslint/no-unused-vars': 'off',
      'unused-imports/no-unused-imports': 'warn',

      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',

      'local-hooks/no-early-return-before-hook': 'error',

      'unused-imports/no-unused-vars': [
        'warn',
        {
          vars: 'all',
          varsIgnorePattern: '^_',
          args: 'after-used',
          argsIgnorePattern: '^_',
        },
      ],

      'import/order': [
        'warn',
        {
          groups: [
            ['builtin', 'external'],
            ['internal', 'parent', 'sibling', 'index'],
          ],
          pathGroups: [
            { pattern: 'react', group: 'builtin', position: 'before' },
            { pattern: 'framer-motion', group: 'builtin', position: 'after' },
            { pattern: 'tsyringe', group: 'builtin', position: 'after' },
            { pattern: 'ts-pattern', group: 'builtin', position: 'after' },
            { pattern: 'configuration/**', group: 'internal', position: 'before' },
            { pattern: 'screen-manager/**', group: 'internal', position: 'before' },
            { pattern: 'websockets/**', group: 'internal', position: 'after' },
            { pattern: 'api-handler/**', group: 'internal', position: 'after' },
            { pattern: 'event-system/**', group: 'internal', position: 'after' },
            { pattern: 'game-data/**', group: 'internal', position: 'after' },
            { pattern: 'game-utils/**', group: 'internal', position: 'after' },
            { pattern: 'ui/**', group: 'internal', position: 'after' },
            { pattern: 'service-container/**', group: 'internal', position: 'after' },
            { pattern: 'service-container-provider/**', group: 'internal', position: 'after' },
          ],
          'newlines-between': 'always',
          alphabetize: { order: 'asc', caseInsensitive: true },
        },
      ],
      'max-len': [
        'warn',
        {
          code: 100,
          tabWidth: 2,
          ignoreUrls: true,
          ignoreStrings: true,
          ignoreTemplateLiterals: true,
          ignoreComments: true,
        },
      ],
      '@stylistic/max-len': [
        'warn',
        {
          code: 100,
          tabWidth: 2,
          ignoreUrls: true,
          ignoreStrings: true,
          ignoreTemplateLiterals: true,
          ignoreComments: true,
        },
      ],
      'no-undef': [
        'off',
        {
          globals: [
            'window',
            'document',
            'HTMLElement',
          ],
        },
      ],
    },
  },
];
