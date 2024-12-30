import { useEffect, useState } from 'react';

import UseScrollIconMenuDefinition from '../definitions/use-scroll-icon-menu-definition';
import UseScrollIconMenuState from '../types/use-scroll-icon-menu-state';

export const useScrollIconMenu = (): UseScrollIconMenuDefinition => {
  const [scrollY, setScrollY] = useState<UseScrollIconMenuState['scrollY']>(0);
  const [isMobile, setIsMobile] =
    useState<UseScrollIconMenuState['isMobile']>(false);

  const handleScroll = () => setScrollY(window.scrollY);

  useEffect(() => {
    window.addEventListener('scroll', handleScroll);

    const checkMobile = () => setIsMobile(window.innerWidth < 1024);

    checkMobile();
    window.addEventListener('resize', checkMobile);

    return () => {
      window.removeEventListener('scroll', handleScroll);
      window.removeEventListener('resize', checkMobile);
    };
  }, []);

  return {
    scrollY,
    isMobile,
  };
};
