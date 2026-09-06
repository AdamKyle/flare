import '@xyflow/react/dist/style.css';

import { Node, ReactFlow, ReactFlowProvider } from '@xyflow/react';
import clsx from 'clsx';
import React, {
  ReactNode,
  useCallback,
  useMemo,
  useRef,
  useState,
} from 'react';

import TreeBranch from './components/tree-branch';
import TreeControls from './components/tree-controls';
import TreeInitialViewport from './components/tree-initial-viewport';
import TreeList from './components/tree-list';
import TreeNode from './components/tree-node';
import TreeScreenReaderSummary from './components/tree-screen-reader-summary';
import TreeFlowNodeDataDefinition from './definitions/tree-flow-node-data-definition';
import TreeLayoutDefinition from './definitions/tree-layout-definition';
import TreeNodeDefinition from './definitions/tree-node-definition';
import TreeStructureDefinition from './definitions/tree-structure-definition';
import TreeMobileMode from './enums/tree-mobile-mode';
import { useTreeDecorativeEdgeAccessibility } from './hooks/use-tree-decorative-edge-accessibility';
import TreeProps from './types/tree-props';
import { buildTreeLayout, DEFAULT_NODE_WIDTH } from './utils/build-tree-layout';
import { resolveTreeStructure } from './utils/resolve-tree-structure';
import { isTreeStructureValid } from './utils/validate-tree-structure';

const NODE_TYPES = { treeNode: TreeNode };
const EDGE_TYPES = { treeBranch: TreeBranch };

/**
 * No-op handler passed to React Flow's `onNodeClick`. Its only purpose is to
 * keep the node wrapper's computed `hasPointerEvents` true so pointer/tap
 * events reach the real interactive `<button>` rendered by `TreeNode` —
 * React Flow only sets `pointer-events: all` on a node wrapper when the node
 * is selectable/draggable or an `onNodeClick`-family handler is supplied,
 * and this generic Tree deliberately keeps every node non-selectable and
 * non-draggable. Real activation stays owned entirely by `TreeNode`'s own
 * button `onClick`; this handler never runs domain logic.
 */
const handleNodeClick = (): void => {};

/**
 * Resolve the horizontal center and top edge of a Tree's root node/root
 * group in flow-space coordinates, used to open the Tree at a consistent
 * readable zoom with the root visible near the upper-central portion of the
 * viewport instead of an automatic whole-tree `fitView`.
 *
 * @param  rootNodes  Already-resolved root-level Tree nodes.
 * @param  flowNodeById  Laid-out React Flow nodes, keyed by id.
 * @param  defaultNodeWidth  Fallback node width for nodes without an explicit width.
 * @return  Root anchor point in flow-space coordinates.
 */
const resolveRootAnchor = <TData,>(
  rootNodes: TreeProps<TData>['nodes'],
  flowNodeById: Map<string, Node<TreeFlowNodeDataDefinition>>,
  defaultNodeWidth: number | undefined
): { center_x: number; top_y: number } => {
  if (rootNodes.length === 0) {
    return { center_x: 0, top_y: 0 };
  }

  const rootAnchors = rootNodes.map((node) => {
    const flowNode = flowNodeById.get(node.id);
    const width = node.width ?? defaultNodeWidth ?? DEFAULT_NODE_WIDTH;

    return {
      center_x: (flowNode?.position.x ?? 0) + width / 2,
      top_y: flowNode?.position.y ?? 0,
    };
  });

  const centerX =
    rootAnchors.reduce((sum, anchor) => sum + anchor.center_x, 0) /
    rootAnchors.length;
  const topY = Math.min(...rootAnchors.map((anchor) => anchor.top_y));

  return { center_x: centerX, top_y: topY };
};

const EMPTY_STRUCTURE: TreeStructureDefinition = {
  parent_id_by_node_id: new Map(),
  child_count_by_node_id: new Map(),
  level_by_node_id: new Map(),
  root_count: 0,
  max_depth: 0,
};

const EMPTY_LAYOUT: TreeLayoutDefinition = {
  flow_nodes: [],
  flow_edges: [],
};

/**
 * Generic shared Flare `Tree`: a bounded, pannable/zoomable top-to-bottom
 * hierarchy visualization backed by `@xyflow/react` for graph rendering and
 * `@dagrejs/dagre` for deterministic layout, both hidden completely behind
 * this component. Flare owns node/branch presentation, colors, dark mode,
 * accessibility, and mobile behavior. A malformed structure (duplicate node
 * ids, a branch referencing an unknown node, a node with more than one
 * parent, or a cycle) renders a generic accessible fallback instead of
 * crashing or silently repairing the data. No file outside
 * `resources/js/ui/tree/**` may import either library directly.
 */
const Tree = <TData,>({
  nodes,
  branches,
  render_node: renderNode,
  render_list_node: renderListNode,
  on_node_activate: onNodeActivate,
  accessibility_label: accessibilityLabel,
  mobile_mode: mobileMode = TreeMobileMode.TREE,
  empty_state: emptyState,
  available_empty_state: availableEmptyState,
  default_node_width: defaultNodeWidth,
  default_node_height: defaultNodeHeight,
  default_branch_color: defaultBranchColor,
  default_zoom: defaultZoom = 1,
  default_focus_node_id: defaultFocusNodeId,
}: TreeProps<TData>): ReactNode => {
  const containerRef = useRef<HTMLDivElement>(null);
  const [isFullscreen, setIsFullscreen] = useState(false);

  const isStructureValid = useMemo(
    () => isTreeStructureValid(nodes, branches),
    [nodes, branches]
  );

  const handleNodeActivateWrapped = useCallback(
    (node: TreeNodeDefinition<TData>): void => {
      if (!onNodeActivate) {
        return;
      }

      const container = containerRef.current;
      const isTreeFullscreen = document.fullscreenElement === container;

      if (!isTreeFullscreen) {
        onNodeActivate(node);

        return;
      }

      document
        .exitFullscreen()
        .then(() => onNodeActivate(node))
        .catch(() => onNodeActivate(node));
    },
    [onNodeActivate]
  );

  const handleNodeActivate = onNodeActivate
    ? handleNodeActivateWrapped
    : undefined;

  const { structure, layout } = useMemo(() => {
    if (!isStructureValid) {
      return { structure: EMPTY_STRUCTURE, layout: EMPTY_LAYOUT };
    }

    const resolvedStructure = resolveTreeStructure(nodes, branches);
    const resolvedLayout = buildTreeLayout({
      nodes,
      branches,
      structure: resolvedStructure,
      render_node: renderNode,
      on_node_activate: handleNodeActivate,
      default_node_width: defaultNodeWidth,
      default_node_height: defaultNodeHeight,
      default_branch_color: defaultBranchColor,
    });

    return { structure: resolvedStructure, layout: resolvedLayout };
  }, [
    isStructureValid,
    nodes,
    branches,
    renderNode,
    handleNodeActivate,
    defaultNodeWidth,
    defaultNodeHeight,
    defaultBranchColor,
  ]);

  const rootAnchor = useMemo(() => {
    const rootNodes = nodes.filter(
      (node) => structure.parent_id_by_node_id.get(node.id) === null
    );
    const flowNodeById = new Map(
      layout.flow_nodes.map((flowNode) => [flowNode.id, flowNode])
    );

    return resolveRootAnchor(rootNodes, flowNodeById, defaultNodeWidth);
  }, [nodes, structure, layout.flow_nodes, defaultNodeWidth]);

  const defaultAnchor = useMemo(() => {
    if (defaultFocusNodeId === undefined) {
      return rootAnchor;
    }

    const flowNodeById = new Map(
      layout.flow_nodes.map((flowNode) => [flowNode.id, flowNode])
    );

    if (!flowNodeById.has(defaultFocusNodeId)) {
      return rootAnchor;
    }

    const focusNodes = nodes.filter((node) => node.id === defaultFocusNodeId);

    return resolveRootAnchor(focusNodes, flowNodeById, defaultNodeWidth);
  }, [
    defaultFocusNodeId,
    nodes,
    layout.flow_nodes,
    defaultNodeWidth,
    rootAnchor,
  ]);

  useTreeDecorativeEdgeAccessibility({
    container_ref: containerRef,
    edge_count: layout.flow_edges.length,
  });

  const renderVisualTree = (): ReactNode => (
    <div
      ref={containerRef}
      className={clsx(
        'relative w-full min-w-0 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800',
        isFullscreen ? 'h-screen w-screen' : 'h-96 md:h-128 lg:h-160'
      )}
    >
      <TreeScreenReaderSummary
        accessibility_label={accessibilityLabel}
        total_nodes={nodes.length}
        root_count={structure.root_count}
        max_depth={structure.max_depth}
      />
      <ReactFlowProvider>
        <ReactFlow
          nodes={layout.flow_nodes}
          edges={layout.flow_edges}
          nodeTypes={NODE_TYPES}
          edgeTypes={EDGE_TYPES}
          onNodeClick={handleNodeClick}
          minZoom={0.1}
          nodesDraggable={false}
          nodesConnectable={false}
          nodesFocusable={false}
          edgesFocusable={false}
          edgesReconnectable={false}
          elementsSelectable={false}
          disableKeyboardA11y
          panOnScroll
          zoomOnPinch
          proOptions={{ hideAttribution: true }}
        >
          <TreeInitialViewport
            container_ref={containerRef}
            default_focus_center_x={defaultAnchor.center_x}
            default_focus_top_y={defaultAnchor.top_y}
            default_zoom={defaultZoom}
          />
          <TreeControls
            container_ref={containerRef}
            default_focus_center_x={defaultAnchor.center_x}
            default_focus_top_y={defaultAnchor.top_y}
            default_zoom={defaultZoom}
            on_fullscreen_change={setIsFullscreen}
          />
        </ReactFlow>
      </ReactFlowProvider>
    </div>
  );

  const renderAvailableList = (): ReactNode => (
    <TreeList
      nodes={nodes}
      render_node={renderNode}
      render_list_node={renderListNode}
      on_node_activate={handleNodeActivate}
      available_empty_state={availableEmptyState}
    />
  );

  if (!isStructureValid) {
    return (
      <p className="text-sm text-gray-600 dark:text-gray-400" role="alert">
        This Tree could not be displayed because its structure is invalid.
      </p>
    );
  }

  if (nodes.length === 0) {
    return <>{emptyState ?? null}</>;
  }

  if (mobileMode === TreeMobileMode.ONLY_WHATS_AVAILABLE) {
    return (
      <div className="w-full min-w-0">
        <div className="md:hidden">{renderAvailableList()}</div>
        <div className="hidden md:block">{renderVisualTree()}</div>
      </div>
    );
  }

  return <div className="w-full min-w-0">{renderVisualTree()}</div>;
};

export default Tree;
