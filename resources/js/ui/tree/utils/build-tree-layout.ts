import dagre, { Graph } from '@dagrejs/dagre';
import { Edge, Node } from '@xyflow/react';
import { ReactNode } from 'react';

import TreeBranchDataDefinition from '../definitions/tree-branch-data-definition';
import TreeBranchDefinition from '../definitions/tree-branch-definition';
import TreeFlowNodeDataDefinition from '../definitions/tree-flow-node-data-definition';
import TreeLayoutDefinition from '../definitions/tree-layout-definition';
import TreeNodeDefinition from '../definitions/tree-node-definition';
import TreeStructureDefinition from '../definitions/tree-structure-definition';
import TreeColor from '../enums/tree-color';

export const DEFAULT_NODE_WIDTH = 288;
export const DEFAULT_NODE_HEIGHT = 112;
const NODE_SEPARATION = 48;
const RANK_SEPARATION = 72;

interface BuildTreeLayoutParams<TData> {
  nodes: Array<TreeNodeDefinition<TData>>;
  branches: TreeBranchDefinition[];
  structure: TreeStructureDefinition;
  render_node: (node: TreeNodeDefinition<TData>) => ReactNode;
  on_node_activate?: (node: TreeNodeDefinition<TData>) => void;
  default_node_width?: number;
  default_node_height?: number;
  default_branch_color?: TreeColor;
}

/**
 * Build the structural accessible name for one interactive Tree node by
 * appending generic hierarchy level/parent/child-count language after the
 * domain-supplied accessible label, so screen-reader users never have to
 * infer hierarchy from visual position alone.
 *
 * @param  domainAccessibleLabel  Domain-supplied factual node description.
 * @param  level  One-based hierarchy level for the node.
 * @param  parentLabel  Parent node's short label, when a parent exists.
 * @param  childCount  Number of direct children the node has.
 * @return  Full structural accessible name for the node.
 */
const buildStructuralAccessibleLabel = (
  domainAccessibleLabel: string,
  level: number,
  parentLabel: string | null,
  childCount: number
): string => {
  const segments = [domainAccessibleLabel, `Level ${level}.`];

  if (parentLabel !== null) {
    segments.push(`Parent: ${parentLabel}.`);
  }

  segments.push(
    childCount === 1 ? '1 child node.' : `${childCount} child nodes.`
  );

  return segments.join(' ');
};

/**
 * Convert generic Tree nodes/branches into a deterministic top-to-bottom
 * React Flow layout using Dagre. Dagre owns rank/position; the generic Tree
 * owns every other visual and accessible concern. Node order is preserved
 * where Dagre allows, multiple roots render as a forest, and each node's
 * accessible name is augmented with structural context resolved from
 * `structure`.
 *
 * @param  params  Nodes, branches, resolved structure, and rendering/layout configuration.
 * @return  Internally generated React Flow nodes and edges.
 */
export const buildTreeLayout = <TData>({
  nodes,
  branches,
  structure,
  render_node: renderNode,
  on_node_activate: onNodeActivate,
  default_node_width: defaultNodeWidth = DEFAULT_NODE_WIDTH,
  default_node_height: defaultNodeHeight = DEFAULT_NODE_HEIGHT,
  default_branch_color: defaultBranchColor = TreeColor.DANUBE,
}: BuildTreeLayoutParams<TData>): TreeLayoutDefinition => {
  const graph: Graph = new dagre.graphlib.Graph();

  graph.setGraph({
    rankdir: 'TB',
    nodesep: NODE_SEPARATION,
    ranksep: RANK_SEPARATION,
  });
  graph.setDefaultEdgeLabel(() => ({}));

  nodes.forEach((node) => {
    graph.setNode(node.id, {
      width: node.width ?? defaultNodeWidth,
      height: node.height ?? defaultNodeHeight,
    });
  });

  branches.forEach((branch) => {
    if (!graph.hasNode(branch.source_id) || !graph.hasNode(branch.target_id)) {
      return;
    }

    graph.setEdge(branch.source_id, branch.target_id);
  });

  dagre.layout(graph);

  const nodeById = new Map(nodes.map((node) => [node.id, node]));

  const flowNodes: Array<Node<TreeFlowNodeDataDefinition>> = nodes.map(
    (node) => {
      const width = node.width ?? defaultNodeWidth;
      const height = node.height ?? defaultNodeHeight;
      const layoutPosition = graph.node(node.id);
      const level = structure.level_by_node_id.get(node.id) ?? 1;
      const parentId = structure.parent_id_by_node_id.get(node.id) ?? null;
      const parentLabel =
        parentId !== null ? (nodeById.get(parentId)?.label ?? null) : null;
      const childCount = structure.child_count_by_node_id.get(node.id) ?? 0;

      return {
        id: node.id,
        type: 'treeNode',
        position: {
          x: (layoutPosition?.x ?? 0) - width / 2,
          y: (layoutPosition?.y ?? 0) - height / 2,
        },
        style: { width, height },
        draggable: false,
        connectable: false,
        selectable: false,
        focusable: false,
        deletable: false,
        ariaRole: 'presentation',
        data: {
          content: renderNode(node),
          color: node.color,
          accessible_label: buildStructuralAccessibleLabel(
            node.accessibility_label,
            level,
            parentLabel,
            childCount
          ),
          on_activate: onNodeActivate ? () => onNodeActivate(node) : undefined,
        },
      };
    }
  );

  const flowEdges: Array<Edge<TreeBranchDataDefinition>> = branches
    .filter(
      (branch) =>
        graph.hasNode(branch.source_id) && graph.hasNode(branch.target_id)
    )
    .map((branch) => ({
      id: branch.id,
      type: 'treeBranch',
      source: branch.source_id,
      target: branch.target_id,
      focusable: false,
      selectable: false,
      deletable: false,
      reconnectable: false,
      ariaRole: 'presentation',
      domAttributes: { 'aria-hidden': true },
      data: {
        color: branch.color ?? defaultBranchColor,
      },
    }));

  return { flow_nodes: flowNodes, flow_edges: flowEdges };
};
