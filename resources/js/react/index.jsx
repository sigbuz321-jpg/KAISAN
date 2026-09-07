import React from 'react';
import { createRoot } from 'react-dom/client';
import { MotionProbe } from './components/MotionProbe';
import { SoundToggle } from './components/SoundToggle';

/**
 * Registry of available React micro-island components.
 * Keys match data-react-island="<name>" attribute on DOM elements.
 */
const registry = {
    'motion-probe': MotionProbe,
    'sound-toggle': SoundToggle,
};

/**
 * Register a React component as a micro-island.
 * @param {string} name
 * @param {React.ComponentType} Component
 */
export function registerIsland(name, Component) {
    registry[name] = Component;
}

// Track mounted roots to clean up or avoid duplicate mounts
const mountedRoots = new WeakMap();

/**
 * Scans the DOM for [data-react-island] containers and mounts their React component.
 * Safe to call repeatedly after Livewire DOM morphs.
 */
export function mountIslands() {
    const containers = document.querySelectorAll('[data-react-island]');

    containers.forEach((container) => {
        if (mountedRoots.has(container)) {
            return;
        }

        const islandName = container.dataset.reactIsland;
        const Component = registry[islandName];

        if (!Component) {
            return;
        }

        let props = {};
        if (container.dataset.reactProps) {
            try {
                props = JSON.parse(container.dataset.reactProps);
            } catch (e) {
                console.error(`Invalid JSON props for island ${islandName}:`, e);
            }
        }

        const root = createRoot(container);
        root.render(<Component {...props} container={container} />);
        mountedRoots.set(container, root);
    });
}

// Initial mount on DOMContentLoaded
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        mountIslands();
    });

    // Re-mount on Livewire navigate / morph updates
    document.addEventListener('livewire:navigated', () => {
        mountIslands();
    });

    // Re-mount on custom event for dynamic SPA updates
    window.addEventListener('kaisan:mount-islands', () => {
        mountIslands();
    });
}
