export default {
  meta: {
    type: 'problem',
    docs: {
      description: 'Disallow early returns and hooks after returns in components or hooks',
    },
    schema: [],
  },
  create(context) {
    return {
      'FunctionDeclaration, FunctionExpression, ArrowFunctionExpression'(node) {
        // Only process functions with a block body
        if (!node.body || node.body.type !== 'BlockStatement') {
          return;
        }

        const stmts = node.body.body;
        let sawReturn = false;

        for (const stmt of stmts) {
          if (stmt.type === 'ReturnStatement') {
            sawReturn = true;
            continue;
          }

          if (sawReturn && isTopLevelHookCall(stmt)) {
            context.report({
              node: stmt,
              message: 'Do not call hooks after returning from a component or custom hook.',
            });
          }
        }
      },
    };
  },
};

function isTopLevelHookCall(stmt) {
  return (
    stmt.type === 'ExpressionStatement' &&
    stmt.expression.type === 'CallExpression' &&
    stmt.expression.callee.type === 'Identifier' &&
    stmt.expression.callee.name.startsWith('use')
  );
}
