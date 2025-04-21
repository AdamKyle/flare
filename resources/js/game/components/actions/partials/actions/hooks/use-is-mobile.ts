import { useEffect, useState } from 'react';

import UseIsMobileDefinition from '../definitions/use-is-mobile-definition';

export const useIsMobile = (): UseIsMobileDefinition => {
  const [isMobile, setIsMobile] = useState<boolean>(false);

  useEffect(() => {
    const checkMobile = () => setIsMobile(window.innerWidth <= 1024);

    checkMobile();
    window.addEventListener('resize', checkMobile);

    return () => {
      window.removeEventListener('resize', checkMobile);
    };
  }, []);

  return {
    isMobile,
  };
};
