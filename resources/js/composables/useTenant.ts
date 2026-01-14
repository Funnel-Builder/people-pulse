import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Composable for tenant-aware URL generation
 */
export function useTenant() {
    const page = usePage();

    const tenant = computed(() => page.props.tenant as {
        id: string;
        name: string;
        prefix: string;
    } | null);

    const tenantPrefix = computed(() => tenant.value?.prefix || '');

    /**
     * Generate a tenant-aware URL
     */
    function url(path: string): string {
        // If already has tenant prefix, return as-is
        if (path.startsWith('/app/')) {
            return path;
        }
        
        // Ensure path starts with /
        const normalizedPath = path.startsWith('/') ? path : `/${path}`;
        
        return `${tenantPrefix.value}${normalizedPath}`;
    }

    return {
        tenant,
        tenantPrefix,
        url,
    };
}
