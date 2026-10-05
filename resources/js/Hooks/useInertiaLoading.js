import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function useInertiaLoading() {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const removeStartListener = router.on('start', () => setLoading(true));
        const removeFinishListener = router.on('finish', () => setLoading(false));

        return () => {
            removeStartListener();
            removeFinishListener();
        };
    }, []);

    return loading;
}
