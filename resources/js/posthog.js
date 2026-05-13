import posthog from 'posthog-js';

const key = import.meta.env.VITE_POSTHOG_KEY;
const host = import.meta.env.VITE_POSTHOG_HOST || 'https://eu.i.posthog.com';

if (key) {
    posthog.init(key, {
        api_host: host,
        person_profiles: 'identified_only',
        capture_pageview: true,
        capture_pageleave: true,
        session_recording: {
            maskAllInputs: true,
            maskTextSelector: '[data-ph-mask]',
        },
        disable_session_recording: false,
        autocapture: true,
    });

    const userMeta = document.querySelector('meta[name="ph-user-id"]');
    if (userMeta?.content) {
        const emailMeta = document.querySelector('meta[name="ph-user-email"]');
        const nameMeta = document.querySelector('meta[name="ph-user-name"]');
        posthog.identify(userMeta.content, {
            email: emailMeta?.content || undefined,
            name: nameMeta?.content || undefined,
        });
    }

    window.posthog = posthog;
}

export default posthog;
