import { Driver } from 'driver.js';
import { useCallback, useEffect, useRef } from 'react';

import UseGuideWalkthroughDriverDefinition from './definitions/use-guide-walkthrough-driver-definition';
import { presentGuideStep } from '../driver/driver-adapter';
import PresentGuideStepParams from '../types/present-guide-step-params';

export const useGuideWalkthroughDriver =
  (): UseGuideWalkthroughDriverDefinition => {
    const driverRef = useRef<Driver | null>(null);
    const activeTargetRef = useRef<HTMLElement | null>(null);
    const targetRectRef = useRef<DOMRect | null>(null);
    const pendingAnimationFrameRef = useRef<number | null>(null);
    const mutationObserverRef = useRef<MutationObserver | null>(null);
    const resizeObserverRef = useRef<ResizeObserver | null>(null);
    const internalDestroyRef = useRef(false);

    const scheduleGeometryRefresh = useCallback((): void => {
      if (pendingAnimationFrameRef.current !== null) {
        return;
      }

      pendingAnimationFrameRef.current = window.requestAnimationFrame(() => {
        pendingAnimationFrameRef.current = null;

        const driverInstance = driverRef.current;
        const target = activeTargetRef.current;

        if (!driverInstance || !target || !target.isConnected) {
          return;
        }

        const nextRect = target.getBoundingClientRect();
        const previousRect = targetRectRef.current;
        const geometryChanged =
          !previousRect ||
          nextRect.x !== previousRect.x ||
          nextRect.y !== previousRect.y ||
          nextRect.width !== previousRect.width ||
          nextRect.height !== previousRect.height;

        if (!geometryChanged) {
          return;
        }

        targetRectRef.current = nextRect;

        const isOutsideViewport =
          nextRect.bottom <= 0 ||
          nextRect.right <= 0 ||
          nextRect.top >= window.innerHeight ||
          nextRect.left >= window.innerWidth;

        if (!isOutsideViewport) {
          driverInstance.refresh();

          return;
        }

        const reduceMotion = window.matchMedia(
          '(prefers-reduced-motion: reduce)'
        ).matches;

        target.scrollIntoView({
          block: 'center',
          inline: 'center',
          behavior: reduceMotion ? 'auto' : 'smooth',
        });

        pendingAnimationFrameRef.current = window.requestAnimationFrame(() => {
          pendingAnimationFrameRef.current = null;

          if (
            driverRef.current !== driverInstance ||
            activeTargetRef.current !== target ||
            !target.isConnected
          ) {
            return;
          }

          targetRectRef.current = target.getBoundingClientRect();
          driverInstance.refresh();
        });
      });
    }, []);

    const destroyDriver = useCallback((): void => {
      console.log('[GuideWalkthrough] driver: destroyDriver called', {
        hasDriver: driverRef.current !== null,
        internalDestroyBefore: internalDestroyRef.current,
        activeTargetConnected: activeTargetRef.current?.isConnected ?? null,
      });

      if (pendingAnimationFrameRef.current !== null) {
        window.cancelAnimationFrame(pendingAnimationFrameRef.current);
        pendingAnimationFrameRef.current = null;
      }

      mutationObserverRef.current?.disconnect();
      mutationObserverRef.current = null;
      resizeObserverRef.current?.disconnect();
      resizeObserverRef.current = null;
      window.removeEventListener('resize', scheduleGeometryRefresh);
      activeTargetRef.current = null;
      targetRectRef.current = null;

      internalDestroyRef.current = true;
      console.log('[GuideWalkthrough] driver: internal destroy started');

      try {
        driverRef.current?.destroy();
      } finally {
        console.log('[GuideWalkthrough] driver: internal destroy finished');
        internalDestroyRef.current = false;
        driverRef.current = null;
      }
    }, [scheduleGeometryRefresh]);

    const presentDriverStep = useCallback(
      (params: PresentGuideStepParams): void => {
        destroyDriver();
        console.log('[GuideWalkthrough] driver: presenting step', {
          title: params.step.title,
          target: params.step.target ?? null,
        });
        driverRef.current = presentGuideStep({
          ...params,
          on_close: () => {
            console.log('[GuideWalkthrough] driver: on_close invoked', {
              internalDestroy: internalDestroyRef.current,
            });

            if (internalDestroyRef.current) {
              console.log(
                '[GuideWalkthrough] driver: on_close ignored for internal destroy'
              );
              return;
            }

            console.log('[GuideWalkthrough] driver: forwarding user close');
            params.on_close();
          },
        });

        if (!params.element) {
          return;
        }

        activeTargetRef.current = params.element;
        targetRectRef.current = params.element.getBoundingClientRect();

        resizeObserverRef.current = new ResizeObserver(scheduleGeometryRefresh);
        resizeObserverRef.current.observe(params.element);

        mutationObserverRef.current = new MutationObserver(
          scheduleGeometryRefresh
        );
        mutationObserverRef.current.observe(document.body, {
          childList: true,
          subtree: true,
          attributes: true,
          attributeFilter: ['class', 'style', 'hidden', 'aria-hidden'],
        });

        window.addEventListener('resize', scheduleGeometryRefresh);
      },
      [destroyDriver, scheduleGeometryRefresh]
    );

    useEffect(() => destroyDriver, [destroyDriver]);

    return { presentDriverStep, destroyDriver };
  };
