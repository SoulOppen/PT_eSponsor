import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Settings from '../../Pages/Dashboard/Settings.vue';

describe('Settings page', () => {
    it('renders name, slug, and bio fields', () => {
        const wrapper = mount(Settings, {
            props: { site: { name: 'Ana', slug: 'ana', bio: 'Hi', avatar_url: null } },
        });
        expect(wrapper.find('input[name="name"]').exists()).toBe(true);
        expect(wrapper.find('input[name="slug"]').exists()).toBe(true);
        expect(wrapper.find('textarea[name="bio"]').exists()).toBe(true);
    });

    it('shows avatar preview when avatar_url is set', () => {
        const wrapper = mount(Settings, {
            props: { site: { name: '', slug: '', bio: '', avatar_url: '/img/test.jpg' } },
        });
        expect(wrapper.find('img').attributes('src')).toBe('/img/test.jpg');
    });
});
