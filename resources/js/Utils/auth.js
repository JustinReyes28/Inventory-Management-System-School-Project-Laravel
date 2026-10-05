import { usePage } from '@inertiajs/react';

/**
 * Spatie-backed authorization data shared by HandleInertiaRequests.
 *
 * `can()` drives conditional React rendering of navigation and mutation
 * buttons. Backend routes re-check the same permissions, so this is a UI
 * convenience — never the authority for access control.
 */
export default function useAuth() {
    const page = usePage();
    const auth = page.props.auth || {};
    const roles = auth.roles || [];
    const permissions = auth.permissions || [];

    return {
        user: auth.user || page.props.user || null,
        roles,
        permissions,
        can: (permission) => permissions.includes(permission),
        hasRole: (role) => roles.map((name) => String(name).toLowerCase()).includes(String(role).toLowerCase()),
    };
}
